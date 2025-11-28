<?php
/**
 * Queue Service
 *
 * Сервис для управления очередью отправки email
 *
 * @package App\Services
 */

namespace App\Services;

use PDO;
use DateTime;

class QueueService
{
    private $db;
    private $emailService;
    private $config;

    /**
     * Конструктор
     */
    public function __construct()
    {
        // Инициализировать подключение к БД
        $this->initDatabase();

        // Загрузить конфигурацию
        $configPath = __DIR__ . '/../../config/mail.php';
        if (!file_exists($configPath)) {
            throw new \Exception('Mail configuration file not found');
        }
        $this->config = require $configPath;

        // Инициализировать EmailService
        $this->emailService = new EmailService();
    }

    /**
     * Добавить письмо в очередь
     *
     * @param string $recipientEmail Email получателя
     * @param string $subject Тема письма
     * @param string $body Тело письма (HTML)
     * @param array $variables Переменные для персонализации
     * @param int|null $campaignId ID кампании
     * @param int|null $contactId ID контакта
     * @param DateTime|null $scheduledAt Время запланированной отправки
     * @param int $priority Приоритет (по умолчанию 0)
     * @return int ID записи в очереди
     */
    public function add(
        string $recipientEmail,
        string $subject,
        string $body,
        array $variables = [],
        ?int $campaignId = null,
        ?int $contactId = null,
        ?DateTime $scheduledAt = null,
        int $priority = 0
    ): int {
        // Если время не указано, отправлять сейчас
        if ($scheduledAt === null) {
            $scheduledAt = new DateTime();
        }

        // Получить имя получателя из переменных
        $recipientName = null;
        if (!empty($variables)) {
            if (isset($variables['first_name'])) {
                $recipientName = $variables['first_name'];
                if (isset($variables['last_name'])) {
                    $recipientName .= ' ' . $variables['last_name'];
                }
            }
        }

        // Вставить в очередь
        $stmt = $this->db->prepare("
            INSERT INTO email_queue
            (campaign_id, contact_id, recipient_email, recipient_name, subject, body,
             variables, status, priority, scheduled_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
        ");

        $stmt->execute([
            $campaignId,
            $contactId,
            $recipientEmail,
            $recipientName,
            $subject,
            $body,
            !empty($variables) ? json_encode($variables) : null,
            $priority,
            $scheduledAt->format('Y-m-d H:i:s')
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Добавить массово (для рассылки)
     *
     * @param array $recipients Массив получателей
     * @param string $subject Тема письма
     * @param string $body Тело письма
     * @param int|null $campaignId ID кампании
     * @param DateTime|null $scheduledAt Время запланированной отправки
     * @return int Количество добавленных
     */
    public function addBulk(
        array $recipients,
        string $subject,
        string $body,
        ?int $campaignId = null,
        ?DateTime $scheduledAt = null
    ): int {
        if (empty($recipients)) {
            return 0;
        }

        // Если время не указано, отправлять сейчас
        if ($scheduledAt === null) {
            $scheduledAt = new DateTime();
        }

        $added = 0;
        $scheduledAtStr = $scheduledAt->format('Y-m-d H:i:s');

        // Начать транзакцию для производительности
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_queue
                (campaign_id, contact_id, recipient_email, recipient_name, subject, body,
                 variables, status, priority, scheduled_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, ?, NOW())
            ");

            foreach ($recipients as $recipient) {
                $email = $recipient['email'] ?? null;
                $contactId = $recipient['contact_id'] ?? $recipient['id'] ?? null;
                $name = null;
                $variables = [];

                // Построить имя и переменные
                if (isset($recipient['first_name'])) {
                    $variables['first_name'] = $recipient['first_name'];
                    $name = $recipient['first_name'];

                    if (isset($recipient['last_name'])) {
                        $variables['last_name'] = $recipient['last_name'];
                        $name .= ' ' . $recipient['last_name'];
                    }
                }

                // Добавить все поля получателя как переменные
                foreach ($recipient as $key => $value) {
                    if (!in_array($key, ['id', 'password', 'reset_token'])) {
                        $variables[$key] = $value;
                    }
                }

                if ($email) {
                    $stmt->execute([
                        $campaignId,
                        $contactId,
                        $email,
                        $name,
                        $subject,
                        $body,
                        json_encode($variables),
                        $scheduledAtStr
                    ]);
                    $added++;
                }
            }

            $this->db->commit();

            // Обновить счетчик получателей в кампании
            if ($campaignId) {
                $this->updateCampaignRecipientCount($campaignId);
            }

            return $added;

        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Failed to add bulk emails to queue: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Обработать очередь (отправить накопившиеся письма)
     *
     * @param int $limit Максимальное количество писем за раз
     * @return array Статистика ['sent' => count, 'failed' => count]
     */
    public function process(int $limit = 50): array
    {
        $stats = ['sent' => 0, 'failed' => 0];

        if (!$this->config['queue']['enabled']) {
            return $stats;
        }

        $batchSize = min($limit, $this->config['queue']['batch_size']);

        // Получить письма из очереди
        $emails = $this->getNextBatch($batchSize);

        if (empty($emails)) {
            return $stats;
        }

        $delayBetweenEmails = $this->config['queue']['delay_between_emails'];

        foreach ($emails as $email) {
            // Проверить rate limit
            if ($this->config['rate_limit']['enabled']) {
                if (!$this->checkRateLimit()) {
                    error_log("Rate limit reached, stopping queue processing");
                    break;
                }
            }

            // Обработать письмо
            if ($this->processEmail($email)) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }

            // Задержка между письмами
            if ($delayBetweenEmails > 0) {
                sleep($delayBetweenEmails);
            }
        }

        return $stats;
    }

    /**
     * Отменить письма в очереди
     *
     * @param int $campaignId ID кампании
     * @return int Количество отмененных
     */
    public function cancel(int $campaignId): int
    {
        $stmt = $this->db->prepare("
            UPDATE email_queue
            SET status = 'cancelled', updated_at = NOW()
            WHERE campaign_id = ? AND status = 'pending'
        ");

        $stmt->execute([$campaignId]);

        return $stmt->rowCount();
    }

    /**
     * Получить статистику очереди
     *
     * @return array Статистика
     */
    public function getStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                status,
                COUNT(*) as count
            FROM email_queue
            GROUP BY status
        ");

        $results = $stmt->fetchAll();
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'total' => 0
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Получить статистику по кампании
     *
     * @param int $campaignId ID кампании
     * @return array Статистика
     */
    public function getCampaignStats(int $campaignId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                status,
                COUNT(*) as count
            FROM email_queue
            WHERE campaign_id = ?
            GROUP BY status
        ");

        $stmt->execute([$campaignId]);
        $results = $stmt->fetchAll();

        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'total' => 0
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    /**
     * Очистить старые записи
     *
     * @param int $daysOld Возраст записей в днях
     * @return int Количество удаленных
     */
    public function cleanup(int $daysOld = 30): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM email_queue
            WHERE status IN ('sent', 'failed', 'cancelled')
            AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->execute([$daysOld]);

        return $stmt->rowCount();
    }

    /**
     * Повторить неудачную отправку
     *
     * @param int $emailQueueId ID записи в очереди
     * @return bool Успешность
     */
    public function retry(int $emailQueueId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE email_queue
            SET status = 'pending', attempts = 0, error_message = NULL, updated_at = NOW()
            WHERE id = ? AND status = 'failed'
        ");

        $stmt->execute([$emailQueueId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Повторить все неудачные отправки для кампании
     *
     * @param int $campaignId ID кампании
     * @return int Количество повторных попыток
     */
    public function retryFailed(int $campaignId): int
    {
        $stmt = $this->db->prepare("
            UPDATE email_queue
            SET status = 'pending', attempts = 0, error_message = NULL, updated_at = NOW()
            WHERE campaign_id = ? AND status = 'failed'
        ");

        $stmt->execute([$campaignId]);

        return $stmt->rowCount();
    }

    /**
     * Получить следующую партию писем для отправки
     *
     * @param int $limit Лимит
     * @return array Массив писем
     */
    private function getNextBatch(int $limit): array
    {
        $maxAttempts = $this->config['queue']['max_attempts'];

        // Заблокировать записи для обработки
        $stmt = $this->db->prepare("
            UPDATE email_queue
            SET status = 'processing', updated_at = NOW()
            WHERE status = 'pending'
            AND scheduled_at <= NOW()
            AND attempts < ?
            ORDER BY priority DESC, scheduled_at ASC
            LIMIT ?
        ");

        $stmt->execute([$maxAttempts, $limit]);

        // Получить заблокированные записи
        $stmt = $this->db->prepare("
            SELECT *
            FROM email_queue
            WHERE status = 'processing'
            ORDER BY priority DESC, scheduled_at ASC
            LIMIT ?
        ");

        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Обработать одно письмо
     *
     * @param array $queueItem Запись из очереди
     * @return bool Успешность
     */
    private function processEmail(array $queueItem): bool
    {
        $id = $queueItem['id'];

        try {
            // Инкрементировать счетчик попыток
            $this->incrementAttempts($id);

            // Получить переменные
            $variables = [];
            if (!empty($queueItem['variables'])) {
                $variables = json_decode($queueItem['variables'], true) ?? [];
            }

            // Отправить письмо
            $result = $this->emailService->sendPersonalized(
                $queueItem['recipient_email'],
                $queueItem['subject'],
                $queueItem['body'],
                $variables
            );

            if ($result) {
                // Успешная отправка
                $this->updateStatus($id, 'sent');

                // Обновить статистику кампании
                if ($queueItem['campaign_id']) {
                    $this->updateCampaignStats($queueItem['campaign_id'], 'sent');
                }

                return true;
            } else {
                // Неудачная отправка
                $errorMessage = "Email sending failed";

                // Проверить, достигнут ли лимит попыток
                if ($queueItem['attempts'] + 1 >= $queueItem['max_attempts']) {
                    $this->updateStatus($id, 'failed', $errorMessage);

                    // Обновить статистику кампании
                    if ($queueItem['campaign_id']) {
                        $this->updateCampaignStats($queueItem['campaign_id'], 'failed');
                    }
                } else {
                    // Вернуть в очередь для повторной попытки
                    $this->updateStatus($id, 'pending', $errorMessage);
                }

                return false;
            }

        } catch (\Exception $e) {
            error_log("Error processing email queue item {$id}: " . $e->getMessage());

            // Обновить статус с ошибкой
            if ($queueItem['attempts'] + 1 >= $queueItem['max_attempts']) {
                $this->updateStatus($id, 'failed', $e->getMessage());

                if ($queueItem['campaign_id']) {
                    $this->updateCampaignStats($queueItem['campaign_id'], 'failed');
                }
            } else {
                $this->updateStatus($id, 'pending', $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Обновить статус записи в очереди
     *
     * @param int $id ID записи
     * @param string $status Новый статус
     * @param string|null $error Сообщение об ошибке
     */
    private function updateStatus(int $id, string $status, ?string $error = null): void
    {
        if ($status === 'sent') {
            $stmt = $this->db->prepare("
                UPDATE email_queue
                SET status = ?, sent_at = NOW(), error_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
        } else {
            $stmt = $this->db->prepare("
                UPDATE email_queue
                SET status = ?, error_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
        }

        $stmt->execute([$status, $error, $id]);
    }

    /**
     * Инкрементировать счетчик попыток
     *
     * @param int $id ID записи
     */
    private function incrementAttempts(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE email_queue
            SET attempts = attempts + 1
            WHERE id = ?
        ");

        $stmt->execute([$id]);
    }

    /**
     * Обновить счетчик получателей в кампании
     *
     * @param int $campaignId ID кампании
     */
    private function updateCampaignRecipientCount(int $campaignId): void
    {
        $stmt = $this->db->prepare("
            UPDATE campaigns
            SET total_recipients = (
                SELECT COUNT(*) FROM email_queue WHERE campaign_id = ?
            )
            WHERE id = ?
        ");

        $stmt->execute([$campaignId, $campaignId]);
    }

    /**
     * Обновить статистику кампании
     *
     * @param int $campaignId ID кампании
     * @param string $type Тип ('sent' или 'failed')
     */
    private function updateCampaignStats(int $campaignId, string $type): void
    {
        if ($type === 'sent') {
            $stmt = $this->db->prepare("
                UPDATE campaigns
                SET sent_count = sent_count + 1,
                    sent_at = CASE WHEN sent_at IS NULL THEN NOW() ELSE sent_at END
                WHERE id = ?
            ");
        } else {
            $stmt = $this->db->prepare("
                UPDATE campaigns
                SET failed_count = failed_count + 1
                WHERE id = ?
            ");
        }

        $stmt->execute([$campaignId]);
    }

    /**
     * Проверить rate limit
     *
     * @return bool Можно ли отправлять
     */
    private function checkRateLimit(): bool
    {
        $maxPerHour = $this->config['rate_limit']['max_per_hour'];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM email_logs
            WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND status = 'sent'
        ");

        $stmt->execute();
        $result = $stmt->fetch();

        return ($result['count'] < $maxPerHour);
    }

    /**
     * Инициализировать подключение к БД
     */
    private function initDatabase(): void
    {
        try {
            // Попытка использовать существующее подключение
            if (class_exists('Database')) {
                $dbClass = \Database::getInstance();
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
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
}
