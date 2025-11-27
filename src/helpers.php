<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input data
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone
 */
function isValidPhone($phone)
{
    // Basic phone validation (can be customized)
    return preg_match('/^[+]?[\d\s\-()]{7,20}$/', $phone);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken()
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token)
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Get current user ID
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Generate random token
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Log activity
 */
function logActivity($action, $entityType = null, $entityId = null, $description = null)
{
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $userId = getCurrentUserId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $description,
            $ipAddress,
            $userAgent
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Format date
 */
function formatDate($date, $format = 'd.m.Y H:i')
{
    if (empty($date)) {
        return '';
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get pagination data
 */
function getPagination($totalItems, $currentPage = 1, $itemsPerPage = DEFAULT_ITEMS_PER_PAGE)
{
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;

    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset
    ];
}

/**
 * Require authentication
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        redirect('/public/index.php?page=login');
    }
}

/**
 * Require admin
 */
function requireAdmin()
{
    requireAuth();
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied. Admin privileges required.');
        redirect('/public/index.php?page=dashboard');
    }
}
