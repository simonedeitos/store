-- AirDirector Store - Subscription Tables for Client Service
-- Run this after the main store schema

-- Client subscription plans (configured by admin)
CREATE TABLE IF NOT EXISTS client_subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    billing_cycle ENUM('monthly','semiannual','annual') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User subscriptions
-- Per database esistenti, eseguire:
-- ALTER TABLE client_subscriptions MODIFY COLUMN status ENUM('pending','active','suspended','expired','cancelled') DEFAULT 'pending';
-- ALTER TABLE client_subscriptions ADD COLUMN order_id INT NULL AFTER user_id;
-- ALTER TABLE client_subscriptions ADD INDEX idx_order (order_id);
CREATE TABLE IF NOT EXISTS client_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    plan_id INT NOT NULL,
    radio_name VARCHAR(255) NOT NULL,
    station_token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending','active','suspended','expired','cancelled') DEFAULT 'pending',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    auto_renew TINYINT(1) DEFAULT 1,
    reminder_30 TINYINT(1) DEFAULT 1,
    reminder_7  TINYINT(1) DEFAULT 1,
    reminder_48 TINYINT(1) DEFAULT 1,
    reminder_30_sent TINYINT(1) DEFAULT 0,
    reminder_7_sent  TINYINT(1) DEFAULT 0,
    reminder_48_sent TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES client_subscription_plans(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subusers (station speakers) - managed by the station owner
CREATE TABLE IF NOT EXISTS client_station_subusers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
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
    FOREIGN KEY (subscription_id) REFERENCES client_subscriptions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_sub (email, subscription_id),
    INDEX idx_subscription (subscription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default plans
INSERT IGNORE INTO client_subscription_plans (name, description, billing_cycle, price, sort_order) VALUES
('AirDirector Client - Mensile', 'Accesso completo ad AirDirector Client per 1 mese', 'monthly', 9.99, 1),
('AirDirector Client - Semestrale', 'Accesso completo ad AirDirector Client per 6 mesi (risparmia 10%)', 'semiannual', 53.94, 2),
('AirDirector Client - Annuale', 'Accesso completo ad AirDirector Client per 12 mesi (risparmia 20%)', 'annual', 95.90, 3);
