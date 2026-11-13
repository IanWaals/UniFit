"""
Unifit check-in bridge: Azure IoT Hub (Event Hub-compatible endpoint) -> Supabase.

ESP32 + PN532 -> IoT Hub -> this script (runs on the educloud VM) -> clock_in_times

Config: a .env file next to this script (loaded automatically), or environment
variables set by systemd / the shell. Already-set variables always win over .env.
"""
from __future__ import annotations

import asyncio
import logging
import os
from datetime import datetime, timezone
from pathlib import Path

import httpx
from azure.eventhub import TransportType
from azure.eventhub.aio import EventHubConsumerClient
from postgrest.exceptions import APIError
from supabase import acreate_client, AsyncClient

# --- .env loading ---------------------------------------------------------------------
# Looks for .env in the same folder as this script, so it works no matter which directory
# you start it from (systemd, cron, another folder). interpolate=False so values containing
# "$" (like "$Default" or password hashes) are taken literally.
ENV_FILE = Path(__file__).resolve().with_name(".env")
try:
    from dotenv import load_dotenv
    load_dotenv(ENV_FILE, override=False, interpolate=False)
except ImportError:
    pass  # python-dotenv not installed -> rely on real environment variables

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("unifit-bridge")
# The Azure SDK is very chatty at INFO; only show its warnings/errors
logging.getLogger("azure").setLevel(logging.WARNING)


