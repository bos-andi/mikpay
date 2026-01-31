-- MIKPAY Database Schema
-- Database: mikpay

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `mikpay` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mikpay`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `full_name` VARCHAR(200) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `trial_started` DATE DEFAULT NULL,
    `trial_ends` DATE DEFAULT NULL,
    `subscription_package` VARCHAR(50) DEFAULT NULL,
    `subscription_start` DATE DEFAULT NULL,
    `subscription_end` DATE DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_status` (`status`),
    INDEX `idx_subscription_end` (`subscription_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table (MikroTik router sessions per user)
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `session_name` VARCHAR(100) NOT NULL,
    `router_name` VARCHAR(200) DEFAULT NULL,
    `router_ip` VARCHAR(50) DEFAULT NULL,
    `router_port` INT(5) DEFAULT 8728,
    `router_username` VARCHAR(100) DEFAULT NULL,
    `router_password` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_session_name` (`session_name`),
    UNIQUE KEY `unique_user_session` (`user_id`, `session_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
-- Password hash untuk 'admin123'
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `role`, `status`, `subscription_package`, `subscription_start`, `subscription_end`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mikpay.com', 'Administrator', 'admin', 'active', 'enterprise', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY))
ON DUPLICATE KEY UPDATE `username`=`username`;
