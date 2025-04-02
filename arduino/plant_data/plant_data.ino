#include <ArduinoHttpClient.h>
#include <WiFiNINA.h>
#include <Arduino_MKRIoTCarrier.h>
#include <ArduinoJson.h>

// WiFi credentials
const char* ssid = "NOKIA-C9E1";
const char* password = "S6NzYRZWFp2g";

// Server details
const char* serverAddress = "192.168.1.69";
const String apiEndpoint = "/smartplant/api/add_plant_data.php";
const String apiKey = "data";
const int serverPort = 80;

// Pin
const int soilMoisturePin = A6;  // Soil moisture sensor pin

int plantId = 1;

// Initialize objects
MKRIoTCarrier carrier;
WiFiClient wifi;
HttpClient client = HttpClient(wifi, serverAddress, serverPort);

// Timing variables
unsigned long previousMillis = 0;
const long interval = 3000;

void setup() {
  Serial.begin(9600);
  while (!Serial)
    ;

  // Initialize the carrier
  CARRIER_CASE = true;
  carrier.begin();
  carrier.display.setRotation(0);

  // Display welcome message
  displayMessage("SmartPlant", "Connecting to WiFi...");

  // Connect to WiFi
  connectToWiFi();
}

void loop() {
  // Check carrier buttons and sensors
  carrier.Buttons.update();

  // Check if it's time to send data
  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis >= interval) {
    previousMillis = currentMillis;

    // Read sensor data
    float soilMoisture = readSoilMoisture();

    float lightLevel = 0;

    if (carrier.Light.colorAvailable()) {
      int proximity = carrier.Light.readProximity();
      lightLevel = 100 - proximity;
    }

    float temperature = carrier.Env.readTemperature();
    float humidity = carrier.Env.readHumidity();
    float batteryLevel = 100;  // Placeholder for now

    // Send data to server
    sendDataToServer(soilMoisture, lightLevel, temperature, humidity, batteryLevel);

    // Update display with current readings
    updateDisplay(soilMoisture, lightLevel, temperature, humidity);
  }

  // Check if button 0 is pressed to change plant ID
  if (carrier.Buttons.onTouchDown(TOUCH0)) {
    changePlantId();
  }

  delay(100);
}

void connectToWiFi() {
  Serial.print("Connecting to ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("");
    Serial.println("WiFi connected");
    Serial.println("IP address: ");
    Serial.println(WiFi.localIP());

    displayMessage("Connected!", "IP: " + WiFi.localIP().toString());
    delay(2000);
  } else {
    Serial.println("");
    Serial.println("WiFi connection failed");
    displayMessage("WiFi Failed", "Check credentials");
    delay(2000);
  }
}

float readSoilMoisture() {
  // Read the analog value from the soil moisture sensor
  int rawValue = analogRead(soilMoisturePin);

  int dryValue = 1023;
  int wetValue = 0;

  // Calculate percentage (0-100%)
  float percentage = map(rawValue, dryValue, wetValue, 0, 100);

  // Ensure values are within valid range
  percentage = constrain(percentage, 0, 100);

  return percentage;
}

void sendDataToServer(float soilMoisture, float lightLevel, float temperature, float humidity, float batteryLevel) {
  // Create JSON document
  StaticJsonDocument<200> jsonDoc;

  jsonDoc["api_key"] = apiKey;
  jsonDoc["plant_id"] = plantId;
  jsonDoc["soil_moisture"] = soilMoisture;
  jsonDoc["light_level"] = lightLevel;
  jsonDoc["temperature"] = temperature;
  jsonDoc["humidity"] = humidity;
  jsonDoc["battery_level"] = batteryLevel;

  // Serialize JSON to string
  String jsonString;
  serializeJson(jsonDoc, jsonString);

  // Print JSON for debugging
  Serial.print("Sending data: ");
  Serial.println(jsonString);

  // Display sending message
  displayMessage("Sending Data", "To server...");

  // Send the POST request
  client.beginRequest();
  client.post(apiEndpoint);
  client.sendHeader("Content-Type", "application/json");
  client.sendHeader("Content-Length", jsonString.length());
  client.beginBody();
  client.print(jsonString);
  client.endRequest();

  // Get the response status and body
  int statusCode = client.responseStatusCode();
  String response = client.responseBody();

  Serial.print("Status code: ");
  Serial.println(statusCode);
  Serial.print("Response: ");
  Serial.println(response);

  if (statusCode == 201) {
    displayMessage("Success!", "Data sent to server");
  } else {
    displayMessage("Error " + String(statusCode), "Failed to send data");
  }

  delay(2000);  // Show status for 2 seconds
}

void updateDisplay(float soilMoisture, float lightLevel, float temperature, float humidity) {
  // Clear the display
  carrier.display.fillScreen(ST77XX_BLACK);
  carrier.display.setTextColor(ST77XX_WHITE);
  carrier.display.setTextSize(2);

  // Plant ID
  carrier.display.setCursor(20, 20);
  carrier.display.print("Plant ID: ");
  carrier.display.print(plantId);

  // Soil Moisture
  carrier.display.setCursor(20, 60);
  carrier.display.print("Soil: ");
  carrier.display.print(soilMoisture, 1);
  carrier.display.print("%");

  // Temperature
  carrier.display.setCursor(20, 90);
  carrier.display.print("Temp: ");
  carrier.display.print(temperature, 1);
  carrier.display.print("C");

  // Humidity
  carrier.display.setCursor(20, 120);
  carrier.display.print("Humidity: ");
  carrier.display.print(humidity, 1);
  carrier.display.print("%");

  // Light Level
  carrier.display.setCursor(20, 150);
  carrier.display.print("Light: ");
  carrier.display.print(lightLevel, 1);
  carrier.display.print(" units");

  // Next update time
  carrier.display.setCursor(20, 200);
  carrier.display.setTextSize(1);
  carrier.display.print("Next update in ");
  carrier.display.print(interval / 1000);
  carrier.display.print(" seconds");
}

void displayMessage(String title, String message) {
  // Clear the display
  carrier.display.fillScreen(ST77XX_BLACK);

  // Draw title
  carrier.display.setTextColor(ST77XX_WHITE);
  carrier.display.setTextSize(3);
  carrier.display.setCursor(20, 40);
  carrier.display.println(title);

  // Draw message
  carrier.display.setTextSize(2);
  carrier.display.setCursor(20, 100);
  carrier.display.println(message);
}

void changePlantId() {
  // Increment plant ID (cycle through 1-10)
  plantId = (plantId % 10) + 1;

  // Display the new plant ID
  displayMessage("Plant ID", "Changed to: " + String(plantId));
  delay(2000);

  // Update the display with current readings
  float soilMoisture = readSoilMoisture();

  float lightLevel = 0;
  if (carrier.Light.colorAvailable()) {
    int proximity = carrier.Light.readProximity();
    lightLevel = 100 - proximity;
  }

  float temperature = carrier.Env.readTemperature();
  float humidity = carrier.Env.readHumidity();

  updateDisplay(soilMoisture, lightLevel, temperature, humidity);
}