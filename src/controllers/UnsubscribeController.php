<?php
/**
 * Unsubscribe Controller
 */

require_once SRC_PATH . '/models/Contact.php';

class UnsubscribeController
{
    private $contactModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
    }

    /**
     * Unsubscribe from emails
     * URL: /public/index.php?page=unsubscribe&token=xxxxx
     */
    public function unsubscribe()
    {
        $token = $_GET['token'] ?? '';
        $contact = null;
        $success = false;

        if (!empty($token)) {
            $contact = $this->contactModel->findByUnsubscribeToken($token);

            if ($contact && $_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($this->contactModel->unsubscribe($token)) {
                    $success = true;
                    logActivity('contact_unsubscribed', 'contact', $contact['id'], 'Contact unsubscribed: ' . $contact['email']);
                }
            }
        }

        require_once SRC_PATH . '/views/unsubscribe.php';
    }
}
