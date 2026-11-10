#include "azure_iot.h"
#include "config.h"

#include <Arduino.h>
#include <ArduinoJson.h>
#include <ArduinoMqttClient.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <az_core.h>
#include <az_iot.h>
#include <azure_ca.h>
#include <mbedtls/base64.h>
#include <mbedtls/md.h>
#include <string.h>
#include <time.h>

#define AZURE_SDK_CLIENT_USER_AGENT "c%2F" AZ_SDK_VERSION_STRING "(ard;esp32;unifc)"
#define SAS_TOKEN_DURATION_MINUTES  60
#define MQTT_PORT                   AZ_IOT_DEFAULT_MQTT_CONNECT_PORT
#define NTP_SERVERS                 "pool.ntp.org", "time.nist.gov"
#define UNIX_TIME_NOV_13_2017       1510592825

static WiFiClientSecure wifiClient;
static MqttClient mqttClient(wifiClient);
static az_iot_hub_client hubClient;

static char mqttClientId[128];
static char mqttUsername[128];
static char mqttPassword[256];
static uint8_t sasSignatureBuffer[256];
static char telemetryTopic[128];
static bool hubClientReady = false;
static uint32_t sasExpirationUnix = 0;

static bool connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  Serial.printf("Wi-Fi connecting to %s ...\n", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  const uint32_t t0 = millis();
  while (WiFi.status() != WL_CONNECTED) {
    if ((millis() - t0) > 20000) {
      Serial.println("Wi-Fi connect timeout");
      return false;
    }
    delay(250);
  }

  Serial.print("Wi-Fi OK: ");
  Serial.println(WiFi.localIP());
  return true;
}

static bool syncTime() {
  time_t now = time(nullptr);
  if (now >= UNIX_TIME_NOV_13_2017) {
    return true;
  }

  Serial.println("Syncing time via NTP ...");
  configTime(0, 0, NTP_SERVERS);

  const uint32_t t0 = millis();
  while ((now = time(nullptr)) < UNIX_TIME_NOV_13_2017) {
    if ((millis() - t0) > 15000) {
      Serial.println("NTP sync timeout");
      return false;
    }
    delay(250);
  }

  Serial.printf("Time OK: %ld\n", (long)now);
  return true;
}

static bool initHubClient() {
  if (hubClientReady) {
    return true;
  }

  az_iot_hub_client_options options = az_iot_hub_client_options_default();
  options.user_agent = AZ_SPAN_FROM_STR(AZURE_SDK_CLIENT_USER_AGENT);

  if (az_result_failed(az_iot_hub_client_init(
          &hubClient,
          az_span_create_from_str((char *)IOT_CONFIG_IOTHUB_FQDN),
          az_span_create_from_str((char *)IOT_CONFIG_DEVICE_ID),
          &options))) {
    Serial.println("az_iot_hub_client_init failed");
    return false;
  }

  size_t clientIdLen = 0;
  if (az_result_failed(az_iot_hub_client_get_client_id(
          &hubClient, mqttClientId, sizeof(mqttClientId) - 1, &clientIdLen))) {
    Serial.println("get_client_id failed");
    return false;
  }
  mqttClientId[clientIdLen] = '\0';

  if (az_result_failed(az_iot_hub_client_get_user_name(
          &hubClient, mqttUsername, sizeof(mqttUsername), nullptr))) {
    Serial.println("get_user_name failed");
    return false;
  }

  hubClientReady = true;
  return true;
}

static bool decodeBase64(az_span encoded, az_span decoded, az_span *outDecoded) {
  size_t len = 0;
  if (mbedtls_base64_decode(az_span_ptr(decoded), (size_t)az_span_size(decoded), &len,
                            az_span_ptr(encoded), (size_t)az_span_size(encoded)) != 0) {
    return false;
  }
  *outDecoded = az_span_create(az_span_ptr(decoded), (int32_t)len);
  return true;
}

static bool encodeBase64(az_span decoded, az_span encoded, az_span *outEncoded) {
  size_t len = 0;
  if (mbedtls_base64_encode(az_span_ptr(encoded), (size_t)az_span_size(encoded), &len,
                            az_span_ptr(decoded), (size_t)az_span_size(decoded)) != 0) {
    return false;
  }
  *outEncoded = az_span_create(az_span_ptr(encoded), (int32_t)len);
  return true;
}

