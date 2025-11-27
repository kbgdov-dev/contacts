<?php
/**
 * Email Tracking Endpoint
 *
 * Отслеживание открытий email через прозрачный 1x1 пиксель
 */

// Загрузить автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\TrackingService;

// Получить token из GET параметра
$token = $_GET['t'] ?? '';

if (!empty($token)) {
    try {
        $trackingService = new TrackingService();

        // Записать открытие
        $trackingService->recordOpen(
            $token,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        );
    } catch (Exception $e) {
        // Игнорируем ошибки, чтобы не сломать отображение письма
        error_log("Tracking pixel error: " . $e->getMessage());
    }
}

// Вернуть прозрачный 1x1 GIF пиксель
header('Content-Type: image/gif');
header('Content-Length: 43');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Прозрачный 1x1 GIF (43 байта)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;
