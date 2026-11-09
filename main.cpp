#include <Arduino.h>
#include <SPI.h>
#include <Adafruit_PN532.h>

// Pin definitions matching your wiring table
#define PN532_SCK  (18)
#define PN532_MISO (19)
#define PN532_MOSI (23)
#define PN532_SS   (5)

// Define optional pins if wired (set to -1 if unconnected)
#define PN532_IRQ  (-1)
#define PN532_RST  (2) // PN532 RST pin; use -1 if RST is tied to 3.3V on the module

// Software SPI: uses the pins below directly (Adafruit recommended when not on
// default bus pins). For VSPI defaults (18/19/23) you may use Adafruit_PN532 nfc(PN532_SS)
// after SPI.begin(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS).
Adafruit_PN532 nfc(PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS);

static void pn532HardwareReset() {
  if (PN532_RST < 0) {
    return;
  }
  pinMode(PN532_RST, OUTPUT);
  digitalWrite(PN532_RST, HIGH);
  delay(10);
  digitalWrite(PN532_RST, LOW);
  delay(1);
  digitalWrite(PN532_RST, HIGH);
  delay(50);
}

void setup(void) {
  Serial.begin(115200);
  while (!Serial) delay(10);

  Serial.println("Initializing PN532 via SPI on ESP32...");
  Serial.printf("Pins: SCK=%d MISO=%d MOSI=%d SS=%d RST=%d\n",
                PN532_SCK, PN532_MISO, PN532_MOSI, PN532_SS, PN532_RST);

  pn532HardwareReset();

  if (!nfc.begin()) {
    Serial.println("PN532 begin() failed.");
    while (1) delay(100);
  }

  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata) {
    Serial.println("Didn't find PN53x board!");
    Serial.println("Hardware:");
    Serial.println("  3.3V only, common GND, MISO/MOSI not swapped.");
    Serial.println("  ESP SCK/MISO/MOSI/SS -> module SCK/SDO/SDI/NSS (or MISO/MOSI labels).");
    Serial.println("DIP (typical red PN532 clone): SPI = SEL0 ON, SEL1 OFF");
    Serial.println("  (SEL0 OFF + SEL1 ON is I2C — will not answer on SPI.)");
    Serial.println("If RST is not on GPIO2, set PN532_RST to -1 and reflash.");
    while (1) delay(100);
  }

  // Print board details if detected
  Serial.print("Found chip PN5"); 
  Serial.println((versiondata >> 24) & 0xFF, HEX);
  Serial.print("Firmware ver. "); 
  Serial.print((versiondata >> 16) & 0xFF, DEC);
  Serial.print('.'); 
  Serial.println((versiondata >> 8) & 0xFF, DEC);

  // Configure board to read RFID tags
  nfc.SAMConfig();

  Serial.println("Waiting for an ISO14443A Card ...");
}

void loop(void) {
  uint8_t success;
  uint8_t uid[] = { 0, 0, 0, 0, 0, 0, 0 };  
  uint8_t uidLength;                       

  // Wait for tag (1000 ms timeout)
  success = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 1000);

  if (success) {
    Serial.println("\n--- Card Found! ---");
    Serial.print("UID Length: ");
    Serial.print(uidLength, DEC);
    Serial.println(" bytes");
    Serial.print("UID Value: ");
    
    for (uint8_t i = 0; i < uidLength; i++) {
      if (uid[i] < 0x10) Serial.print(" 0");
      else Serial.print(" ");
      Serial.print(uid[i], HEX);
    }
    Serial.println();

    delay(1000); // Cooldown to prevent spamming
  } else {
    Serial.print(".");
  }
}