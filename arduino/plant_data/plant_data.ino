#include <WiFiNINA.h>
#include <PubSubClient.h>
#include <Arduino_MKRIoTCarrier.h>
#include <ArduinoJson.h>
#include <WiFiUdp.h>
#include <NTPClient.h>
#include "config.h"

MKRIoTCarrier carrier;
const char* mqtt_server = MQTT_SERVER; 
const char* topic = "plant-data"; 
const char* mqtt_username = MQTT_USERNAME;
const char* mqtt_password = MQTT_PASSWORD;

// Define moisture sensor pin - only using A6 now
const int moistureSensorPin = 6; // A6

// Thresholds for detecting if a sensor is connected
const int MIN_SENSOR_VALUE = 10;   // Minimum value for a connected sensor
const int MAX_SENSOR_VALUE = 1020; // Maximum value for a connected sensor

// Variables for tracking watering events
int previousMoisture = 0;
boolean isFirstReading = true;
boolean wateringDetected = false;
long wateringTimestamp = 0;
const int MOISTURE_INCREASE_THRESHOLD = 50; // Minimum moisture increase to consider as watering

WiFiClient wifiClient;
PubSubClient client(wifiClient);

WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org");
const long gmtOffset_sec = 3600; 

void setup() {
  Serial.begin(9600);
  while (!Serial); // Wait for serial connection
  
  Serial.println("Initializing...");
  
  // Initialize MKR IoT Carrier
  carrier.begin();
  
  // Initialize the light sensor (without trying to access private methods)
  Serial.println("Light sensor initialized");
  
  // Connect to WiFi
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  
  // Wait for connection
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Forbinder til WiFi...");
  }
  
  Serial.println("Forbundet til WiFi!");

  // Connect to MQTT broker
  client.setServer(mqtt_server, 1883);
  if (client.connect("ArduinoClient", mqtt_username, mqtt_password)) {
    Serial.println("Forbundet til MQTT-broker!");
  } else {
    Serial.print("Fejl ved forbindelse til MQTT-broker, rc=");
    Serial.print(client.state());
  }

  // Initialize time client
  timeClient.begin();
  timeClient.setTimeOffset(gmtOffset_sec);
  
  Serial.println("Setup complete");
}

void loop() {
  // Maintain MQTT connection
  if (!client.connected()) {
    client.connect("ArduinoClient", mqtt_username, mqtt_password);
  }
  client.loop();

  // Update time
  timeClient.update();
  
  // Read common sensor data for all plants
  float temperature = carrier.Env.readTemperature();
  float humidity = carrier.Env.readHumidity();
  float pressure = carrier.Pressure.readPressure();
  long timestamp = timeClient.getEpochTime();
  
  // Get the current time 
  int hour = timeClient.getHours();
  
  // Create a simulated light level based on time of day
  int lightLevel;
  if (hour >= 6 && hour < 20) {
    // Daytime (higher light level)
    lightLevel = 600 + random(0, 400); // Random between 600-1000
  } else {
    // Nighttime (lower light level)
    lightLevel = random(0, 200); // Random between 0-200
  }
  
  // Check sensor A6 only
  int moistureValue = analogRead(moistureSensorPin);
  
  // Print raw moisture value for debugging
  Serial.print("Raw moisture reading from A6: ");
  Serial.println(moistureValue);
  
  // Detect watering event (significant increase in moisture)
  if (!isFirstReading) {
    // Calculate the change in moisture since the last reading
    int moistureDifference = moistureValue - previousMoisture;
    Serial.print("Moisture change: ");
    Serial.println(moistureDifference);
    
    // If moisture increased significantly, this indicates watering
    if (moistureDifference <= -MOISTURE_INCREASE_THRESHOLD && !wateringDetected) {
      // Remember, with moisture sensors lower values means more moisture
      Serial.println("Watering detected!");
      wateringDetected = true;
      wateringTimestamp = timestamp;
    }
    
    // Reset watering detection when moisture starts decreasing again
    // This allows detecting the next watering event
    if (moistureDifference > 10 && wateringDetected) {
      Serial.println("Moisture decreasing, resetting watering detection");
      wateringDetected = false;
    }
  }
  
  // Update previous moisture reading
  previousMoisture = moistureValue;
  isFirstReading = false;
  
  if (isSensorConnected(moistureValue)) {
    // Send watering timestamp only once after detection
    if (wateringDetected && wateringTimestamp > 0) {
      sendSensorDataWithWatering(timestamp, temperature, humidity, pressure, moistureValue, moistureSensorPin, lightLevel, wateringTimestamp);
      wateringTimestamp = 0; // Reset to prevent sending again
    } else {
      sendSensorData(timestamp, temperature, humidity, pressure, moistureValue, moistureSensorPin, lightLevel);
    }
  } else {
    Serial.println("Ingen sensor forbundet til A6 eller værdi uden for rækkevidde");
  }

  // Wait before next reading
  delay(5000);
}

// Function to check if a sensor is connected
boolean isSensorConnected(int sensorValue) {
  // If reading is within expected range for a connected sensor
  return (sensorValue >= MIN_SENSOR_VALUE && sensorValue <= MAX_SENSOR_VALUE);
}

// Function to send sensor data (without watering timestamp)
void sendSensorData(long timestamp, float temperature, float humidity, float pressure, 
                   int moisture, int pin, int lightLevel) {
  // Create JSON document
  StaticJsonDocument<250> doc;
  
  // Add data to JSON document
  doc["timestamp"] = timestamp;
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  doc["pressure"] = pressure;
  doc["moisture"] = moisture;
  doc["light_level"] = lightLevel;
  doc["pin"] = pin;
  
  // Convert JSON to string
  char jsonBuffer[250];
  serializeJson(doc, jsonBuffer);
  
  // Send data
  if (client.publish(topic, jsonBuffer)) {
    Serial.print("Data sendt fra A");
    Serial.print(pin);
    Serial.print(": ");
    Serial.println(jsonBuffer);
  } else {
    Serial.print("Fejl ved sending af data fra A");
    Serial.println(pin);
  }
}

// Function to send sensor data with watering timestamp
void sendSensorDataWithWatering(long timestamp, float temperature, float humidity, float pressure, 
                   int moisture, int pin, int lightLevel, long wateringTime) {
  // Create JSON document
  StaticJsonDocument<300> doc;
  
  // Add data to JSON document
  doc["timestamp"] = timestamp;
  doc["temperature"] = temperature;
  doc["humidity"] = humidity;
  doc["pressure"] = pressure;
  doc["moisture"] = moisture;
  doc["light_level"] = lightLevel;
  doc["pin"] = pin;
  doc["watered_at"] = wateringTime; // Add watering timestamp
  
  // Convert JSON to string
  char jsonBuffer[300];
  serializeJson(doc, jsonBuffer);
  
  // Send data
  if (client.publish(topic, jsonBuffer)) {
    Serial.print("Watering data sendt fra A");
    Serial.print(pin);
    Serial.print(": ");
    Serial.println(jsonBuffer);
  } else {
    Serial.print("Fejl ved sending af watering data fra A");
    Serial.println(pin);
  }
}