<?php
/**
 * Campaign Controller
 */

require_once SRC_PATH . '/models/Campaign.php';
require_once SRC_PATH . '/models/Contact.php';

class CampaignController
{
    private $campaignModel;
    private $contactModel;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
        $this->contactModel = new Contact();
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
                redirect('/public/index.php?page=campaign-create');
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
                redirect('/public/index.php?page=campaign-edit&id=' . $campaignId);
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
            redirect('/public/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/public/index.php?page=campaigns');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'update';

            if ($action === 'add_recipients') {
                $this->addRecipients($id);
                redirect('/public/index.php?page=campaign-edit&id=' . $id);
            } else {
                $csrfToken = $_POST['csrf_token'] ?? '';

                if (!verifyCsrfToken($csrfToken)) {
                    setFlashMessage('error', 'Invalid request. Please try again.');
                    redirect('/public/index.php?page=campaign-edit&id=' . $id);
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
                    redirect('/public/index.php?page=campaign-edit&id=' . $id);
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
            redirect('/public/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/public/index.php?page=campaigns');
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
            redirect('/public/index.php?page=campaigns');
        }

        $campaign = $this->campaignModel->findById($id, $userId);

        if (!$campaign) {
            setFlashMessage('error', 'Campaign not found.');
            redirect('/public/index.php?page=campaigns');
        }

        $recipients = $this->campaignModel->getRecipients($id);

        if (empty($recipients)) {
            setFlashMessage('error', 'No recipients found. Please add recipients first.');
            redirect('/public/index.php?page=campaign-edit&id=' . $id);
        }

        // NOTE: This is a simplified version. In production, you would:
        // 1. Use a queue system (e.g., Redis, RabbitMQ)
        // 2. Send emails in background using a worker
        // 3. Implement rate limiting to avoid being blacklisted
        //
        // For now, we'll just mark it as sent

        $stmt = $this->campaignModel->update($id, array_merge($campaign, ['status' => 'sent']), $userId);

        logActivity('campaign_sent', 'campaign', $id, 'Sent campaign: ' . $campaign['name']);
        setFlashMessage('success', 'Campaign queued for sending. Recipients: ' . count($recipients));
        redirect('/public/index.php?page=campaign-view&id=' . $id);
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
