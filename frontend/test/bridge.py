""""
Unifit check-in bridge: Azure IoT Hub (Event Hub-compatible endpoint) -> Supabase.

ESP32 + PN532 -> IoT Hub -> this script (runs on the educloud VM) -> clock_in_times

Config comes from environment variables (see .env.example), never hard-coded keys.
"""
from __future__ import annotations

import asyncio
import logging
import os
from datetime import datetime, timezone

from azure.eventhub import TransportType
from azure.eventhub.aio import EventHubConsumerClient
from supabase import acreate_client, AsyncClient

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
log = logging.getLogger("unifit-bridge")

EH_CONN_STR = os.environ["IOTHUB_EH_CONNECTION_STRING"]
CONSUMER_GROUP = os.environ.get("IOTHUB_CONSUMER_GROUP", "$Default")
SUPABASE_URL = os.environ["SUPABASE_URL"]            # e.g. http://localhost:8000 (Kong) on the VM
SUPABASE_KEY = os.environ["SUPABASE_SERVICE_ROLE_KEY"]
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


async def lookup(table: str, key_col: str, key, want: str):
    res = await sb.table(table).select(want).eq(key_col, key).limit(1).execute()
    return res.data[0][want] if res.data else None


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
        known = await lookup("students", "student_nr", student_nr, "student_nr")
    except Exception:
        # Supabase unreachable etc. -> don't checkpoint, so it is retried after a restart
        log.exception("Student lookup failed for %s", student_nr)
        return
    if known is None:
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
    except (KeyError, ValueError, TypeError):
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
        await sb.table("clock_in_times").upsert(
            row, on_conflict="student_nr,datetime", ignore_duplicates=True
        ).execute()
        log.info("Check-in: student %s subject %s via %s", student_nr, subject_id, device_id)
    except Exception:
        # don't checkpoint -> message is retried if the process restarts
        log.exception("Insert failed for %s", row)
        return

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
