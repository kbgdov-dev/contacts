-- ============================================
-- Миграция: Добавление очереди email и логов
-- Версия: 001
-- Дата: 2025-11-27
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- Таблица: email_queue
-- Описание: Очередь отправки email
-- ============================================
CREATE TABLE IF NOT EXISTS `email_queue` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT UNSIGNED NULL COMMENT 'ID кампании (если часть рассылки)',
    `contact_id` INT UNSIGNED NULL COMMENT 'ID контакта',
    `recipient_email` VARCHAR(255) NOT NULL COMMENT 'Email получателя',
    `recipient_name` VARCHAR(255) NULL COMMENT 'Имя получателя',
    `subject` VARCHAR(500) NOT NULL COMMENT 'Тема письма',
    `body` TEXT NOT NULL COMMENT 'Тело письма (HTML)',
    `variables` JSON NULL COMMENT 'Переменные для персонализации',
    `attachments` JSON NULL COMMENT 'Список вложений',
    `status` ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Статус отправки',
    `priority` INT NOT NULL DEFAULT 0 COMMENT 'Приоритет (больше = выше)',
    `attempts` INT NOT NULL DEFAULT 0 COMMENT 'Количество попыток отправки',
    `max_attempts` INT NOT NULL DEFAULT 3 COMMENT 'Максимальное количество попыток',
    `scheduled_at` DATETIME NOT NULL COMMENT 'Время запланированной отправки',
    `sent_at` DATETIME NULL COMMENT 'Время фактической отправки',
    `error_message` TEXT NULL COMMENT 'Сообщение об ошибке',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_status_scheduled` (`status`, `scheduled_at`),
    INDEX `idx_campaign` (`campaign_id`),
    INDEX `idx_contact` (`contact_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),

    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Очередь отправки email';

-- ============================================
-- Таблица: email_logs
-- Описание: Логи отправки email
-- ============================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT UNSIGNED NULL COMMENT 'ID кампании',
    `contact_id` INT UNSIGNED NULL COMMENT 'ID контакта',
    `recipient_email` VARCHAR(255) NOT NULL COMMENT 'Email получателя',
    `subject` VARCHAR(500) NOT NULL COMMENT 'Тема письма',
    `status` ENUM('sent', 'failed', 'bounced') NOT NULL COMMENT 'Статус',
    `error_message` TEXT NULL COMMENT 'Сообщение об ошибке',
    `opened_at` DATETIME NULL COMMENT 'Время открытия',
    `clicked_at` DATETIME NULL COMMENT 'Время первого клика',
    `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Время отправки',

    PRIMARY KEY (`id`),
    INDEX `idx_campaign` (`campaign_id`),
    INDEX `idx_contact` (`contact_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sent_at` (`sent_at`),
    INDEX `idx_recipient` (`recipient_email`),

    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Логи отправки email';

-- ============================================
-- Таблица: email_tracking
-- Описание: Отслеживание открытий писем (tracking pixel)
-- ============================================
CREATE TABLE IF NOT EXISTS `email_tracking` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email_log_id` INT UNSIGNED NOT NULL COMMENT 'ID записи в email_logs',
    `tracking_token` VARCHAR(64) NOT NULL COMMENT 'Уникальный токен для отслеживания',
    `opened` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Было ли открыто',
    `open_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество открытий',
    `first_opened_at` DATETIME NULL COMMENT 'Время первого открытия',
    `last_opened_at` DATETIME NULL COMMENT 'Время последнего открытия',
    `user_agent` TEXT NULL COMMENT 'User Agent браузера',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP адрес',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_token` (`tracking_token`),
    INDEX `idx_email_log` (`email_log_id`),
    INDEX `idx_opened` (`opened`),

    FOREIGN KEY (`email_log_id`) REFERENCES `email_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Отслеживание открытий email';

-- ============================================
-- Таблица: email_link_tracking
-- Описание: Отслеживание кликов по ссылкам
-- ============================================
CREATE TABLE IF NOT EXISTS `email_link_tracking` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email_log_id` INT UNSIGNED NOT NULL COMMENT 'ID записи в email_logs',
    `original_url` TEXT NOT NULL COMMENT 'Оригинальный URL',
    `tracking_token` VARCHAR(64) NOT NULL COMMENT 'Уникальный токен для отслеживания',
    `click_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество кликов',
    `first_clicked_at` DATETIME NULL COMMENT 'Время первого клика',
    `last_clicked_at` DATETIME NULL COMMENT 'Время последнего клика',
    `user_agent` TEXT NULL COMMENT 'User Agent браузера',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP адрес',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_token` (`tracking_token`),
    INDEX `idx_email_log` (`email_log_id`),

    FOREIGN KEY (`email_log_id`) REFERENCES `email_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Отслеживание кликов по ссылкам в email';

-- ============================================
-- Обновление таблицы campaign_recipients
-- Добавление полей для отслеживания
-- ============================================
-- Note: Run these ALTER commands only if columns don't exist
-- ALTER TABLE `campaign_recipients`
-- ADD COLUMN `opened_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество открытий' AFTER `tracking_token`,
-- ADD COLUMN `clicked_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Количество кликов' AFTER `opened_count`;

-- ============================================
-- Таблица: rate_limit
-- Описание: Отслеживание лимитов отправки
-- ============================================
CREATE TABLE IF NOT EXISTS `rate_limit` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL COMMENT 'Идентификатор (email, IP, user_id)',
    `action` VARCHAR(100) NOT NULL COMMENT 'Действие (send_email)',
    `count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Количество действий',
    `window_start` DATETIME NOT NULL COMMENT 'Начало временного окна',
    `window_end` DATETIME NOT NULL COMMENT 'Конец временного окна',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_limit` (`identifier`, `action`, `window_start`),
    INDEX `idx_window` (`window_start`, `window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Отслеживание rate limiting';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Комментарии и примечания
-- ============================================
--
-- Использование:
-- 1. email_queue - добавляйте письма через QueueService
-- 2. email_logs - автоматически заполняется при отправке
-- 3. email_tracking - используется для tracking pixel
-- 4. email_link_tracking - используется для отслеживания кликов
-- 5. rate_limit - автоматически проверяется перед отправкой
--
-- Индексы оптимизированы для:
-- - Быстрой выборки pending писем из очереди
-- - Поиска логов по кампаниям и контактам
-- - Отслеживания открытий и кликов
-- - Проверки rate limits
-- ============================================
