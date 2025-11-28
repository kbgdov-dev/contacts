<?php
/**
 * Tracking Service
 *
 * Сервис для отслеживания открытий и кликов по email
 *
 * @package App\Services
 */

namespace App\Services;

use PDO;

class TrackingService
{
    private $db;
    private $config;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->initDatabase();

        // Загрузить конфигурацию
        $configPath = __DIR__ . '/../../config/mail.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        } else {
            $this->config = ['tracking' => ['enabled' => true, 'base_url' => 'http://localhost']];
        }
    }

    /**
     * Добавить tracking pixel в HTML письма
     *
     * @param string $htmlBody Тело письма (HTML)
     * @param string $trackingToken Токен отслеживания
     * @return string HTML с tracking pixel
     */
    public function addTrackingPixel(string $htmlBody, string $trackingToken): string
    {
        if (!$this->config['tracking']['enabled'] || !$this->config['tracking']['pixel_enabled']) {
            return $htmlBody;
        }

        $baseUrl = $this->config['tracking']['base_url'];
        $pixelUrl = $baseUrl . '/track.php?t=' . $trackingToken;

        // Добавить pixel перед закрывающим тегом </body>
        $pixel = '<img src="' . $pixelUrl . '" width="1" height="1" border="0" alt="" style="display:none;" />';

        if (stripos($htmlBody, '</body>') !== false) {
            $htmlBody = str_ireplace('</body>', $pixel . '</body>', $htmlBody);
        } else {
            $htmlBody .= $pixel;
        }

        return $htmlBody;
    }

    /**
     * Заменить ссылки на tracking ссылки
     *
     * @param string $htmlBody Тело письма (HTML)
     * @param int $emailLogId ID записи в email_logs
     * @return string HTML с tracking ссылками
     */
    public function replaceLinksWithTracking(string $htmlBody, int $emailLogId): string
    {
        if (!$this->config['tracking']['enabled'] || !$this->config['tracking']['link_enabled']) {
            return $htmlBody;
        }

        $baseUrl = $this->config['tracking']['base_url'];

        // Найти все ссылки
        preg_match_all('/<a\s+[^>]*href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', $htmlBody, $matches);

        if (empty($matches[1])) {
            return $htmlBody;
        }

        $urls = array_unique($matches[1]);

        foreach ($urls as $originalUrl) {
            // Создать tracking токен для ссылки
            $trackingToken = $this->generateToken();

            // Сохранить в БД
            $this->saveLinkTracking($emailLogId, $originalUrl, $trackingToken);

            // Создать tracking URL
            $trackingUrl = $baseUrl . '/click.php?t=' . $trackingToken;

            // Заменить в HTML
            $htmlBody = str_replace('href="' . $originalUrl . '"', 'href="' . $trackingUrl . '"', $htmlBody);
            $htmlBody = str_replace("href='" . $originalUrl . "'", "href='" . $trackingUrl . "'", $htmlBody);
        }

        return $htmlBody;
    }

    /**
     * Сгенерировать уникальный token
     *
     * @return string Токен
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Зарегистрировать открытие письма
     *
     * @param string $token Токен отслеживания
     * @param string $userAgent User Agent
     * @param string $ip IP адрес
     * @return bool Успешность
     */
    public function recordOpen(string $token, string $userAgent, string $ip): bool
    {
        try {
            // Найти запись в email_tracking
            $stmt = $this->db->prepare("
                SELECT id, opened, open_count
                FROM email_tracking
                WHERE tracking_token = ?
            ");

            $stmt->execute([$token]);
            $tracking = $stmt->fetch();

            if (!$tracking) {
                return false;
            }

            // Обновить статистику
            if ($tracking['opened']) {
                // Уже было открыто, просто увеличить счетчик
                $stmt = $this->db->prepare("
                    UPDATE email_tracking
                    SET open_count = open_count + 1,
                        last_opened_at = NOW(),
                        user_agent = ?,
                        ip_address = ?
                    WHERE id = ?
                ");

                $stmt->execute([$userAgent, $ip, $tracking['id']]);
            } else {
                // Первое открытие
                $stmt = $this->db->prepare("
                    UPDATE email_tracking
                    SET opened = TRUE,
                        open_count = 1,
                        first_opened_at = NOW(),
                        last_opened_at = NOW(),
                        user_agent = ?,
                        ip_address = ?
                    WHERE id = ?
                ");

                $stmt->execute([$userAgent, $ip, $tracking['id']]);

                // Обновить email_logs
                $stmt = $this->db->prepare("
                    UPDATE email_logs
                    SET opened_at = NOW()
                    WHERE id = (SELECT email_log_id FROM email_tracking WHERE id = ?)
                    AND opened_at IS NULL
                ");

                $stmt->execute([$tracking['id']]);
            }

            return true;

        } catch (\PDOException $e) {
            error_log("Failed to record email open: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Зарегистрировать клик по ссылке
     *
     * @param string $token Токен отслеживания
     * @param string $userAgent User Agent
     * @param string $ip IP адрес
     * @return string|null URL для редиректа
     */
    public function recordClick(string $token, string $userAgent, string $ip): ?string
    {
        try {
            // Найти запись в email_link_tracking
            $stmt = $this->db->prepare("
                SELECT id, email_log_id, original_url, click_count
                FROM email_link_tracking
                WHERE tracking_token = ?
            ");

            $stmt->execute([$token]);
            $tracking = $stmt->fetch();

            if (!$tracking) {
                return null;
            }

            // Обновить статистику
            if ($tracking['click_count'] > 0) {
                // Уже был клик, просто увеличить счетчик
                $stmt = $this->db->prepare("
                    UPDATE email_link_tracking
                    SET click_count = click_count + 1,
                        last_clicked_at = NOW(),
                        user_agent = ?,
                        ip_address = ?
                    WHERE id = ?
                ");

                $stmt->execute([$userAgent, $ip, $tracking['id']]);
            } else {
                // Первый клик
                $stmt = $this->db->prepare("
                    UPDATE email_link_tracking
                    SET click_count = 1,
                        first_clicked_at = NOW(),
                        last_clicked_at = NOW(),
                        user_agent = ?,
                        ip_address = ?
                    WHERE id = ?
                ");

                $stmt->execute([$userAgent, $ip, $tracking['id']]);

                // Обновить email_logs
                $stmt = $this->db->prepare("
                    UPDATE email_logs
                    SET clicked_at = NOW()
                    WHERE id = ?
                    AND clicked_at IS NULL
                ");

                $stmt->execute([$tracking['email_log_id']]);
            }

            return $tracking['original_url'];

        } catch (\PDOException $e) {
            error_log("Failed to record link click: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создать запись отслеживания для email
     *
     * @param int $emailLogId ID записи в email_logs
     * @return string Токен отслеживания
     */
    public function createTracking(int $emailLogId): string
    {
        $token = $this->generateToken();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_tracking
                (email_log_id, tracking_token)
                VALUES (?, ?)
            ");

            $stmt->execute([$emailLogId, $token]);

            return $token;

        } catch (\PDOException $e) {
            error_log("Failed to create tracking: " . $e->getMessage());
            return $token;
        }
    }

    /**
     * Сохранить tracking для ссылки
     *
     * @param int $emailLogId ID записи в email_logs
     * @param string $originalUrl Оригинальный URL
     * @param string $trackingToken Токен отслеживания
     */
    private function saveLinkTracking(int $emailLogId, string $originalUrl, string $trackingToken): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_link_tracking
                (email_log_id, original_url, tracking_token)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([$emailLogId, $originalUrl, $trackingToken]);

        } catch (\PDOException $e) {
            error_log("Failed to save link tracking: " . $e->getMessage());
        }
    }

    /**
     * Получить статистику по tracking
     *
     * @param int $campaignId ID кампании
     * @return array Статистика
     */
    public function getCampaignTrackingStats(int $campaignId): array
    {
        try {
            // Статистика открытий
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(DISTINCT el.id) as total_emails,
                    COUNT(DISTINCT CASE WHEN el.opened_at IS NOT NULL THEN el.id END) as opened_emails,
                    COUNT(DISTINCT CASE WHEN el.clicked_at IS NOT NULL THEN el.id END) as clicked_emails,
                    SUM(et.open_count) as total_opens,
                    SUM(elt.click_count) as total_clicks
                FROM email_logs el
                LEFT JOIN email_tracking et ON et.email_log_id = el.id
                LEFT JOIN email_link_tracking elt ON elt.email_log_id = el.id
                WHERE el.campaign_id = ?
            ");

            $stmt->execute([$campaignId]);
            $result = $stmt->fetch();

            $totalEmails = (int)$result['total_emails'];
            $openedEmails = (int)$result['opened_emails'];
            $clickedEmails = (int)$result['clicked_emails'];

            return [
                'total_emails' => $totalEmails,
                'opened_emails' => $openedEmails,
                'clicked_emails' => $clickedEmails,
                'total_opens' => (int)$result['total_opens'],
                'total_clicks' => (int)$result['total_clicks'],
                'open_rate' => $totalEmails > 0 ? round(($openedEmails / $totalEmails) * 100, 2) : 0,
                'click_rate' => $totalEmails > 0 ? round(($clickedEmails / $totalEmails) * 100, 2) : 0,
            ];

        } catch (\PDOException $e) {
            error_log("Failed to get tracking stats: " . $e->getMessage());
            return [
                'total_emails' => 0,
                'opened_emails' => 0,
                'clicked_emails' => 0,
                'total_opens' => 0,
                'total_clicks' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
            ];
        }
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
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
}
