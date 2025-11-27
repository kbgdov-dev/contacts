<?php
/**
 * Campaign Model
 */

class Campaign
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new campaign
     */
    public function create($data, $userId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO campaigns (name, subject, body, sender_name, sender_email, status, scheduled_at, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['name'],
            $data['subject'],
            $data['body'],
            $data['sender_name'],
            $data['sender_email'],
            $data['status'] ?? 'draft',
            $data['scheduled_at'] ?? null,
            $userId
        ]);
    }

    /**
     * Get last inserted campaign ID
     */
    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Update campaign
     */
    public function update($id, $data, $userId)
    {
        $stmt = $this->db->prepare("
            UPDATE campaigns
            SET name = ?, subject = ?, body = ?, sender_name = ?, sender_email = ?, status = ?, scheduled_at = ?
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['subject'],
            $data['body'],
            $data['sender_name'],
            $data['sender_email'],
            $data['status'] ?? 'draft',
            $data['scheduled_at'] ?? null,
            $id,
            $userId
        ]);
    }

    /**
     * Find campaign by ID
     */
    public function findById($id, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    /**
     * Get all campaigns
     */
    public function getAll($userId, $orderBy = 'created_at', $orderDir = 'DESC', $limit = 50, $offset = 0)
    {
        $allowedColumns = ['id', 'name', 'status', 'created_at', 'sent_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }

        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $stmt = $this->db->prepare("
            SELECT * FROM campaigns
            WHERE user_id = ?
            ORDER BY $orderBy $orderDir
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get total campaigns count
     */
    public function getTotalCount($userId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM campaigns WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Add recipient to campaign
     */
    public function addRecipient($campaignId, $contactId)
    {
        $trackingToken = generateToken(32);

        $stmt = $this->db->prepare("
            INSERT INTO campaign_recipients (campaign_id, contact_id, tracking_token)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$campaignId, $contactId, $trackingToken]);
    }

    /**
     * Get campaign recipients
     */
    public function getRecipients($campaignId)
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, c.first_name, c.last_name, c.email
            FROM campaign_recipients cr
            JOIN contacts c ON cr.contact_id = c.id
            WHERE cr.campaign_id = ?
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /**
     * Get campaign statistics
     */
    public function getStatistics($campaignId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_recipients,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'opened' OR status = 'clicked' THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked
            FROM campaign_recipients
            WHERE campaign_id = ?
        ");
        $stmt->execute([$campaignId]);
        return $stmt->fetch();
    }

    /**
     * Update recipient status
     */
    public function updateRecipientStatus($recipientId, $status, $errorMessage = null)
    {
        $stmt = $this->db->prepare("
            UPDATE campaign_recipients
            SET status = ?, error_message = ?, sent_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $errorMessage, $recipientId]);
    }

    /**
     * Track email open
     */
    public function trackOpen($trackingToken)
    {
        $stmt = $this->db->prepare("
            UPDATE campaign_recipients
            SET status = 'opened', opened_at = NOW()
            WHERE tracking_token = ? AND status != 'clicked'
        ");
        return $stmt->execute([$trackingToken]);
    }

    /**
     * Track link click
     */
    public function trackClick($trackingToken)
    {
        $stmt = $this->db->prepare("
            UPDATE campaign_recipients
            SET status = 'clicked', clicked_at = NOW()
            WHERE tracking_token = ?
        ");
        return $stmt->execute([$trackingToken]);
    }

    /**
     * Delete campaign
     */
    public function delete($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM campaigns WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Update campaign counters
     */
    public function updateCounters($campaignId)
    {
        $stats = $this->getStatistics($campaignId);

        $stmt = $this->db->prepare("
            UPDATE campaigns
            SET total_recipients = ?, sent_count = ?, failed_count = ?, opened_count = ?, clicked_count = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $stats['total_recipients'],
            $stats['sent'],
            $stats['failed'],
            $stats['opened'],
            $stats['clicked'],
            $campaignId
        ]);
    }
}
