-- ============================================
-- Migration: Add user_settings table
-- Version: 002
-- Date: 2025-11-28
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Table: user_settings
-- Description: User-specific SMTP settings
-- ============================================
CREATE TABLE IF NOT EXISTS `user_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'User ID',
    `smtp_host` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SMTP server hostname',
    `smtp_port` INT NOT NULL DEFAULT 587 COMMENT 'SMTP server port',
    `smtp_encryption` ENUM('tls', 'ssl', 'none') NOT NULL DEFAULT 'tls' COMMENT 'Encryption type',
    `smtp_username` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SMTP username',
    `smtp_password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'SMTP password',
    `smtp_from_email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'From email address',
    `smtp_from_name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'From name',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user` (`user_id`),
    INDEX `idx_user_id` (`user_id`),

    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User-specific SMTP configuration';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Comments
-- ============================================
--
-- This table stores SMTP settings per user, allowing each user
-- to configure their own email sending settings.
--
-- ============================================
