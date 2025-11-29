<?php
/**
 * Application Configuration Example
 *
 * Copy this file to config.php and update with your settings.
 * The config.php file is already set up to load environment variables from .env
 *
 * This example shows the structure but you should use the .env file
 * for actual configuration values.
 */

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Application constants
define('APP_NAME', getenv('APP_NAME') ?: 'Contact Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Password hashing algorithm
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Path constants
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('SRC_PATH', BASE_PATH . '/src');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('LOGS_PATH', BASE_PATH . '/logs');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'contacts_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200)); // 2 hours

// Pagination
define('DEFAULT_ITEMS_PER_PAGE', 20);

// Create required directories if they don't exist
$requiredDirs = [LOGS_PATH, UPLOAD_PATH];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php-errors.log');
}

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');
