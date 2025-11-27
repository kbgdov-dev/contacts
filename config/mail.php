<?php
/**
 * Конфигурация почтового сервиса
 *
 * Поддержка PHPMailer с SMTP и стандартной функции mail()
 */

return [
    // Драйвер отправки: 'smtp' или 'mail'
    'driver' => getenv('MAIL_DRIVER') ?: 'smtp',

    // Настройки SMTP
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.yandex.ru',
        'port' => (int)(getenv('SMTP_PORT') ?: 465),
        'encryption' => getenv('SMTP_ENCRYPTION') ?: 'ssl', // 'ssl' или 'tls'
        'username' => getenv('SMTP_USERNAME') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'noreply@example.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Contacts System',
        'timeout' => (int)(getenv('SMTP_TIMEOUT') ?: 30),
        'debug' => (int)(getenv('SMTP_DEBUG') ?: 0), // 0 = off, 1 = client messages, 2 = client and server
    ],

    // Предустановленные провайдеры
    'providers' => [
        'yandex' => [
            'host' => 'smtp.yandex.ru',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        'mailru' => [
            'host' => 'smtp.mail.ru',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        'gmail' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
        'sendgrid' => [
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'encryption' => 'tls',
        ],
        'mailgun' => [
            'host' => 'smtp.mailgun.org',
            'port' => 587,
            'encryption' => 'tls',
        ],
    ],

    // Настройки очереди
    'queue' => [
        'enabled' => (bool)(getenv('QUEUE_ENABLED') ?: true),
        'batch_size' => (int)(getenv('QUEUE_BATCH_SIZE') ?: 50), // Количество писем в одной партии
        'delay_between_batches' => (int)(getenv('QUEUE_DELAY_BATCHES') ?: 60), // Секунд между партиями
        'delay_between_emails' => (int)(getenv('QUEUE_DELAY_EMAILS') ?: 2), // Секунд между письмами
        'max_attempts' => (int)(getenv('QUEUE_MAX_ATTEMPTS') ?: 3), // Максимум попыток отправки
        'retry_delay' => (int)(getenv('QUEUE_RETRY_DELAY') ?: 300), // Секунд перед повтором
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled' => (bool)(getenv('RATE_LIMIT_ENABLED') ?: true),
        'max_per_hour' => (int)(getenv('RATE_LIMIT_PER_HOUR') ?: 100),
        'max_per_day' => (int)(getenv('RATE_LIMIT_PER_DAY') ?: 1000),
    ],

    // Настройки логирования
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/../logs/email.log',
        'log_level' => getenv('LOG_LEVEL') ?: 'info', // debug, info, warning, error
    ],

    // Tracking (отслеживание)
    'tracking' => [
        'enabled' => (bool)(getenv('TRACKING_ENABLED') ?: true),
        'pixel_enabled' => (bool)(getenv('TRACKING_PIXEL_ENABLED') ?: true),
        'link_enabled' => (bool)(getenv('TRACKING_LINK_ENABLED') ?: true),
        'base_url' => getenv('APP_URL') ?: 'http://localhost',
    ],

    // Настройки безопасности
    'security' => [
        'validate_email' => true,
        'sanitize_html' => true,
        'max_attachment_size' => (int)(getenv('MAX_ATTACHMENT_SIZE') ?: 10485760), // 10MB в байтах
        'allowed_attachment_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'],
    ],
];
