-- AirDirector Client Database Schema
-- Database: 4362062795_adclient

CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_subscription_id INT NOT NULL,
    store_user_id INT NOT NULL,
    station_name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_store_user (store_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    UNIQUE KEY unique_email_station (email, station_id),
    INDEX idx_station (station_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    station_user_id INT NULL,
    store_user_id INT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    session_token VARCHAR(128) NOT NULL,
    is_mic_active TINYINT(1) DEFAULT 0,
    connected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_ping DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    INDEX idx_station_active (station_id, last_ping)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS command_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    user_type ENUM('owner','subuser','admin') NOT NULL,
    user_id INT NOT NULL,
    command VARCHAR(50) NOT NULL,
    command_data JSON,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    INDEX idx_station_time (station_id, executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
