
import asyncio
import signal
from bleak import BleakClient, BleakScanner
from evdev import UInput, ecodes

# ==========================================
# BLE CONFIGURATION
# ==========================================

BLE_NAME = "Uni-BT-NFC"

UUID_SERVICE = "4fafc201-1fb5-459e-8fcc-c5c9c331914b"
UUID_JOYSTICK = "beb5483e-36e1-4688-b7f5-ea07361b26a8"

# ==========================================
# JOYSTICK CONFIGURATION
# ==========================================

DEADZONE = 25
NEUTRAL_COMMAND = None

# ==========================================
# VIRTUAL KEYBOARD
# ==========================================

key_map = {
    "w": ecodes.KEY_W,
    "a": ecodes.KEY_A,
    "s": ecodes.KEY_S,
    "d": ecodes.KEY_D,
    "space": ecodes.KEY_SPACE
}

keyboard = UInput(
    {ecodes.EV_KEY: list(key_map.values())},
    name="UniFit Virtual Keyboard"
)

current_key = None


def release_all():
    global current_key

    for key_code in key_map.values():
        keyboard.write(ecodes.EV_KEY, key_code, 0)

    keyboard.syn()
    current_key = None


def joystick_to_wasd(x, y):
    if abs(x) <= DEADZONE and abs(y) <= DEADZONE:
        return NEUTRAL_COMMAND

    if abs(x) > abs(y):
        return "a" if x < 0 else "d"

    return "w" if y < 0 else "s"


def handle_joystick(x, y):
    global current_key

    new_key = joystick_to_wasd(x, y)

    if new_key == current_key:
        return

    if current_key is not None:
        keyboard.write(
            ecodes.EV_KEY,
            key_map[current_key],
            0
        )
        print(f"[KEY] Released {current_key.upper()}")

    current_key = new_key

    if current_key is not None:
        keyboard.write(
            ecodes.EV_KEY,
            key_map[current_key],
            1
        )
        print(f"[KEY] Pressed {current_key.upper()}")
    keyboard.syn()


def handle_button(button):
    if button:
        keyboard.write(ecodes.EV_KEY, ecodes.KEY_SPACE, 1)
    else:
        keyboard.write(ecodes.EV_KEY, ecodes.KEY_SPACE, 0)

    keyboard.syn()


# ==========================================
# BLE CALLBACK
# ==========================================

def joystick_notification(sender, data):
    if len(data) != 3:
        return

    x = data[0] if data[0] < 128 else data[0] - 256
    y = data[1] if data[1] < 128 else data[1] - 256

    button = data[2]

    print(f"[JOY] X={x}, Y={y}, Button={button}")

    handle_joystick(x, y)
    handle_button(button)


# ==========================================
# BLE CONNECTION
# ==========================================

async def run_controller():
    while True:
        try:
            print(f"[BLE] Searching for {BLE_NAME}...")

            device = await BleakScanner.find_device_by_name(
                BLE_NAME,
                timeout=10.0
            )

            if device is None:
                print("[BLE] Controller not found.")
                await asyncio.sleep(3)
                continue

            print(f"[BLE] Found {device.address}")

            async with BleakClient(device) as client:
                print("[BLE] Connected!")

                await client.start_notify(
                    UUID_JOYSTICK,
                    joystick_notification
                )

                print("[SYSTEM] Controller ready.")

                while client.is_connected:
                    await asyncio.sleep(0.5)

        except asyncio.CancelledError:
            raise

        except Exception as error:
            print(f"[ERROR] {error}")

        finally:
            release_all()
            print("[BLE] Disconnected. All keys released.")

        await asyncio.sleep(3)


# ==========================================
# MAIN
# ==========================================

async def main():
    loop = asyncio.get_running_loop()
    stop_event = asyncio.Event()

    for sig in (signal.SIGINT, signal.SIGTERM):
        loop.add_signal_handler(sig, stop_event.set)

    controller_task = asyncio.create_task(run_controller())

    try:
        await stop_event.wait()

    finally:
        controller_task.cancel()

        try:
            await controller_task
        except asyncio.CancelledError:
            pass

        release_all()
        print("[SYSTEM] Controller stopped.")


if __name__ == "__main__":
    try:
        asyncio.run(main())
    finally:
        release_all()
        keyboard.close()
