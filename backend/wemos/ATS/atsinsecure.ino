#include <WebSocketsClient.h>
#include <ArduinoJson.h>
#include <WiFiClientSecure.h>

// ============================================================================
// CONFIGURATION
// ============================================================================
const char* WIFI_SSID   = "Shimo's A05";
const char* WIFI_PASS   = "cn97nzjf9yr9ksrk";
const char* DEVICE_ID   = "1";
const char* USERNAME    = "wemos_user";
const char* PASSWORD    = "wemos_pass";
const char* SERVER_HOST = "combined-server-1fyr.onrender.com";
const uint16_t SERVER_PORT = 443;

// ============================================================================
// CONSTANTS & PINS
// ============================================================================
const uint8_t PIN_D6 = D6;
const uint8_t PIN_D5 = D5;

const uint32_t WS_PING_INTERVAL   = 15000;   // 15 seconds
const uint32_t HTTP_PING_INTERVAL = 120000;  // 2 minutes
const uint32_t AUTO_ON_DURATION   = 5000;    // 5 seconds

// ============================================================================
// GLOBAL STATE
// ============================================================================
WebSocketsClient ws;
WiFiClientSecure secureClient;

struct {
  uint32_t lastWsPing;
  uint32_t lastHttpPing;
  bool isFailsafeActive;
} state = {0, 0, false};

// ============================================================================
// PIN CONTROL
// ============================================================================
void setPinMode(uint8_t d6State, uint8_t d5State) {
  digitalWrite(PIN_D6, d6State);
  digitalWrite(PIN_D5, d5State);
}

void activateFailsafe() {
  if (!state.isFailsafeActive) {
    setPinMode(HIGH, LOW);
    state.isFailsafeActive = true;
    Serial.println(F("FAILSAFE: HARD ON"));
  }
}

void deactivateFailsafe() {
  if (state.isFailsafeActive) {
    setPinMode(HIGH, HIGH);
    state.isFailsafeActive = false;
    Serial.println(F("Failsafe Cleared"));
  }
}

// ============================================================================
// NETWORK HELPERS
// ============================================================================
void sendHealthPing() {
  secureClient.setInsecure();
  if (secureClient.connect(SERVER_HOST, SERVER_PORT)) {
    secureClient.print(F("GET /health HTTP/1.1\r\nHost: "));
    secureClient.print(SERVER_HOST);
    secureClient.print(F("\r\nConnection: close\r\n\r\n"));
    secureClient.stop();
    Serial.println(F("Health Ping Sent"));
  }
}

// ============================================================================
// WEBSOCKET HANDLER
// ============================================================================
void onWsEvent(WStype_t type, uint8_t* payload, size_t length) {
  switch (type) {
    case WStype_CONNECTED: {
      Serial.println(F("WS Connected"));
      deactivateFailsafe();

      StaticJsonDocument<128> authDoc;
      authDoc["type"]     = "auth";
      authDoc["username"] = USERNAME;
      authDoc["password"] = PASSWORD;

      String authMsg;
      serializeJson(authDoc, authMsg);
      ws.sendTXT(authMsg);
      break;
    }

    case WStype_TEXT: {
      StaticJsonDocument<256> doc;
      if (deserializeJson(doc, payload, length)) return;

      const char* msgType = doc["type"] | "";
      if (strcmp(msgType, "command") == 0) {
        const char* action = doc["payload"]["action"] | "";
        Serial.printf("Action: %s\n", action);

        if (strcmp(action, "HARD_ON") == 0) {
          setPinMode(HIGH, LOW);
          ws.sendTXT("{\"type\":\"status\",\"pin\":\"D6\",\"state\":\"LOW\"}");
        } 
        else if (strcmp(action, "AUTO_ON") == 0) {
          setPinMode(LOW, HIGH);
          delay(AUTO_ON_DURATION);
          setPinMode(HIGH, HIGH);
          ws.sendTXT("{\"type\":\"status\",\"pin\":\"D5\",\"state\":\"LOW\"}");
        } 
        else if (strcmp(action, "HARD_OFF") == 0 || strcmp(action, "AUTO_OFF") == 0) {
          setPinMode(HIGH, HIGH);
          ws.sendTXT("{\"type\":\"status\",\"gpio\":\"ALL_HIGH\"}");
        }
      }
      break;
    }

    case WStype_DISCONNECTED:
      Serial.println(F("WS Disconnected"));
      activateFailsafe();
      break;
  }
}

// ============================================================================
// ARDUINO CORE
// ============================================================================
void setup() {
  Serial.begin(115200);

  pinMode(PIN_D6, OUTPUT);
  pinMode(PIN_D5, OUTPUT);
  setPinMode(HIGH, HIGH);

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(F("\nWiFi OK"));

  char path[64];
  snprintf(path, sizeof(path), "/ws/device?deviceId=%s", DEVICE_ID);

  ws.beginSSL(SERVER_HOST, SERVER_PORT, path);
  ws.onEvent(onWsEvent);
  ws.setReconnectInterval(5000);
}

void loop() {
  ws.loop();
  uint32_t now = millis();

  // WebSocket ping
  if (ws.isConnected() && (now - state.lastWsPing >= WS_PING_INTERVAL)) {
    ws.sendTXT("{\"type\":\"ping\"}");
    state.lastWsPing = now;
  }

  // HTTP health ping
  if (now - state.lastHttpPing >= HTTP_PING_INTERVAL) {
    sendHealthPing();
    state.lastHttpPing = now;
  }

  yield(); // Let the system breathe
}
