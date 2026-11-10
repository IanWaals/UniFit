#pragma once

#include <stdint.h>

/** Connect Wi-Fi, sync NTP, and open MQTT to Azure IoT Hub. */
bool azureEnsureConnected();

/** Publish a check-in with card UID + UTC timestamp. */
bool azureSendCardUid(const uint8_t *uid, uint8_t uidLength);

/** Drop MQTT + Wi-Fi before deep sleep. */
void azureDisconnect();
