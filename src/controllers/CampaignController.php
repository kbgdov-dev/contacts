<?php
/**
 * Campaign Controller
 */

require_once SRC_PATH . '/models/Campaign.php';
require_once SRC_PATH . '/models/Contact.php';

// Подключить сервисы
require_once SRC_PATH . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Services\EmailService;

class CampaignController
{
    private $campaignModel;
    private $contactModel;
    private $queueService;
    private $emailService;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
        $this->contactModel = new Contact();
        $this->queueService = new QueueService();
        $this->emailService = new EmailService();
    }

    /**
     * List all campaigns
     */
    public function index()
    {
        $userId = getCurrentUserId();

        $page = max(1, intval($_GET['page'] ?? 1));
        $itemsPerPage = DEFAULT_ITEMS_PER_PAGE;

        $totalCampaigns = $this->campaignModel->getTotalCount($userId);
        $pagination = getPagination($totalCampaigns, $page, $itemsPerPage);

        $campaigns = $this->campaignModel->getAll(
            $userId,
            'created_at',
            'DESC',
            $pagination['items_per_page'],
            $pagination['offset']
        );

        require_once SRC_PATH . '/views/campaigns/index.php';
    }

    /**
     * Create new campaign
     */
    public function create()
    {
        $userId = getCurrentUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/index.php?page=campaign-create');
            }

            $data = [
                'name' => sanitize($_POST['name'] ?? ''),
                'subject' => sanitize($_POST['subject'] ?? ''),
                'body' => $_POST['body'] ?? '', // Don't sanitize HTML body
                'sender_name' => sanitize($_POST['sender_name'] ?? ''),
                'sender_email' => sanitize($_POST['sender_email'] ?? ''),
                'status' => 'draft'
            ];

            $errors = $this->validateCampaignData($data);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    setFlashMessage('error', $error);
                }
                require_once SRC_PATH . '/views/campaigns/create.php';
                return;
            }

            if ($this->campaignModel->create($data, $userId)) {
                $campaignId = $this->campaignModel->getLastInsertId();

                logActivity('campaign_created', 'campaign', $campaignId, 'Created campaign: ' . $data['name']);
                setFlashMessage('success', 'Campaign created successfully. Now add recipients.');
                redirect('/index.php?page=campaign-edit&id=' . $campaignId);
            } else {
                setFlashMessage('error', 'Failed to create campaign.');
                require_once SRC_PATH . '/views/campaigns/create.php';
            }
        } else {
            // Default sender info from user
            $user = (new User())->findById($userId);
            $defaultSenderName = $user['first_name'] . ' ' . $user['last_name'];
            $defaultSenderEmail = $user['email'];

            require_once SRC_PATH . '/views/campaigns/create.php';
        }
    }

    /**
     * Edit campaign
     */
    public function edit()
    {
        $userId = getCurrentUserId();
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            setFlashMessage('error', 'Invalid campaign ID.');
            redirect('/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/index.php?page=campaigns');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'update';

            if ($action === 'add_recipients') {
                $this->addRecipients($id);
                redirect('/index.php?page=campaign-edit&id=' . $id);
            } else {
                $csrfToken = $_POST['csrf_token'] ?? '';

                if (!verifyCsrfToken($csrfToken)) {
                    setFlashMessage('error', 'Invalid request. Please try again.');
                    redirect('/index.php?page=campaign-edit&id=' . $id);
                }

                $data = [
                    'name' => sanitize($_POST['name'] ?? ''),
                    'subject' => sanitize($_POST['subject'] ?? ''),
                    'body' => $_POST['body'] ?? '',
                    'sender_name' => sanitize($_POST['sender_name'] ?? ''),
                    'sender_email' => sanitize($_POST['sender_email'] ?? ''),
                    'status' => $campaign['status'] // Keep current status
                ];

                $errors = $this->validateCampaignData($data);

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        setFlashMessage('error', $error);
                    }
                    require_once SRC_PATH . '/views/campaigns/edit.php';
                    return;
                }

                if ($this->campaignModel->update($id, $data, $userId)) {
                    logActivity('campaign_updated', 'campaign', $id, 'Updated campaign: ' . $data['name']);
                    setFlashMessage('success', 'Campaign updated successfully.');
                    redirect('/index.php?page=campaign-edit&id=' . $id);
                } else {
                    setFlashMessage('error', 'Failed to update campaign.');
                    require_once SRC_PATH . '/views/campaigns/edit.php';
                }
            }
        } else {
            // Get recipients
            $recipients = $this->campaignModel->getRecipients($id);
            $stats = $this->campaignModel->getStatistics($id);

            // Get all contacts for adding
            $contacts = $this->contactModel->getAll($userId, [], 'first_name', 'ASC', 1000, 0);

            require_once SRC_PATH . '/views/campaigns/edit.php';
        }
    }

    /**
     * View campaign details
     */
    public function view()
    {
        $userId = getCurrentUserId();
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            setFlashMessage('error', 'Invalid campaign ID.');
            redirect('/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/index.php?page=campaigns');
        }

        $recipients = $this->campaignModel->getRecipients($id);
        $stats = $this->campaignModel->getStatistics($id);

        require_once SRC_PATH . '/views/campaigns/view.php';
    }

    /**
     * Send campaign
     */
    public function send()
    {
        $userId = getCurrentUserId();
        $id = intval($_POST['campaign_id'] ?? 0);

        if (!$id) {
            setFlashMessage('error', 'Invalid campaign ID.');
            redirect('/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/index.php?page=campaigns');
        }

        $recipients = $this->campaignModel->getRecipients($id);

        if (empty($recipients)) {
            setFlashMessage('error', 'No recipients found. Please add recipients first.');
            redirect('/index.php?page=campaign-edit&id=' . $id);
        }

        // Подготовить получателей для очереди
        $recipientsData = [];
        foreach ($recipients as $recipient) {
            // Получить полные данные контакта
            $contact = $this->contactModel->findById($recipient['contact_id'], $userId);
            if ($contact && $contact['status'] !== 'unsubscribed') {
                $recipientsData[] = $contact;
            }
        }

        if (empty($recipientsData)) {
            setFlashMessage('error', 'No active recipients found.');
            redirect('/index.php?page=campaign-edit&id=' . $id);
        }

        try {
            // Добавить всех получателей в очередь
            $added = $this->queueService->addBulk(
                $recipientsData,
                $campaign['subject'],
                $campaign['body'],
                $id,
                !empty($campaign['scheduled_at']) ? new DateTime($campaign['scheduled_at']) : null
            );

            // Обновить статус кампании
            $newStatus = !empty($campaign['scheduled_at']) ? 'scheduled' : 'sending';
            $this->campaignModel->update($id, array_merge($campaign, ['status' => $newStatus]), $userId);

            logActivity('campaign_sent', 'campaign', $id, 'Queued campaign: ' . $campaign['name']);
            setFlashMessage('success', "Campaign queued successfully. {$added} emails added to queue.");
            redirect('/index.php?page=campaign-view&id=' . $id);

        } catch (Exception $e) {
            error_log("Failed to queue campaign: " . $e->getMessage());
            setFlashMessage('error', 'Failed to queue campaign for sending.');
            redirect('/index.php?page=campaign-edit&id=' . $id);
        }
    }

    /**
     * Send test email
     */
    public function sendTest()
    {
        $userId = getCurrentUserId();
        $id = intval($_POST['campaign_id'] ?? 0);
        $testEmail = sanitize($_POST['test_email'] ?? '');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID']);
            return;
        }

        if (empty($testEmail) || !isValidEmail($testEmail)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            return;
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            echo json_encode(['success' => false, 'message' => 'Campaign not found']);
            return;
        }

        try {
            // Подготовить тестовые переменные
            $variables = [
                'first_name' => 'Тест',
                'last_name' => 'Тестович',
                'email' => $testEmail,
                'company' => 'Тестовая Компания'
            ];

            // Отправить тестовое письмо
            $result = $this->emailService->sendPersonalized(
                $testEmail,
                '[ТЕСТ] ' . $campaign['subject'],
                $campaign['body'],
                $variables
            );

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $testEmail
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send test email. Check SMTP settings.'
                ]);
            }

        } catch (Exception $e) {
            error_log("Failed to send test email: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get queue status for campaign
     */
    public function getQueueStatus()
    {
        $userId = getCurrentUserId();
        $id = intval($_GET['campaign_id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID']);
            return;
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            echo json_encode(['success' => false, 'message' => 'Campaign not found']);
            return;
        }

        try {
            $stats = $this->queueService->getCampaignStats($id);
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel pending emails in queue
     */
    public function cancelQueue()
    {
        $userId = getCurrentUserId();
        $id = intval($_POST['campaign_id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid campaign ID']);
            return;
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            echo json_encode(['success' => false, 'message' => 'Campaign not found']);
            return;
        }

        try {
            $cancelled = $this->queueService->cancel($id);

            // Обновить статус кампании
            $this->campaignModel->update($id, array_merge($campaign, ['status' => 'cancelled']), $userId);

            echo json_encode([
                'success' => true,
                'message' => "Cancelled {$cancelled} pending emails",
                'cancelled' => $cancelled
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add recipients to campaign
     */
    private function addRecipients($campaignId)
    {
        $userId = getCurrentUserId();
        $recipientType = $_POST['recipient_type'] ?? 'all';

        $contacts = [];

        switch ($recipientType) {
            case 'all':
                $contacts = $this->contactModel->getAll($userId, ['status' => 'active'], 'id', 'ASC', 10000, 0);
                break;

            case 'selected':
                $selectedIds = $_POST['selected_contacts'] ?? [];
                foreach ($selectedIds as $contactId) {
                    $contact = $this->contactModel->findById(intval($contactId), $userId);
                    if ($contact && $contact['status'] === 'active') {
                        $contacts[] = $contact;
                    }
                }
                break;

            case 'tags':
                $tags = sanitize($_POST['tags'] ?? '');
                if (!empty($tags)) {
                    $contacts = $this->contactModel->getAll($userId, ['status' => 'active', 'tags' => $tags], 'id', 'ASC', 10000, 0);
                }
                break;
        }

        $added = 0;
        foreach ($contacts as $contact) {
            if ($this->campaignModel->addRecipient($campaignId, $contact['id'])) {
                $added++;
            }
        }

        setFlashMessage('success', "Added $added recipients to campaign.");
    }

    /**
     * Validate campaign data
     */
    private function validateCampaignData($data)
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Campaign name is required.';
        }

        if (empty($data['subject'])) {
            $errors[] = 'Email subject is required.';
        }

        if (empty($data['body'])) {
            $errors[] = 'Email body is required.';
        }

        if (empty($data['sender_name'])) {
            $errors[] = 'Sender name is required.';
        }

        if (empty($data['sender_email']) || !isValidEmail($data['sender_email'])) {
            $errors[] = 'Valid sender email is required.';
        }

        return $errors;
    }
}
