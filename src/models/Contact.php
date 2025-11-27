<?php
/**
 * Contact Model
 */

class Contact
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new contact
     */
    public function create($data, $userId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO contacts (first_name, last_name, middle_name, email, phone, company, position, tags, status, notes, unsubscribe_token, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $unsubscribeToken = generateToken(32);

        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'] ?? null,
            $data['email'],
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['position'] ?? null,
            $data['tags'] ?? null,
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
            $unsubscribeToken,
            $userId
        ]);
    }

    /**
     * Update contact
     */
    public function update($id, $data, $userId)
    {
        $stmt = $this->db->prepare("
            UPDATE contacts
            SET first_name = ?, last_name = ?, middle_name = ?, email = ?, phone = ?,
                company = ?, position = ?, tags = ?, status = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ");

        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'] ?? null,
            $data['email'],
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['position'] ?? null,
            $data['tags'] ?? null,
            $data['status'] ?? 'active',
            $data['notes'] ?? null,
            $id,
            $userId
        ]);
    }

    /**
     * Delete contact
     */
    public function delete($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM contacts WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Find contact by ID
     */
    public function findById($id, $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM contacts WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        return $stmt->fetch();
    }

    /**
     * Get all contacts with filters
     */
    public function getAll($userId, $filters = [], $orderBy = 'created_at', $orderDir = 'DESC', $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM contacts WHERE user_id = ?";
        $params = [$userId];

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['tags'])) {
            $sql .= " AND tags LIKE ?";
            $params[] = '%' . $filters['tags'] . '%';
        }

        // Order by
        $allowedColumns = ['id', 'first_name', 'last_name', 'email', 'company', 'created_at', 'updated_at'];
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'created_at';
        }

        $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $orderBy $orderDir";

        // Pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get total count with filters
     */
    public function getTotalCount($userId, $filters = [])
    {
        $sql = "SELECT COUNT(*) FROM contacts WHERE user_id = ?";
        $params = [$userId];

        // Apply filters
        if (!empty($filters['search'])) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['tags'])) {
            $sql .= " AND tags LIKE ?";
            $params[] = '%' . $filters['tags'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Check if email exists for user
     */
    public function emailExists($email, $userId, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM contacts WHERE email = ? AND user_id = ? AND id != ?");
            $stmt->execute([$email, $userId, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM contacts WHERE email = ? AND user_id = ?");
            $stmt->execute([$email, $userId]);
        }
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Bulk delete contacts
     */
    public function bulkDelete($ids, $userId)
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM contacts WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($ids, [$userId]);
        return $stmt->execute($params);
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus($ids, $status, $userId)
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE contacts SET status = ? WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge([$status], $ids, [$userId]);
        return $stmt->execute($params);
    }

    /**
     * Get contact statistics
     */
    public function getStats($userId)
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
            FROM contacts
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Get all unique tags
     */
    public function getAllTags($userId)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT tags
            FROM contacts
            WHERE user_id = ? AND tags IS NOT NULL AND tags != ''
        ");
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Parse comma-separated tags
        $allTags = [];
        foreach ($results as $tagString) {
            $tags = array_map('trim', explode(',', $tagString));
            $allTags = array_merge($allTags, $tags);
        }

        return array_unique(array_filter($allTags));
    }

    /**
     * Find contact by unsubscribe token
     */
    public function findByUnsubscribeToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM contacts WHERE unsubscribe_token = ? LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Unsubscribe contact
     */
    public function unsubscribe($token)
    {
        $stmt = $this->db->prepare("UPDATE contacts SET status = 'unsubscribed' WHERE unsubscribe_token = ?");
        return $stmt->execute([$token]);
    }
}
