#include <ESP8266WiFi.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>

// --- WiFi credentials ---
const char* ssid = "NETWORK";
const char* pass = "trishello";

// --- Device identity ---
const char* deviceId = "1";
const char* username = "wemos_user";
const char* password = "wemos_pass";

// --- Server config ---
const char* host = "iot-gateway-89zp.onrender.com";
const int port = 443;
String path = "/ws/device?deviceId=" + String(deviceId);

// --- TLS fingerprint (SHA1 of leaf cert) ---
const uint8_t fingerprint[20] = {
  0x43, 0x9F, 0x7D, 0x88, 0xE0,
  0x8F, 0xAD, 0x9E, 0xA2, 0xED,
  0x1A, 0x00, 0x48, 0x45, 0xBE,
  0x46, 0xDE, 0xF8, 0xE2, 0x4A
};

WebSocketsClient ws;

// --- Pin assignments ---
const int pinD6 = D6;
const int pinD5 = D5;

// --- Reconnect control ---
unsigned long lastAttempt = 0;
bool waitingForServer = false;

// --- Helpers ---
void forceHardOn() {
  digitalWrite(pinD6, HIGH);
  digitalWrite(pinD5, LOW);
  Serial.println("Failsafe: HARD ON triggered due to disconnect");
}

void exitFailsafe() {
  digitalWrite(pinD6, HIGH);
  digitalWrite(pinD5, HIGH);
  Serial.println("Exited failsafe: pins reset to OFF");
}

void reconnectWiFi() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Reconnecting WiFi...");
    WiFi.disconnect();
    WiFi.begin(ssid, pass);
  }
}

void reconnectWebSocket() {
  if (!ws.isConnected() && WiFi.status() == WL_CONNECTED) {
    unsigned long now = millis();
    if (!waitingForServer || (now - lastAttempt > 120000)) {
      Serial.println("Attempting WebSocket connection...");
      ws.beginSSL(host, port, path.c_str(), fingerprint);
      ws.onEvent(onWsEvent);
      ws.setReconnectInterval(0); // disable auto-reconnect
      lastAttempt = now;
      waitingForServer = true;
    }
  }
}

// --- WebSocket event handler ---
void onWsEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch (type) {
    case WStype_CONNECTED:
      Serial.println("WS connected");
      waitingForServer = false;
      exitFailsafe();
      {
        StaticJsonDocument<128> doc;
        doc["type"] = "auth";
        doc["username"] = username;
        doc["password"] = password;
        String out;
        serializeJson(doc, out);
        ws.sendTXT(out);
        Serial.println("Auth JSON sent");
      }
      break;

    case WStype_TEXT: {
      StaticJsonDocument<256> doc;
      if (deserializeJson(doc, payload, length)) {
        Serial.println("JSON parse error");
        return;
      }
      const char* msgType = doc["type"] | "";

      if (strcmp(msgType, "auth_failed") == 0) {
        const char* reason = doc["reason"] | "Unknown";
        Serial.printf("Auth failed: %s\n", reason);
        forceHardOn();
        ws.disconnect();
        waitingForServer = false;
        return;
      }

      if (strcmp(msgType, "pong") == 0) {
        Serial.println("Received PONG from Server B");
        return;
      }

      if (strcmp(msgType, "command") == 0) {
        const char* action = doc["payload"]["action"] | "";
        Serial.printf("Received command: %s\n", action);

        if (strcmp(action, "HARD_ON") == 0) {
          digitalWrite(pinD6, HIGH);
          digitalWrite(pinD5, LOW);
          ws.sendTXT("{\"type\":\"status\",\"pin\":\"D6\",\"state\":\"LOW\"}");
          Serial.println("D6 set LOW (HARD_ON)");

        } else if (strcmp(action, "AUTO_ON") == 0) {
            // Save current states before triggering
            int prevD5 = digitalRead(pinD5);
            int prevD6 = digitalRead(pinD6);

            // Trigger AUTO_ON sequence
            digitalWrite(pinD5, HIGH);
            digitalWrite(pinD6, LOW);
            delay(5000);

            // Restore previous states
            digitalWrite(pinD5, prevD5);
            digitalWrite(pinD6, prevD6);

              // Send status update (optional: reflect restored state)
            ws.sendTXT("{\"type\":\"status\",\"pin\":\"D5\",\"state\":\"LOW\"}");
            Serial.println("AUTO_ON completed, pins restored");
        }
       else if (strcmp(action, "HARD_OFF") == 0 || strcmp(action, "AUTO_OFF") == 0) {
          digitalWrite(pinD6, HIGH);
          digitalWrite(pinD5, HIGH);
          ws.sendTXT("{\"type\":\"status\",\"gpio\":\"ALL_HIGH\"}");
          Serial.println("Pins reset HIGH (OFF)");
        }
      }
      break;
    }

    case WStype_DISCONNECTED:
      Serial.println("WS disconnected");
      waitingForServer = false;
      forceHardOn();
      break;

    case WStype_ERROR:
      Serial.println("WS error occurred");
      waitingForServer = false;
      forceHardOn();
      break;
  }
}

// --- Setup ---
void setup() {
  Serial.begin(115200);

  pinMode(pinD6, OUTPUT);
  pinMode(pinD5, OUTPUT);
  digitalWrite(pinD6, HIGH);
  digitalWrite(pinD5, HIGH);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);
  Serial.print("Connecting to WiFi");

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 30) {
    delay(1000);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi OK");
    Serial.print("IP address: ");
    Serial.println(WiFi.localIP());
    reconnectWebSocket();
  } else {
    Serial.println("\nWiFi connection FAILED");
    forceHardOn();
  }
}

// --- Loop ---
void loop() {
  ws.loop();

  if (WiFi.status() != WL_CONNECTED) {
    forceHardOn();
    reconnectWiFi();
  }

  if (!ws.isConnected() && WiFi.status() == WL_CONNECTED) {
    reconnectWebSocket();
  }

  static uint32_t lastPing = 0;
  if (millis() - lastPing > 15000 && ws.isConnected()) {
    ws.sendTXT("{\"type\":\"ping\"}");
    lastPing = millis();
  }
}
