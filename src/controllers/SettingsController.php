<?php
/**
 * Settings Controller
 */

class SettingsController
{
    /**
     * Display settings page
     */
    public function index()
    {
        $userId = getCurrentUserId();
        $db = Database::getInstance()->getConnection();

        // Get current SMTP settings from user_settings table
        $stmt = $db->prepare("
            SELECT * FROM user_settings
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $settings = $stmt->fetch();

        // If no settings exist, create default ones
        if (!$settings) {
            $settings = [
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_encryption' => 'tls',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_from_email' => '',
                'smtp_from_name' => ''
            ];
        }

        require_once SRC_PATH . '/views/settings/index.php';
    }

    /**
     * Save SMTP settings
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/index.php?page=settings');
            return;
        }

        // Validate CSRF token
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('Invalid security token', 'error');
            redirect('/index.php?page=settings');
            return;
        }

        $userId = getCurrentUserId();
        $db = Database::getInstance()->getConnection();

        // Get form data
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = intval($_POST['smtp_port'] ?? 587);
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';
        $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
        $smtpFromName = trim($_POST['smtp_from_name'] ?? '');

        // Validate required fields
        if (empty($smtpHost) || empty($smtpFromEmail)) {
            setFlashMessage('SMTP Host and From Email are required', 'error');
            redirect('/index.php?page=settings');
            return;
        }

        // Validate email
        if (!filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('Invalid From Email address', 'error');
            redirect('/index.php?page=settings');
            return;
        }

        try {
            // Check if settings exist
            $stmt = $db->prepare("SELECT id FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Update existing settings
                $stmt = $db->prepare("
                    UPDATE user_settings
                    SET smtp_host = ?,
                        smtp_port = ?,
                        smtp_encryption = ?,
                        smtp_username = ?,
                        smtp_password = ?,
                        smtp_from_email = ?,
                        smtp_from_name = ?,
                        updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $smtpHost,
                    $smtpPort,
                    $smtpEncryption,
                    $smtpUsername,
                    $smtpPassword,
                    $smtpFromEmail,
                    $smtpFromName,
                    $userId
                ]);
            } else {
                // Insert new settings
                $stmt = $db->prepare("
                    INSERT INTO user_settings (
                        user_id, smtp_host, smtp_port, smtp_encryption,
                        smtp_username, smtp_password, smtp_from_email, smtp_from_name
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $smtpHost,
                    $smtpPort,
                    $smtpEncryption,
                    $smtpUsername,
                    $smtpPassword,
                    $smtpFromEmail,
                    $smtpFromName
                ]);
            }

            setFlashMessage('SMTP settings saved successfully', 'success');
        } catch (PDOException $e) {
            setFlashMessage('Failed to save settings: ' . $e->getMessage(), 'error');
        }

        redirect('/index.php?page=settings');
    }
}
