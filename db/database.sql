DROP DATABASE IF EXISTS smartplant;
CREATE DATABASE smartplant;
USE smartplant;

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (session_id)
);

-- Index for faster lookups by session_id
CREATE INDEX idx_session_id ON user_sessions(session_id);

-- Index for cleanup of expired sessions
CREATE INDEX idx_expires_at ON user_sessions(expires_at);

CREATE TABLE IF NOT EXISTS plants (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    plant_type ENUM('Indendørs', 'Udendørs', 'Både og') NOT NULL DEFAULT 'Indendørs',
    watering_frequency VARCHAR(50),
    light_needs VARCHAR(20),
    notes TEXT,
    water_notification ENUM('on', 'off') DEFAULT 'off',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS plant_data (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    soil_moisture INT,
    light_level FLOAT,
    temperature FLOAT,
    humidity FLOAT,
    pressure FLOAT,
    watered_at DATETIME,
    reading_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    timestamp BIGINT,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS plant_sensors (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    sensor_pin VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_plant (plant_id),
    UNIQUE KEY unique_sensor (sensor_pin)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    plant_id INT NOT NULL,
    notification_type ENUM('water', 'fertilize', 'repot') NOT NULL,
    message TEXT,
    is_read ENUM('yes', 'no') DEFAULT 'no',
    scheduled_for TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE CASCADE
);