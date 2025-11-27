<?php
/**
 * Contact Controller
 */

require_once SRC_PATH . '/models/Contact.php';

class ContactController
{
    private $contactModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
    }

    /**
     * List all contacts
     */
    public function index()
    {
        $userId = getCurrentUserId();

        // Get filters from query string
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tags' => $_GET['tags'] ?? ''
        ];

        $orderBy = $_GET['order_by'] ?? 'created_at';
        $orderDir = $_GET['order_dir'] ?? 'DESC';
        $page = max(1, intval($_GET['page'] ?? 1));
        $itemsPerPage = min(intval($_GET['per_page'] ?? DEFAULT_ITEMS_PER_PAGE), MAX_ITEMS_PER_PAGE);

        // Get total count
        $totalContacts = $this->contactModel->getTotalCount($userId, $filters);

        // Get pagination data
        $pagination = getPagination($totalContacts, $page, $itemsPerPage);

        // Get contacts
        $contacts = $this->contactModel->getAll(
            $userId,
            $filters,
            $orderBy,
            $orderDir,
            $pagination['items_per_page'],
            $pagination['offset']
        );

        // Get stats
        $stats = $this->contactModel->getStats($userId);

        // Get all tags
        $allTags = $this->contactModel->getAllTags($userId);

        require_once SRC_PATH . '/views/contacts/index.php';
    }

    /**
     * Create new contact
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = getCurrentUserId();
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/public/index.php?page=contact-create');
            }

            // Sanitize input
            $data = [
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'middle_name' => sanitize($_POST['middle_name'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'company' => sanitize($_POST['company'] ?? ''),
                'position' => sanitize($_POST['position'] ?? ''),
                'tags' => sanitize($_POST['tags'] ?? ''),
                'status' => sanitize($_POST['status'] ?? 'active'),
                'notes' => sanitize($_POST['notes'] ?? '')
            ];

            // Validate
            $errors = $this->validateContactData($data);

            // Check if email exists
            if ($this->contactModel->emailExists($data['email'], $userId)) {
                $errors[] = 'Email already exists.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    setFlashMessage('error', $error);
                }
                require_once SRC_PATH . '/views/contacts/create.php';
                return;
            }

            // Create contact
            if ($this->contactModel->create($data, $userId)) {
                logActivity('contact_created', 'contact', null, 'Created contact: ' . $data['email']);
                setFlashMessage('success', 'Contact created successfully.');
                redirect('/public/index.php?page=contacts');
            } else {
                setFlashMessage('error', 'Failed to create contact.');
                require_once SRC_PATH . '/views/contacts/create.php';
            }
        } else {
            // Show create form
            require_once SRC_PATH . '/views/contacts/create.php';
        }
    }

    /**
     * Edit contact
     */
    public function edit()
    {
        $userId = getCurrentUserId();
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            setFlashMessage('error', 'Invalid contact ID.');
            redirect('/public/index.php?page=contacts');
        }

        $contact = $this->contactModel->findById($id, $userId);

        if (!$contact) {
            setFlashMessage('error', 'Contact not found.');
            redirect('/public/index.php?page=contacts');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/public/index.php?page=contact-edit&id=' . $id);
            }

            // Sanitize input
            $data = [
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'middle_name' => sanitize($_POST['middle_name'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'company' => sanitize($_POST['company'] ?? ''),
                'position' => sanitize($_POST['position'] ?? ''),
                'tags' => sanitize($_POST['tags'] ?? ''),
                'status' => sanitize($_POST['status'] ?? 'active'),
                'notes' => sanitize($_POST['notes'] ?? '')
            ];

            // Validate
            $errors = $this->validateContactData($data);

            // Check if email exists (excluding current contact)
            if ($this->contactModel->emailExists($data['email'], $userId, $id)) {
                $errors[] = 'Email already exists.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    setFlashMessage('error', $error);
                }
                require_once SRC_PATH . '/views/contacts/edit.php';
                return;
            }

            // Update contact
            if ($this->contactModel->update($id, $data, $userId)) {
                logActivity('contact_updated', 'contact', $id, 'Updated contact: ' . $data['email']);
                setFlashMessage('success', 'Contact updated successfully.');
                redirect('/public/index.php?page=contacts');
            } else {
                setFlashMessage('error', 'Failed to update contact.');
                require_once SRC_PATH . '/views/contacts/edit.php';
            }
        } else {
            // Show edit form
            require_once SRC_PATH . '/views/contacts/edit.php';
        }
    }

    /**
     * Delete contact
     */
    public function delete()
    {
        $userId = getCurrentUserId();
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            setFlashMessage('error', 'Invalid contact ID.');
            redirect('/public/index.php?page=contacts');
        }

        $contact = $this->contactModel->findById($id, $userId);

        if (!$contact) {
            setFlashMessage('error', 'Contact not found.');
            redirect('/public/index.php?page=contacts');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/public/index.php?page=contacts');
            }

            if ($this->contactModel->delete($id, $userId)) {
                logActivity('contact_deleted', 'contact', $id, 'Deleted contact: ' . $contact['email']);
                setFlashMessage('success', 'Contact deleted successfully.');
            } else {
                setFlashMessage('error', 'Failed to delete contact.');
            }
        }

        redirect('/public/index.php?page=contacts');
    }

    /**
     * Import contacts
     */
    public function import()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = getCurrentUserId();
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/public/index.php?page=contact-import');
            }

            // Check if file was uploaded
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                setFlashMessage('error', 'Please select a file to import.');
                require_once SRC_PATH . '/views/contacts/import.php';
                return;
            }

            $file = $_FILES['import_file'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Validate file type
            if (!in_array($fileExt, ['csv'])) {
                setFlashMessage('error', 'Only CSV files are supported.');
                require_once SRC_PATH . '/views/contacts/import.php';
                return;
            }

            // Read and parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                setFlashMessage('error', 'Failed to read file.');
                require_once SRC_PATH . '/views/contacts/import.php';
                return;
            }

            $headers = fgetcsv($handle);
            $imported = 0;
            $skipped = 0;
            $errors = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($headers)) {
                    $errors++;
                    continue;
                }

                $data = array_combine($headers, $row);

                // Map fields
                $contactData = [
                    'first_name' => $data['first_name'] ?? $data['First Name'] ?? '',
                    'last_name' => $data['last_name'] ?? $data['Last Name'] ?? '',
                    'middle_name' => $data['middle_name'] ?? $data['Middle Name'] ?? '',
                    'email' => $data['email'] ?? $data['Email'] ?? '',
                    'phone' => $data['phone'] ?? $data['Phone'] ?? '',
                    'company' => $data['company'] ?? $data['Company'] ?? '',
                    'position' => $data['position'] ?? $data['Position'] ?? '',
                    'tags' => $data['tags'] ?? $data['Tags'] ?? '',
                    'status' => $data['status'] ?? 'active',
                    'notes' => $data['notes'] ?? $data['Notes'] ?? ''
                ];

                // Validate email
                if (empty($contactData['email']) || !isValidEmail($contactData['email'])) {
                    $errors++;
                    continue;
                }

                // Check for duplicates
                if ($this->contactModel->emailExists($contactData['email'], $userId)) {
                    $skipped++;
                    continue;
                }

                // Create contact
                if ($this->contactModel->create($contactData, $userId)) {
                    $imported++;
                } else {
                    $errors++;
                }
            }

            fclose($handle);

            logActivity('contacts_imported', 'contact', null, "Imported $imported contacts");

            setFlashMessage('success', "Import complete. Imported: $imported, Skipped: $skipped, Errors: $errors");
            redirect('/public/index.php?page=contacts');
        } else {
            // Show import form
            require_once SRC_PATH . '/views/contacts/import.php';
        }
    }

    /**
     * Export contacts
     */
    public function export()
    {
        $userId = getCurrentUserId();
        $format = $_GET['format'] ?? 'csv';
        $ids = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];

        // Get contacts
        if (!empty($ids)) {
            $contacts = [];
            foreach ($ids as $id) {
                $contact = $this->contactModel->findById(intval($id), $userId);
                if ($contact) {
                    $contacts[] = $contact;
                }
            }
        } else {
            $contacts = $this->contactModel->getAll($userId, [], 'created_at', 'DESC', 10000, 0);
        }

        if (empty($contacts)) {
            setFlashMessage('error', 'No contacts to export.');
            redirect('/public/index.php?page=contacts');
        }

        // Export as CSV
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="contacts_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            // Headers
            fputcsv($output, ['first_name', 'last_name', 'middle_name', 'email', 'phone', 'company', 'position', 'tags', 'status', 'notes']);

            // Data
            foreach ($contacts as $contact) {
                fputcsv($output, [
                    $contact['first_name'],
                    $contact['last_name'],
                    $contact['middle_name'],
                    $contact['email'],
                    $contact['phone'],
                    $contact['company'],
                    $contact['position'],
                    $contact['tags'],
                    $contact['status'],
                    $contact['notes']
                ]);
            }

            fclose($output);

            logActivity('contacts_exported', 'contact', null, 'Exported ' . count($contacts) . ' contacts');
            exit;
        }
    }

    /**
     * Validate contact data
     */
    private function validateContactData($data)
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = 'First name is required.';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required.';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!isValidEmail($data['email'])) {
            $errors[] = 'Invalid email format.';
        }

        if (!empty($data['phone']) && !isValidPhone($data['phone'])) {
            $errors[] = 'Invalid phone format.';
        }

        return $errors;
    }
}
