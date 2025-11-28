<?php
/**
 * Tracking Controller
 * Handles email open and link click tracking
 */

require_once SRC_PATH . '/models/Campaign.php';

class TrackingController
{
    private $campaignModel;

    public function __construct()
    {
        $this->campaignModel = new Campaign();
    }

    /**
     * Track email open
     * URL: /index.php?page=track-open&token=xxxxx
     */
    public function trackOpen()
    {
        $token = $_GET['token'] ?? '';

        if (!empty($token)) {
            $this->campaignModel->trackOpen($token);
        }

        // Return 1x1 transparent GIF pixel
        header('Content-Type: image/gif');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

        // 1x1 transparent GIF in base64
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Track link click
     * URL: /index.php?page=track-click&token=xxxxx&url=encoded_url
     */
    public function trackClick()
    {
        $token = $_GET['token'] ?? '';
        $url = $_GET['url'] ?? '';

        if (!empty($token)) {
            $this->campaignModel->trackClick($token);
        }

        // Redirect to original URL
        if (!empty($url)) {
            $decodedUrl = base64_decode($url);
            // Validate URL
            if (filter_var($decodedUrl, FILTER_VALIDATE_URL)) {
                redirect($decodedUrl);
            }
        }

        // If no valid URL, redirect to homepage
        redirect(APP_URL);
    }
}
