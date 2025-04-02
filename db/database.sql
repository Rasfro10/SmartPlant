DROP DATABASE IF EXISTS smartplant;
CREATE DATABASE smartplant;
USE smartplant;

-- Opret users tabel
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Opret plants tabel
CREATE TABLE IF NOT EXISTS plants (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    plant_type ENUM('Indendørs', 'Udendørs', 'Både og') NOT NULL DEFAULT 'Indendørs',
    watering_frequency VARCHAR(50),
    light_needs VARCHAR(20),
    notes TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS plant_data (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    soil_moisture FLOAT,
    light_level FLOAT,
    temperature FLOAT,
    humidity FLOAT,
    battery_level FLOAT,
    watered_at DATETIME,
    fertilized_at DATETIME,
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);