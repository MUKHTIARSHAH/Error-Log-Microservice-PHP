-- ============================================================================
-- Error Log Microservice - Complete Database Schema
-- ============================================================================
-- This file contains the complete database schema including:
-- - Database creation
-- - Table structure with IP address tracking
-- - All indexes for performance
-- - Views for common queries
-- - Stored procedures for maintenance
-- - Migration section for existing databases
-- ============================================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `error_log` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `error_log`;

-- ============================================================================
-- Create errors table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `errors` (
    `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto-increment primary key',
    `uuid` CHAR(36) NULL COMMENT 'Unique error identifier (UUID) - optional',
    `user_id` INT(11) NOT NULL COMMENT 'User ID who generated the error',
    `organization_id` VARCHAR(255) NOT NULL COMMENT 'Organization identifier',
    `product_id` INT(11) NOT NULL COMMENT 'Product ID where error occurred',
    `status` ENUM('error', 'warning', 'info', 'debug') NOT NULL DEFAULT 'error' COMMENT 'Error status level',
    `message` TEXT NOT NULL COMMENT 'Error message',
    `code` INT(11) NOT NULL COMMENT 'Error code',
    `data` JSON NULL COMMENT 'Additional error data in JSON format',
    `timestamp` DATETIME NOT NULL COMMENT 'Error timestamp from request',
    `ip_address` VARCHAR(45) NULL COMMENT 'Client IP address (supports IPv4 and IPv6)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Soft delete flag (1=active, 0=deleted)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update timestamp',
    PRIMARY KEY (`id`),
    INDEX `idx_uuid` (`uuid`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_organization_id` (`organization_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_timestamp` (`timestamp`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Error logs storage table';

-- ============================================================================
-- Additional composite indexes for better query performance
-- ============================================================================
CREATE INDEX IF NOT EXISTS `idx_user_org_product` ON `errors` (`user_id`, `organization_id`, `product_id`);
CREATE INDEX IF NOT EXISTS `idx_status_timestamp` ON `errors` (`status`, `timestamp`);
CREATE INDEX IF NOT EXISTS `idx_org_status` ON `errors` (`organization_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_active_status` ON `errors` (`is_active`, `status`);
CREATE INDEX IF NOT EXISTS `idx_active_user_org` ON `errors` (`is_active`, `user_id`, `organization_id`);
CREATE INDEX IF NOT EXISTS `idx_active_timestamp` ON `errors` (`is_active`, `timestamp`);

-- ============================================================================
-- Insert sample data for testing
-- ============================================================================
INSERT INTO `errors` (`user_id`, `organization_id`, `product_id`, `status`, `message`, `code`, `data`, `timestamp`, `ip_address`) VALUES
(1, 'xyz', 1, 'error', 'Validation failed', 422, '{"validation_errors": {"email_address": "Email format is invalid", "password": "Password must be at least 8 characters"}}', '2024-01-15 14:25:30', '192.168.1.100'),
(2, 'abc', 1, 'warning', 'Deprecated function used', 200, '{"function_name": "old_function", "suggestion": "Use new_function instead"}', '2024-01-15 14:30:00', '192.168.1.101'),
(1, 'xyz', 2, 'info', 'User login successful', 200, '{"login_time": "2024-01-15 14:35:00"}', '2024-01-15 14:35:00', '192.168.1.102'),
(3, 'def', 1, 'error', 'Database connection failed', 500, '{"connection_string": "mysql://localhost:3306/db", "error_code": "2002"}', '2024-01-15 14:40:00', '192.168.1.103'),
(1, 'xyz', 1, 'debug', 'API request received', 200, '{"endpoint": "/api/users", "method": "POST", "request_id": "req_123"}', '2024-01-15 14:45:00', '192.168.1.104');

-- ============================================================================
-- Create views for common queries
-- ============================================================================

-- View for recent errors (last 24 hours)
CREATE OR REPLACE VIEW `recent_errors` AS
SELECT 
    `id`,
    `uuid`,
    `user_id`,
    `organization_id`,
    `product_id`,
    `status`,
    `message`,
    `code`,
    `data`,
    `timestamp`,
    `ip_address`,
    `is_active`,
    `created_at`,
    `updated_at`
FROM `errors`
WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND `is_active` = 1
ORDER BY `created_at` DESC;

-- View for error statistics
CREATE OR REPLACE VIEW `error_statistics` AS
SELECT 
    `status`,
    COUNT(*) as `count`,
    DATE(`created_at`) as `date`
FROM `errors`
WHERE `is_active` = 1
GROUP BY `status`, DATE(`created_at`)
ORDER BY `date` DESC, `count` DESC;

-- ============================================================================
-- Stored procedures for maintenance operations
-- ============================================================================

-- Stored procedure for soft delete by UUID
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `soft_delete_error`(IN error_uuid CHAR(36))
BEGIN
    UPDATE `errors` 
    SET `is_active` = 0, `updated_at` = CURRENT_TIMESTAMP 
    WHERE `uuid` = error_uuid AND `is_active` = 1;
END //
DELIMITER ;

-- Stored procedure for soft delete by ID
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `soft_delete_error_by_id`(IN error_id INT)
BEGIN
    UPDATE `errors` 
    SET `is_active` = 0, `updated_at` = CURRENT_TIMESTAMP 
    WHERE `id` = error_id AND `is_active` = 1;
END //
DELIMITER ;

-- Stored procedure for error cleanup (delete old records)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `cleanup_old_errors`(IN days_to_keep INT)
BEGIN
    DELETE FROM `errors` 
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    AND `is_active` = 0;
END //
DELIMITER ;

-- ============================================================================
-- Migration section for existing databases
-- ============================================================================
-- If you already have an errors table, run these commands to add the ip_address column:

-- Add ip_address column if it doesn't exist (for existing databases)
-- ALTER TABLE `errors` 
-- ADD COLUMN IF NOT EXISTS `ip_address` VARCHAR(45) NULL COMMENT 'Client IP address (supports IPv4 and IPv6)' 
-- AFTER `timestamp`;

-- Add index for ip_address if it doesn't exist
-- CREATE INDEX IF NOT EXISTS `idx_ip_address` ON `errors` (`ip_address`);

-- Update the recent_errors view to include ip_address
-- (Already included in the view definition above)

-- ============================================================================
-- Optional: Create database user with proper permissions
-- ============================================================================
-- Uncomment and modify as needed:
-- CREATE USER IF NOT EXISTS 'error_log_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `error_log`.* TO 'error_log_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================================================
-- Optional: Foreign key constraints (uncomment when reference tables exist)
-- ============================================================================
-- ALTER TABLE `errors` ADD CONSTRAINT `fk_errors_user_id` 
--     FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
-- ALTER TABLE `errors` ADD CONSTRAINT `fk_errors_product_id` 
--     FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================================================
-- Verification queries (optional - run to verify setup)
-- ============================================================================
-- Verify table structure:
-- DESCRIBE `errors`;

-- Verify ip_address column exists:
-- SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'error_log' 
-- AND TABLE_NAME = 'errors' 
-- AND COLUMN_NAME = 'ip_address';

-- Verify indexes:
-- SHOW INDEXES FROM `errors`;

-- Verify views:
-- SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Verify stored procedures:
-- SHOW PROCEDURE STATUS WHERE Db = 'error_log';