static bool generateSasToken(unsigned int expiryMinutes) {
  const uint64_t expiry = (uint64_t)time(nullptr) + (uint64_t)expiryMinutes * 60ULL;

  az_span signature = AZ_SPAN_FROM_BUFFER(sasSignatureBuffer);
  if (az_result_failed(az_iot_hub_client_sas_get_signature(&hubClient, expiry, signature,
                                                           &signature))) {
    Serial.println("sas_get_signature failed");
    return false;
  }

  uint8_t decodedKeyBuf[64];
  az_span decodedKey = AZ_SPAN_FROM_BUFFER(decodedKeyBuf);
  if (!decodeBase64(az_span_create_from_str((char *)IOT_CONFIG_DEVICE_KEY), decodedKey,
                    &decodedKey)) {
    Serial.println("device key base64 decode failed");
    return false;
  }

  uint8_t hmacBuf[32];
  az_span hmac = AZ_SPAN_FROM_BUFFER(hmacBuf);
  mbedtls_md_context_t ctx;
  mbedtls_md_init(&ctx);
  mbedtls_md_setup(&ctx, mbedtls_md_info_from_type(MBEDTLS_MD_SHA256), 1);
  mbedtls_md_hmac_starts(&ctx, az_span_ptr(decodedKey), (size_t)az_span_size(decodedKey));
  mbedtls_md_hmac_update(&ctx, az_span_ptr(signature), (size_t)az_span_size(signature));
  mbedtls_md_hmac_finish(&ctx, az_span_ptr(hmac));
  mbedtls_md_free(&ctx);

  char b64SigBuf[64];
  az_span b64Sig = AZ_SPAN_FROM_BUFFER(b64SigBuf);
  if (!encodeBase64(hmac, b64Sig, &b64Sig)) {
    Serial.println("signature base64 encode failed");
    return false;
  }

  size_t passwordLen = 0;
  if (az_result_failed(az_iot_hub_client_sas_get_password(
          &hubClient, expiry, b64Sig, AZ_SPAN_EMPTY, mqttPassword, sizeof(mqttPassword),
          &passwordLen))) {
    Serial.println("sas_get_password failed");
    return false;
  }

  sasExpirationUnix = (uint32_t)expiry;
  return true;
}

static bool sasExpired() {
  const time_t now = time(nullptr);
  return (now == (time_t)-1) || (now >= (time_t)sasExpirationUnix);
}

static bool connectMqtt() {
  if (mqttClient.connected() && !sasExpired()) {
    return true;
  }

  if (mqttClient.connected()) {
    mqttClient.stop();
  }

  if (!generateSasToken(SAS_TOKEN_DURATION_MINUTES)) {
    return false;
  }

  wifiClient.setCACert((const char *)ca_pem);
  mqttClient.setId(mqttClientId);
  mqttClient.setUsernamePassword(mqttUsername, mqttPassword);
  mqttClient.setKeepAliveInterval(30 * 1000);

  Serial.printf("MQTT connecting to %s:%d ...\n", IOT_CONFIG_IOTHUB_FQDN, MQTT_PORT);
  if (!mqttClient.connect(IOT_CONFIG_IOTHUB_FQDN, MQTT_PORT)) {
    Serial.printf("MQTT connect failed, error=%d\n", mqttClient.connectError());
    return false;
  }

  Serial.println("Azure IoT Hub MQTT connected");
  return true;
}

bool azureEnsureConnected() {
  if (!connectWifi()) {
    return false;
  }
  if (!syncTime()) {
    return false;
  }
  if (!initHubClient()) {
    return false;
  }
  return connectMqtt();
}

static void formatUidHex(const uint8_t *uid, uint8_t uidLength, char *out, size_t outSize) {
  size_t pos = 0;
  for (uint8_t i = 0; i < uidLength && (pos + 2) < outSize; i++) {
    pos += (size_t)snprintf(out + pos, outSize - pos, "%02X", uid[i]);
  }
  if (outSize > 0) {
    out[pos < outSize ? pos : outSize - 1] = '\0';
  }
}

bool azureSendCardUid(const uint8_t *uid, uint8_t uidLength) {
  if (!azureEnsureConnected()) {
    Serial.println("Azure connect failed — skip send");
    return false;
  }

  if (az_result_failed(az_iot_hub_client_telemetry_get_publish_topic(
          &hubClient, nullptr, telemetryTopic, sizeof(telemetryTopic), nullptr))) {
    Serial.println("telemetry topic failed");
    return false;
  }

  char uidHex[32];
  formatUidHex(uid, uidLength, uidHex, sizeof(uidHex));

  const time_t now = time(nullptr);
  char isoTime[32];
  struct tm tmUtc;
  gmtime_r(&now, &tmUtc);
  strftime(isoTime, sizeof(isoTime), "%Y-%m-%dT%H:%M:%SZ", &tmUtc);

  JsonDocument doc;
  doc["uid"] = uidHex;
  doc["timestamp"] = isoTime;
  doc["epoch"] = (long)now;
  doc["deviceId"] = IOT_CONFIG_DEVICE_ID;

  char payload[192];
  const size_t n = serializeJson(doc, payload, sizeof(payload));
  if (n == 0 || n >= sizeof(payload)) {
    Serial.println("JSON serialize failed");
    return false;
  }

  Serial.printf("Sending telemetry: %s\n", payload);

  if (!mqttClient.beginMessage(telemetryTopic, false, 1)) {
    Serial.println("beginMessage failed");
    return false;
  }
  mqttClient.print(payload);
  if (!mqttClient.endMessage()) {
    Serial.println("endMessage failed");
    return false;
  }

  mqttClient.poll();
  Serial.println("Telemetry sent");
  return true;
}

void azureDisconnect() {
  if (mqttClient.connected()) {
    mqttClient.stop();
  }
  WiFi.disconnect(true);
  WiFi.mode(WIFI_OFF);
  Serial.println("Azure / Wi-Fi disconnected");
}
