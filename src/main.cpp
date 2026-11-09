#include <Arduino.h>
#include <Wire.h>
#include <Adafruit_PN532.h>
#include <driver/rtc_io.h>
#include <esp_sleep.h>

#define PN532_SDA  (21)
#define PN532_SCL  (22)
#define PN532_IRQ  (4)
#define PN532_RST  (27)

#define NFC_WATCHDOG_SEC      600
#define NFC_READ_TIMEOUT_MS   200
#define NFC_POST_WAKE_POLL_MS 2500
#define NFC_WAIT_TAG_GONE_MS  10000
#define NFC_ARM_RETRIES       3

#define LEDPIN_GREEN 25
#define LEDPIN_RED   26

Adafruit_PN532 nfc(PN532_IRQ, PN532_RST);

RTC_DATA_ATTR static uint32_t lastUidKey = 0;
RTC_DATA_ATTR static uint32_t wakeCount = 0;

static void releaseGpioHolds() {
  gpio_deep_sleep_hold_dis();
  gpio_hold_dis((gpio_num_t)PN532_RST);
  if (rtc_gpio_is_valid_gpio((gpio_num_t)PN532_IRQ)) {
    rtc_gpio_deinit((gpio_num_t)PN532_IRQ);
  }
}

static void pn532HardwareReset() {
  pinMode(PN532_RST, OUTPUT);
  digitalWrite(PN532_RST, HIGH);
  delay(5);
  digitalWrite(PN532_RST, LOW);
  delay(10);
  digitalWrite(PN532_RST, HIGH);
  delay(50);
}

static void pn532ResetHoldHigh() {
  pinMode(PN532_RST, OUTPUT);
  digitalWrite(PN532_RST, HIGH);
}

static void prepareIrqPin() {
  pinMode(PN532_IRQ, INPUT_PULLUP);
}

static void prepareLeds() {
  pinMode(LEDPIN_GREEN, OUTPUT);
  pinMode(LEDPIN_RED, OUTPUT);
  digitalWrite(LEDPIN_GREEN, LOW);
  digitalWrite(LEDPIN_RED, LOW);
}

static bool irqIsLow() {
  return digitalRead(PN532_IRQ) == LOW;
}

static void printUid(const uint8_t *uid, uint8_t uidLength) {
  digitalWrite(LEDPIN_GREEN, HIGH);
  Serial.println("\n--- Card Found! ---");
  Serial.print("UID Length: ");
  Serial.print(uidLength, DEC);
  Serial.println(" bytes");
  Serial.print("UID Value: ");
  for (uint8_t i = 0; i < uidLength; i++) {
    if (uid[i] < 0x10) {
      Serial.print(" 0");
    } else {
      Serial.print(" ");
    }
    Serial.print(uid[i], HEX);
  }
  Serial.println();
  delay(400);
  digitalWrite(LEDPIN_GREEN, LOW);
}

static uint32_t uidToKey(const uint8_t *uid, uint8_t uidLength) {
  uint32_t key = 0;
  for (uint8_t i = 0; i < uidLength && i < 4; i++) {
    key = (key << 8) | uid[i];
  }
  return key;
}

static void notifyIfNewTag(const uint8_t *uid, uint8_t uidLength) {
  const uint32_t key = uidToKey(uid, uidLength);
  if (key == lastUidKey) {
    return;
  }
  printUid(uid, uidLength);
  lastUidKey = key;
}

/** Full clean init — call before every read session and before every arm. */
static bool initNfcFresh() {
  prepareIrqPin();
  pn532HardwareReset();
  if (!nfc.begin()) {
    return false;
  }
  if (!nfc.getFirmwareVersion()) {
    return false;
  }
  return nfc.SAMConfig();
}

static bool readTagOnce(uint8_t *uid, uint8_t *uidLength) {
  return nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, uidLength,
                                 NFC_READ_TIMEOUT_MS);
}

static bool readTagAfterWake(uint8_t *uid, uint8_t *uidLength) {
  const uint32_t t0 = millis();
  while ((millis() - t0) < NFC_POST_WAKE_POLL_MS) {
    if (readTagOnce(uid, uidLength)) {
      return true;
    }
  }
  return false;
}

/** Block until no card is in the field (or timeout). */
static void waitForTagGone() {
  const uint32_t t0 = millis();
  while ((millis() - t0) < NFC_WAIT_TAG_GONE_MS) {
    uint8_t uid[7];
    uint8_t uidLength = 0;
    if (!readTagOnce(uid, &uidLength)) {
      lastUidKey = 0;
      delay(100);
      // double-check
      if (!readTagOnce(uid, &uidLength)) {
        return;
      }
    }
    delay(50);
  }
  Serial.println("Tag still in field after wait.");
}

