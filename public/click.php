<?php
/**
 * Link Click Tracking Endpoint
 *
 * Отслеживание кликов по ссылкам в email
 */

// Загрузить автозагрузчик
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\TrackingService;

// Получить token из GET параметра
$token = $_GET['t'] ?? '';
$redirectUrl = '/';

if (!empty($token)) {
    try {
        $trackingService = new TrackingService();

        // Записать клик и получить оригинальный URL
        $originalUrl = $trackingService->recordClick(
            $token,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? ''
        );

        if ($originalUrl) {
            $redirectUrl = $originalUrl;
        }
    } catch (Exception $e) {
        // Игнорируем ошибки и перенаправляем на главную
        error_log("Link tracking error: " . $e->getMessage());
    }
}

// Перенаправить на оригинальный URL
header('Location: ' . $redirectUrl, true, 302);
exit;
