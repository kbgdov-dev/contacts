<?php
/**
 * Application Entry Point
 */

// Load configuration and dependencies
require_once '../config/config.php';
require_once SRC_PATH . '/Database.php';
require_once SRC_PATH . '/helpers.php';

// Get requested page
$page = $_GET['page'] ?? 'dashboard';

// If not logged in, redirect to login page
if (!isLoggedIn() && $page !== 'login' && $page !== 'register' && $page !== 'forgot-password' && $page !== 'reset-password' && $page !== 'unsubscribe') {
    $page = 'login';
}

// Route to appropriate controller
switch ($page) {
    // Authentication
    case 'login':
        require_once SRC_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;

    case 'register':
        require_once SRC_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->register();
        break;

    case 'logout':
        require_once SRC_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'forgot-password':
        require_once SRC_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->forgotPassword();
        break;

    case 'reset-password':
        require_once SRC_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
        break;

    // Dashboard
    case 'dashboard':
        requireAuth();
        require_once SRC_PATH . '/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;

    // Contacts
    case 'contacts':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->index();
        break;

    case 'contact-create':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->create();
        break;

    case 'contact-edit':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->edit();
        break;

    case 'contact-delete':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->delete();
        break;

    case 'contact-import':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->import();
        break;

    case 'contact-export':
        requireAuth();
        require_once SRC_PATH . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->export();
        break;

    // Campaigns
    case 'campaigns':
        requireAuth();
        require_once SRC_PATH . '/controllers/CampaignController.php';
        $controller = new CampaignController();
        $controller->index();
        break;

    case 'campaign-create':
        requireAuth();
        require_once SRC_PATH . '/controllers/CampaignController.php';
        $controller = new CampaignController();
        $controller->create();
        break;

    case 'campaign-edit':
        requireAuth();
        require_once SRC_PATH . '/controllers/CampaignController.php';
        $controller = new CampaignController();
        $controller->edit();
        break;

    case 'campaign-view':
        requireAuth();
        require_once SRC_PATH . '/controllers/CampaignController.php';
        $controller = new CampaignController();
        $controller->view();
        break;

    case 'campaign-send':
        requireAuth();
        require_once SRC_PATH . '/controllers/CampaignController.php';
        $controller = new CampaignController();
        $controller->send();
        break;

    // Email tracking
    case 'track-open':
        require_once SRC_PATH . '/controllers/TrackingController.php';
        $controller = new TrackingController();
        $controller->trackOpen();
        break;

    case 'track-click':
        require_once SRC_PATH . '/controllers/TrackingController.php';
        $controller = new TrackingController();
        $controller->trackClick();
        break;

    // Unsubscribe
    case 'unsubscribe':
        require_once SRC_PATH . '/controllers/UnsubscribeController.php';
        $controller = new UnsubscribeController();
        $controller->unsubscribe();
        break;

    // 404
    default:
        http_response_code(404);
        echo "Page not found";
        break;
}