/**
 * Reset + SAMConfig + start detection. Retry until IRQ is idle HIGH so EXT0
 * will not fire immediately.
 */
static bool armIrqListenerClean() {
  for (int attempt = 1; attempt <= NFC_ARM_RETRIES; attempt++) {
    if (!initNfcFresh()) {
      Serial.printf("Arm init failed (try %d)\n", attempt);
      continue;
    }

    (void)nfc.startPassiveTargetIDDetection(PN532_MIFARE_ISO14443A);
    delay(30);

    const uint32_t t0 = millis();
    while (irqIsLow() && (millis() - t0) < 800) {
      delay(10);
    }

    if (!irqIsLow()) {
      Serial.printf("Armed OK (IRQ high), try %d\n", attempt);
      return true;
    }

    Serial.printf("Arm try %d: IRQ still low\n", attempt);
  }
  return false;
}

static void configureIrqRtcPullupForSleep() {
  rtc_gpio_init((gpio_num_t)PN532_IRQ);
  rtc_gpio_set_direction((gpio_num_t)PN532_IRQ, RTC_GPIO_MODE_INPUT_ONLY);
  rtc_gpio_pullup_en((gpio_num_t)PN532_IRQ);
  rtc_gpio_pulldown_dis((gpio_num_t)PN532_IRQ);
}

static void holdRstAndDeepSleep() {
  pn532ResetHoldHigh();
  gpio_hold_en((gpio_num_t)PN532_RST);
  gpio_deep_sleep_hold_en();
  esp_deep_sleep_start();
}

static void enterDeepSleepForCard() {
  Serial.println("Deep sleep — wake on card.");
  Serial.flush();

  configureIrqRtcPullupForSleep();
  esp_sleep_disable_wakeup_source(ESP_SLEEP_WAKEUP_ALL);
  esp_sleep_enable_ext0_wakeup((gpio_num_t)PN532_IRQ, 0);
  esp_sleep_enable_timer_wakeup((uint64_t)NFC_WATCHDOG_SEC * 1000000ULL);
  holdRstAndDeepSleep();
}

static void enterTimerOnlySleep(uint32_t sec) {
  Serial.printf("Timer sleep %us (no IRQ wake).\n", (unsigned)sec);
  Serial.flush();
  esp_sleep_disable_wakeup_source(ESP_SLEEP_WAKEUP_ALL);
  esp_sleep_enable_timer_wakeup((uint64_t)sec * 1000000ULL);
  holdRstAndDeepSleep();
}

static const char *wakeName(esp_sleep_wakeup_cause_t cause) {
  switch (cause) {
  case ESP_SLEEP_WAKEUP_EXT0:
    return "card (IRQ)";
  case ESP_SLEEP_WAKEUP_TIMER:
    return "timer";
  default:
    return "power-on / reset";
  }
}

void setup(void) {
  releaseGpioHolds();
  wakeCount++;

  const esp_sleep_wakeup_cause_t cause = esp_sleep_get_wakeup_cause();
  const bool coldBoot = (cause == ESP_SLEEP_WAKEUP_UNDEFINED);

  prepareLeds();

  Serial.begin(115200);
  delay(coldBoot ? 300 : 40);

  Wire.begin(PN532_SDA, PN532_SCL);

  Serial.printf("Wake #%lu: %s\n", (unsigned long)wakeCount, wakeName(cause));

  if (!initNfcFresh()) {
    Serial.println("PN532 init failed — timer retry.");
    enterTimerOnlySleep(3);
  }

  if (coldBoot) {
    const uint32_t ver = nfc.getFirmwareVersion();
    Serial.print("PN532 ");
    Serial.print((ver >> 16) & 0xFF, DEC);
    Serial.print('.');
    Serial.print((ver >> 8) & 0xFF, DEC);
    Serial.println(" — deep sleep + IRQ");
  }

  uint8_t uid[7] = {0};
  uint8_t uidLength = 0;

  // Always try to read after any wake (IRQ, timer, or cold boot with card)
  if (readTagAfterWake(uid, &uidLength)) {
    notifyIfNewTag(uid, uidLength);
  } else if (cause == ESP_SLEEP_WAKEUP_EXT0) {
    digitalWrite(LEDPIN_RED, HIGH);
    delay(400);
    digitalWrite(LEDPIN_RED, LOW);
    Serial.println("IRQ wake, no UID — hold card longer.");
  }

  waitForTagGone();

  if (!armIrqListenerClean()) {
    // Do not EXT0-sleep with IRQ stuck low (instant wake loop / dead listen)
    Serial.println("Could not arm IRQ listener.");
    enterTimerOnlySleep(3);
  }

  enterDeepSleepForCard();
}

void loop(void) {}
