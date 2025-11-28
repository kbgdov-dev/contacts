<?php
/**
 * User Model
 */

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new user
     */
    public function create($email, $password, $firstName, $lastName, $role = 'user')
    {
        $hashedPassword = password_hash($password, PASSWORD_HASH_ALGO);

        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, first_name, last_name, role)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $role]);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Find user by ID
     */
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Verify password
     */
    public function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Update password
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_HASH_ALGO);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Set reset token
     */
    public function setResetToken($email, $token, $expires)
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET reset_token = ?, reset_token_expires = ?
            WHERE email = ?
        ");
        return $stmt->execute([$token, $expires, $email]);
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken($token)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE reset_token = ?
            AND reset_token_expires > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Clear reset token
     */
    public function clearResetToken($userId)
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET reset_token = NULL, reset_token_expires = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get all users (admin only)
     */
    public function getAll($limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, role, status, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get total users count
     */
    public function getTotalCount()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }
}
