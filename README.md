# uNiFC

```
  ┌─────────────────────────────────────────┐
  │                                         │
  │   ██╗   ██╗███╗   ██╗██╗███████╗ ██████╗│
  │   ██║   ██║████╗  ██║██║██╔════╝██╔════╝│
  │   ██║   ██║██╔██╗ ██║██║█████╗  ██║     │
  │   ██║   ██║██║╚██╗██║██║██╔══╝  ██║     │
  │   ╚██████╔╝██║ ╚████║██║██║     ╚██████╗│
  │    ╚═════╝ ╚═╝  ╚═══╝╚═╝╚═╝      ╚═════╝│
  │                                         │
  │     Ultra-low-power NFC check-in        │
  │     ESP32  ·  PN532  ·  deep sleep      │
  └─────────────────────────────────────────┘
```

**Tap. Wake. Blink. Sleep.**  
Hardware check-in die wakker wordt bij een NFC-tag, de UID leest, feedback geeft, en meteen weer slapen gaat — klaar voor de volgende tap.

---

## Waarom dit bestaat

De meeste NFC-readers blijven permanent actief en slurpen stroom.  
**uNiFC** doet het anders: de ESP32 ligt in deep sleep tot de PN532 een kaart ziet. Dan: IRQ → wake → UID → LED → terug slapen.

Perfect voor Unifit check-in hardware die lang op batterij moet blijven werken.

---

## Features

| | |
|---|---|
| **IRQ wake** | Wordt wakker op `EXT0` zodra de PN532 een tag detecteert |
| **Deep sleep** | Bijna geen verbruik tussen taps; RST blijft vastgehouden |
| **Watchdog** | Timer-wake elke 10 minuten als safety net |
| **LED feedback** | Groen bij succesvolle UID · rood bij wake zonder tag |
| **Debounce** | Wacht tot de tag weg is, voorkomt spam-reads |
| **RTC memory** | Onthoudt laatste UID over sleep heen |

---

## Hardware

| Onderdeel | Rol |
|-----------|-----|
| **ESP32** (DOIT DevKit v1) | Brain + deep sleep |
| **Adafruit / Elechouse PN532** | NFC reader (I2C) |
| **Groene LED** + weerstand | Succes-feedback |
| **Rode LED** + weerstand | Missed-read feedback |

### Pinout

```
ESP32                          PN532
─────                          ─────
GPIO 21  ─────────────────────  SDA
GPIO 22  ─────────────────────  SCL
GPIO 4   ─────────────────────  IRQ   ← wake source
GPIO 27  ─────────────────────  RST   ← held HIGH in sleep
3V3 / GND ───────────────────── VCC / GND

ESP32                          LEDs
─────                          ────
GPIO 25  ──[R]──►|── GND        GREEN  (card found)
GPIO 26  ──[R]──►|── GND        RED    (IRQ, geen UID)
```

> PN532 in **I2C-modus** zetten (DIP-switches volgens jouw module).

---

## Hoe het werkt

```
         ┌──────────────┐
         │  deep sleep  │◄──────────────────────────┐
         └──────┬───────┘                           │
                │ IRQ low / timer                   │
                ▼                                   │
         ┌──────────────┐                           │
         │   wake up    │                           │
         └──────┬───────┘                           │
                ▼                                   │
         ┌──────────────┐     fail                  │
         │  read UID    │──────────► rood LED       │
         └──────┬───────┘                           │
                │ ok                                │
                ▼                                   │
         ┌──────────────┐                           │
         │  groen LED   │                           │
         │  print UID   │                           │
         └──────┬───────┘                           │
                ▼                                   │
         ┌──────────────┐                           │
         │ wait tag gone│                           │
         └──────┬───────┘                           │
                ▼                                   │
         ┌──────────────┐                           │
         │ arm IRQ listen│──────────────────────────┘
         └──────────────┘
```

1. **Cold boot / wake** — GPIO holds loslaten, LEDs klaarzetten, PN532 fris resetten  
2. **Read** — tot 2,5 s pollen voor een ISO14443A UID  
3. **Feedback** — nieuw tag → groen + serial dump; IRQ zonder UID → rood  
4. **Cooldown** — wachten tot de kaart uit het veld is  
5. **Arm** — `startPassiveTargetIDDetection`, IRQ idle HIGH  
6. **Sleep** — EXT0 op IRQ + 600 s watchdog, RST held HIGH  

---

## Build & flash

PlatformIO project — openen, builden, flashen.

```bash
# dependencies komen uit platformio.ini
pio run -t upload

# serial monitor (115200)
pio device monitor
```

Of gewoon de PlatformIO-knoppen in VS Code / Cursor.

**Board:** `esp32doit-devkit-v1`  
**Framework:** Arduino  
**Lib:** `adafruit/Adafruit PN532`

---

## Serial output

Bij een geslaagde tap zie je zoiets:

```
Wake #12: card (IRQ)

--- Card Found! ---
UID Length: 4 bytes
UID Value:  A1 B2 C3 D4
Deep sleep — wake on card.
```

---

## Tunables

Alles zit bovenaan `src/main.cpp`:

| Define | Default | Betekenis |
|--------|---------|-----------|
| `NFC_WATCHDOG_SEC` | `600` | Timer-wake als safety (10 min) |
| `NFC_READ_TIMEOUT_MS` | `200` | Timeout per poll |
| `NFC_POST_WAKE_POLL_MS` | `2500` | Max tijd om UID te vangen na wake |
| `NFC_WAIT_TAG_GONE_MS` | `10000` | Max wachten tot tag weg is |
| `NFC_ARM_RETRIES` | `3` | Pogingen om IRQ clean te armen |

---

## Tips

- Houd de tag even stil boven de antenne — te kort = IRQ wake zonder UID (rode LED)
- Check dat de PN532 écht op I2C staat, niet SPI
- IRQ moet naar een RTC-capable pin (GPIO 4 is dat)
- Geen permanente `loop()` — alles draait in `setup()`, daarna deep sleep

---

## License

Doe ermee wat je wilt — tap happy.
