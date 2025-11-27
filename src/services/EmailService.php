<?php
/**
 * Email Service
 *
 * Сервис для отправки email через PHPMailer с поддержкой SMTP
 *
 * @package App\Services
 */

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PDO;

class EmailService
{
    private $config;
    private $mailer;
    private $db;

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Загрузить конфигурацию
        $configPath = __DIR__ . '/../../config/mail.php';
        if (!file_exists($configPath)) {
            throw new \Exception('Mail configuration file not found');
        }
        $this->config = require $configPath;

        // Инициализировать PHPMailer
        $this->setupMailer();

        // Инициализировать подключение к БД
        $this->initDatabase();
    }

    /**
     * Отправить одно письмо
     *
     * @param string $to Email получателя
     * @param string $subject Тема письма
     * @param string $body Тело письма (HTML)
     * @param array $attachments Массив путей к вложениям
     * @param bool $isHtml Отправлять как HTML
     * @param string|null $toName Имя получателя
     * @return bool Успешность отправки
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        array $attachments = [],
        bool $isHtml = true,
        ?string $toName = null
    ): bool {
        try {
            // Валидация email
            if ($this->config['security']['validate_email'] && !$this->isValidEmail($to)) {
                throw new \Exception("Invalid email address: {$to}");
            }

            // Очистить предыдущие данные
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Установить получателя
            $this->mailer->addAddress($to, $toName ?: '');

            // Установить тему и тело
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML($isHtml);

            if ($isHtml) {
                $this->mailer->Body = $body;
                $this->mailer->AltBody = strip_tags($body);
            } else {
                $this->mailer->Body = $body;
            }

            // Добавить вложения
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $this->mailer->addAttachment($attachment);
                }
            }

            // Отправить
            $result = $this->mailer->send();

            // Логировать успешную отправку
            $this->logEmail($to, $subject, true);

            return $result;

        } catch (Exception $e) {
            // Логировать ошибку
            $this->logEmail($to, $subject, false, $this->mailer->ErrorInfo);
            error_log("Email send failed: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    /**
     * Отправить письмо с персонализацией
     *
     * @param string $to Email получателя
     * @param string $subject Тема письма
     * @param string $body Тело письма с переменными {variable_name}
     * @param array $variables Массив переменных для замены
     * @param array $attachments Массив путей к вложениям
     * @return bool Успешность отправки
     */
    public function sendPersonalized(
        string $to,
        string $subject,
        string $body,
        array $variables = [],
        array $attachments = []
    ): bool {
        // Заменить переменные в теме и теле
        $personalizedSubject = $this->replaceVariables($subject, $variables);
        $personalizedBody = $this->replaceVariables($body, $variables);

        // Получить имя из переменных
        $toName = null;
        if (isset($variables['first_name'])) {
            $toName = $variables['first_name'];
            if (isset($variables['last_name'])) {
                $toName .= ' ' . $variables['last_name'];
            }
        }

        return $this->send($to, $personalizedSubject, $personalizedBody, $attachments, true, $toName);
    }

    /**
     * Отправить тестовое письмо
     *
     * @param string $to Email получателя
     * @return bool Успешность отправки
     */
    public function sendTest(string $to): bool
    {
        $subject = 'Тестовое письмо - Проверка настроек SMTP';

        $body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .success { color: #4CAF50; font-weight: bold; }
                .footer { padding: 10px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Проверка SMTP</h1>
                </div>
                <div class="content">
                    <p class="success">Поздравляем!</p>
                    <p>Настройки SMTP работают корректно. Это тестовое письмо было успешно доставлено.</p>
                    <h3>Параметры подключения:</h3>
                    <ul>
                        <li><strong>Хост:</strong> ' . $this->config['smtp']['host'] . '</li>
                        <li><strong>Порт:</strong> ' . $this->config['smtp']['port'] . '</li>
                        <li><strong>Шифрование:</strong> ' . $this->config['smtp']['encryption'] . '</li>
                        <li><strong>Отправитель:</strong> ' . $this->config['smtp']['from_email'] . '</li>
                    </ul>
                    <p>Теперь вы можете начать отправку email-рассылок!</p>
                </div>
                <div class="footer">
                    <p>Дата отправки: ' . date('d.m.Y H:i:s') . '</p>
                    <p>Contact Management System</p>
                </div>
            </div>
        </body>
        </html>
        ';

        return $this->send($to, $subject, $body);
    }

    /**
     * Проверить подключение к SMTP серверу
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(): array
    {
        try {
            // Создать новый экземпляр для теста
            $testMailer = new PHPMailer(true);

            if ($this->config['driver'] === 'smtp') {
                $testMailer->isSMTP();
                $testMailer->Host = $this->config['smtp']['host'];
                $testMailer->Port = $this->config['smtp']['port'];
                $testMailer->SMTPAuth = true;
                $testMailer->Username = $this->config['smtp']['username'];
                $testMailer->Password = $this->config['smtp']['password'];
                $testMailer->SMTPSecure = $this->config['smtp']['encryption'];
                $testMailer->Timeout = 10;

                // Попытка подключения
                if (!$testMailer->smtpConnect()) {
                    throw new \Exception('Не удалось подключиться к SMTP серверу');
                }

                $testMailer->smtpClose();

                return [
                    'success' => true,
                    'message' => 'Подключение к SMTP серверу успешно установлено'
                ];
            } else {
                return [
                    'success' => true,
                    'message' => 'Используется стандартная функция mail()'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Логировать отправку email
     *
     * @param string $to Email получателя
     * @param string $subject Тема письма
     * @param bool $success Успешность отправки
     * @param string|null $error Сообщение об ошибке
     */
    private function logEmail(
        string $to,
        string $subject,
        bool $success,
        ?string $error = null
    ): void {
        if (!$this->config['logging']['enabled']) {
            return;
        }

        // Логировать в файл
        $logFile = $this->config['logging']['log_file'];
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logMessage = sprintf(
            "[%s] %s | To: %s | Subject: %s | %s\n",
            date('Y-m-d H:i:s'),
            $success ? 'SUCCESS' : 'FAILED',
            $to,
            $subject,
            $error ?: 'OK'
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Также записать в БД если доступна
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO email_logs
                    (recipient_email, subject, status, error_message, sent_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $to,
                    $subject,
                    $success ? 'sent' : 'failed',
                    $error
                ]);
            } catch (\PDOException $e) {
                // Игнорируем ошибки БД в логировании
                error_log("Failed to log email to database: " . $e->getMessage());
            }
        }
    }

    /**
     * Заменить переменные в тексте
     *
     * @param string $text Текст с переменными {variable_name}
     * @param array $variables Массив переменных ['variable_name' => 'value']
     * @return string Текст с замененными переменными
     */
    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Настроить PHPMailer
     */
    private function setupMailer(): void
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64';

        if ($this->config['driver'] === 'smtp') {
            // SMTP настройки
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp']['host'];
            $this->mailer->Port = $this->config['smtp']['port'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp']['username'];
            $this->mailer->Password = $this->config['smtp']['password'];
            $this->mailer->SMTPSecure = $this->config['smtp']['encryption'];
            $this->mailer->Timeout = $this->config['smtp']['timeout'];

            // Отладка
            if ($this->config['smtp']['debug'] > 0) {
                $this->mailer->SMTPDebug = $this->config['smtp']['debug'];
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("PHPMailer: $str");
                };
            }
        } else {
            // Использовать стандартную функцию mail()
            $this->mailer->isMail();
        }

        // Установить отправителя
        $fromEmail = $this->config['smtp']['from_email'];
        $fromName = $this->config['smtp']['from_name'];
        $this->mailer->setFrom($fromEmail, $fromName);
    }

    /**
     * Инициализировать подключение к БД
     */
    private function initDatabase(): void
    {
        try {
            // Попытка использовать существующее подключение
            if (class_exists('Database')) {
                $dbClass = new \Database();
                $this->db = $dbClass->getConnection();
            } else {
                // Создать новое подключение
                $host = getenv('DB_HOST') ?: 'localhost';
                $dbname = getenv('DB_NAME') ?: 'contacts_db';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASSWORD') ?: '';

                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                $this->db = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            }
        } catch (\PDOException $e) {
            // БД недоступна, будем логировать только в файл
            $this->db = null;
            error_log("Database connection failed in EmailService: " . $e->getMessage());
        }
    }

    /**
     * Валидация email адреса
     *
     * @param string $email Email для проверки
     * @return bool Валидность email
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Получить статистику отправленных писем
     *
     * @param int $days За сколько дней (по умолчанию 30)
     * @return array Статистика
     */
    public function getStatistics(int $days = 30): array
    {
        if (!$this->db) {
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'rate' => 0
            ];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM email_logs
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");

            $stmt->execute([$days]);
            $result = $stmt->fetch();

            $total = (int)$result['total'];
            $sent = (int)$result['sent'];
            $failed = (int)$result['failed'];

            return [
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0
            ];

        } catch (\PDOException $e) {
            error_log("Failed to get email statistics: " . $e->getMessage());
            return [
                'total' => 0,
                'sent' => 0,
                'failed' => 0,
                'rate' => 0
            ];
        }
    }
}