def require(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise SystemExit(f"Missing setting {name}: put it in {ENV_FILE} or the environment")
    return value


EH_CONN_STR = require("IOTHUB_EH_CONNECTION_STRING")
SUPABASE_URL = require("SUPABASE_URL")            # e.g. http://localhost:8000 (Kong) on the VM
SUPABASE_KEY = require("SUPABASE_SERVICE_ROLE_KEY")
CONSUMER_GROUP = os.environ.get("IOTHUB_CONSUMER_GROUP", "$Default")
UID_FIELD = os.environ.get("PAYLOAD_UID_FIELD", "uid")  # JSON key the ESP32 puts the card UID in
# How the hex UID becomes a student_nr: "big" -> 5ACCC325 = 1523368741, "little" -> 633588826
UID_BYTE_ORDER = os.environ.get("UID_BYTE_ORDER", "big")
# "-1" = replay everything IoT Hub still keeps (safe, duplicates are ignored), "@latest" = only new scans
STARTING_POSITION = os.environ.get("STARTING_POSITION", "-1")

sb: AsyncClient | None = None


def normalise_uid(raw) -> str:
    """'04:a1:b2 c3' / '04A1B2C3' / [4,161,178,195] -> '04A1B2C3'"""
    if isinstance(raw, list):
        return "".join(f"{b:02X}" for b in raw)
    return "".join(ch for ch in str(raw) if ch.isalnum()).upper()


def is_permanent(err: APIError) -> bool:
    """Errors that will fail again on retry (bad data, missing FK, wrong column...).
    Everything else (DB restarting, gateway down, timeouts) is treated as temporary."""
    code = str(getattr(err, "code", "") or "")
    return code[:2] in ("22", "23", "42") or code.startswith(("PGRST1", "PGRST2"))


async def with_retry(what: str, make_request):
    """Run a Supabase request; on temporary failures wait and retry (1s, 2s, 4s ... max 60s).
    This keeps scans from being lost when Supabase is restarting or not up yet at boot."""
    delay = 1
    while True:
        try:
            return await make_request()
        except APIError as e:
            if is_permanent(e):
                raise
            log.warning("%s failed (%s), retrying in %ss", what, e, delay)
        except (httpx.HTTPError, OSError) as e:
            log.warning("%s failed (%r), retrying in %ss", what, e, delay)
        await asyncio.sleep(delay)
        delay = min(delay * 2, 60)


async def student_exists(student_nr: int) -> bool:
    res = await with_retry(
        "Student lookup",
        lambda: sb.table("students").select("student_nr").eq("student_nr", student_nr).limit(1).execute(),
    )
    return bool(res.data)


async def on_event(partition_context, event):
    if event is None:
        return
    props = event.system_properties or {}
    hub_device = props.get(b"iothub-connection-device-id", b"").decode()

    try:
        body = event.body_as_json()
    except Exception:
        log.warning("Non-JSON message from %s: %r", hub_device, event.body_as_str())
        await partition_context.update_checkpoint(event)
        return

    # deviceId in the payload == subject_id
    device_id = str(body.get("deviceId") or hub_device or "unknown")
    try:
        subject_id = int(body["deviceId"])
    except (KeyError, ValueError, TypeError):
        log.warning("Bad/missing deviceId from %s: %s", hub_device, body)
        await partition_context.update_checkpoint(event)
        return

    uid = normalise_uid(body.get(UID_FIELD, ""))
    try:
        if not uid:
            raise ValueError("empty uid")
        student_nr = int.from_bytes(bytes.fromhex(uid), UID_BYTE_ORDER)
    except ValueError:
        log.warning("No valid '%s' in message from %s: %s", UID_FIELD, device_id, body)
        await partition_context.update_checkpoint(event)
        return

    # Check the student exists so unknown cards give a clear log line instead of an FK error
    try:
        known = await student_exists(student_nr)
    except APIError as e:
        log.error("Student lookup rejected for %s: %s", student_nr, e)
        await partition_context.update_checkpoint(event)
        return
    if not known:
        log.warning("Unknown card %s (student_nr %s) at subject %s", uid, student_nr, subject_id)
        await partition_context.update_checkpoint(event)
        return

    # Prefer the ESP32's own scan time (epoch, UTC). Fall back to the time IoT Hub received it
    # if the epoch is missing or clearly wrong (e.g. NTP not synced yet -> 1970).
    enqueued = event.enqueued_time.astimezone(timezone.utc)
    scanned = enqueued
    try:
        dev_time = datetime.fromtimestamp(int(body["epoch"]), timezone.utc)
        if abs((enqueued - dev_time).total_seconds()) < 24 * 3600:
            scanned = dev_time
        else:
            log.warning("Device %s clock looks off (%s vs %s), using enqueued time",
                        device_id, dev_time, enqueued)
    except (KeyError, ValueError, TypeError, OverflowError, OSError):
        pass

    row = {
        "student_nr": student_nr,
        "subject_id": subject_id,
        "datetime": scanned.isoformat(),
    }
    try:
        # INSERT ... ON CONFLICT (student_nr, datetime) DO NOTHING
        # The primary key is (student_nr, datetime) and both come from the message itself,
        # so a message replayed after a restart is ignored instead of inserted twice.
        await with_retry(
            "Insert",
            lambda: sb.table("clock_in_times").upsert(
                row, on_conflict="student_nr,datetime", ignore_duplicates=True
            ).execute(),
        )
        log.info("Check-in: student %s subject %s via %s", student_nr, subject_id, device_id)
    except APIError as e:
        # e.g. subject_id that doesn't exist in subjects -> will never succeed, so skip it
        log.error("Insert rejected for %s: %s", row, e)

    await partition_context.update_checkpoint(event)


async def on_error(partition_context, error):
    pid = partition_context.partition_id if partition_context else "-"
    log.error("Event Hub error on partition %s: %r", pid, error)


async def main():
    global sb
    sb = await acreate_client(SUPABASE_URL, SUPABASE_KEY)

    client = EventHubConsumerClient.from_connection_string(
        EH_CONN_STR,
        consumer_group=CONSUMER_GROUP,
        # AMQP over websockets = port 443 only; plain AMQP needs 5671, often blocked on school/cloud nets
        transport_type=TransportType.AmqpOverWebsocket,
    )
    log.info("Listening for NFC check-ins (consumer group %s, start %s)...",
             CONSUMER_GROUP, STARTING_POSITION)
    async with client:
        await client.receive(
            on_event=on_event,
            on_error=on_error,
            starting_position=STARTING_POSITION,
        )


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        log.info("Stopped")
