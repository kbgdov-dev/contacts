#!/usr/bin/env php
<?php
/**
 * Cron Job: Process Email Queue
 *
 * Обрабатывает очередь отправки email
 * Запускать каждые 5 минут через crontab:
 * */5 * * * * /usr/bin/php /path/to/project/cron/process-email-queue.php >> /var/log/email-queue.log 2>&1
 *
 * @package App\Cron
 */

// Проверка, что скрипт запущен из CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line' . PHP_EOL);
}

// Установить рабочую директорию
$rootDir = dirname(__DIR__);
chdir($rootDir);

// Загрузить автозагрузчик Composer
require_once $rootDir . '/vendor/autoload.php';

// Загрузить переменные окружения из .env
if (file_exists($rootDir . '/.env')) {
    $lines = file($rootDir . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

use App\Services\QueueService;

try {
    echo "[" . date('Y-m-d H:i:s') . "] Starting email queue processing..." . PHP_EOL;

    // Загрузить конфигурацию
    $config = require $rootDir . '/config/mail.php';

    if (!$config['queue']['enabled']) {
        echo "[" . date('Y-m-d H:i:s') . "] Queue is disabled in configuration." . PHP_EOL;
        exit(0);
    }

    $batchSize = $config['queue']['batch_size'] ?? 50;

    // Создать экземпляр сервиса очереди
    $queueService = new QueueService();

    // Получить текущую статистику
    $statsBefore = $queueService->getStats();
    echo "[" . date('Y-m-d H:i:s') . "] Queue status before processing:" . PHP_EOL;
    echo "  - Pending: {$statsBefore['pending']}" . PHP_EOL;
    echo "  - Processing: {$statsBefore['processing']}" . PHP_EOL;
    echo "  - Sent: {$statsBefore['sent']}" . PHP_EOL;
    echo "  - Failed: {$statsBefore['failed']}" . PHP_EOL;

    // Обработать очередь
    $stats = $queueService->process($batchSize);

    echo "[" . date('Y-m-d H:i:s') . "] Processing completed:" . PHP_EOL;
    echo "  - Sent: {$stats['sent']}" . PHP_EOL;
    echo "  - Failed: {$stats['failed']}" . PHP_EOL;

    // Очистка старых записей (раз в день в 3 часа ночи)
    $currentHour = (int)date('H');
    if ($currentHour === 3) {
        echo "[" . date('Y-m-d H:i:s') . "] Running cleanup of old records..." . PHP_EOL;
        $cleaned = $queueService->cleanup(30);
        echo "[" . date('Y-m-d H:i:s') . "] Cleaned {$cleaned} old records." . PHP_EOL;
    }

    echo "[" . date('Y-m-d H:i:s') . "] Done." . PHP_EOL;
    exit(0);

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
