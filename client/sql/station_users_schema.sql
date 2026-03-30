-- AirDirector Client DB - Schema for station_users and active_sessions tables
-- Run this on the client DB (u362062795_adclient) to ensure correct structure

CREATE TABLE IF NOT EXISTS station_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    language VARCHAR(5) DEFAULT 'it',
    access_days VARCHAR(20) DEFAULT '1,2,3,4,5,6,7',
    access_time_start TIME DEFAULT '00:00:00',
    access_time_end TIME DEFAULT '23:59:59',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_station_email (station_id, email),
    INDEX idx_station (station_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add UNIQUE KEY if the table already exists but is missing the index
ALTER TABLE station_users
    ADD UNIQUE KEY IF NOT EXISTS unique_station_email (station_id, email);

CREATE TABLE IF NOT EXISTS active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    store_user_id INT NULL,
    station_user_id INT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    last_ping DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_station (station_id),
    INDEX idx_ping (last_ping)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
