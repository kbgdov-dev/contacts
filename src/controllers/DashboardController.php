<?php
/**
 * Dashboard Controller
 */

require_once SRC_PATH . '/models/Contact.php';

class DashboardController
{
    private $contactModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
    }

    /**
     * Dashboard index
     */
    public function index()
    {
        $userId = getCurrentUserId();
        $db = Database::getInstance()->getConnection();

        // Get contact statistics
        $contactStats = $this->contactModel->getStats($userId);

        // Get campaign statistics
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'sending' THEN 1 ELSE 0 END) as sending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM campaigns
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $campaignStats = $stmt->fetch();

        // Get recent contacts (last 5)
        $recentContacts = $this->contactModel->getAll($userId, [], 'created_at', 'DESC', 5, 0);

        // Get recent campaigns (last 5)
        $stmt = $db->prepare("
            SELECT * FROM campaigns
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentCampaigns = $stmt->fetchAll();

        // Get growth data for the last 7 days
        $stmt = $db->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM contacts
            WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$userId]);
        $growthData = $stmt->fetchAll();

        require_once SRC_PATH . '/views/dashboard/index.php';
    }
}
