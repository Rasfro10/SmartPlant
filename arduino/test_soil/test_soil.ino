#include <Wire.h>

#define MOISTURE_SENSOR_PIN_A5 A5
#define MOISTURE_SENSOR_PIN_A6 A6

void setup() {
  Serial.begin(9600);  // Initialize serial communication
  pinMode(MOISTURE_SENSOR_PIN_A5, INPUT);  // Set pin A5 as input
  pinMode(MOISTURE_SENSOR_PIN_A6, INPUT);  // Set pin A6 as input
  Serial.println("Soil Moisture Test - Reading from pin A6");
  Serial.println("=======================================");
}

void loop() {
  checkMoisture(MOISTURE_SENSOR_PIN_A6, "A6");
  Serial.println("---------------------------------------");
  delay(2000);  // Wait 2 seconds between readings
}

void checkMoisture(int pin, String pinName) {
  float moist = analogRead(pin);
  float moistPct = 100 - ((moist / 1023) * 100);
  
  Serial.print(pinName);
  Serial.print(" Raw reading: ");
  Serial.print(moist);
  Serial.print(" | ");

  if(moist > 0 && moist < 204.6) {
    printValue(pinName + " Moisture", moistPct, "Critical Low");
  }
  else if (moist > 204.6 && moist < 409.2) {
    printValue(pinName + " Moisture", moistPct, "Moderately Low");
  }
  else if (moist > 409.2 && moist < 613.8) {
    printValue(pinName + " Moisture", moistPct, "Moderate");
  }
  else if (moist > 613.8 && moist < 818.4) {
    printValue(pinName + " Moisture", moistPct, "Moderately High");
  }
  else if (moist > 818.4 && moist < 1023) {
    printValue(pinName + " Moisture", moistPct, "Critical High");
  }
  else {
    printValue(pinName + " Moisture", moistPct, "Out of bounds");
  }
}

void printValue(String type, float value, String alert) {
  Serial.print(alert);
  Serial.print(" - ");
  Serial.print(type);
  Serial.print(": ");

  if(type.indexOf("Moisture") >= 0) {
    Serial.print(value);
    Serial.println("%");
  }
  else {    
    Serial.println(value);
  }
}