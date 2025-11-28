<?php
$pageTitle = 'Import Contacts - ' . APP_NAME;
ob_start();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-upload"></i> Import Contacts
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Import Instructions:</h5>
                        <ul class="mb-0">
                            <li>Upload a CSV file with contact information</li>
                            <li>The first row should contain column headers</li>
                            <li>Required columns: <strong>first_name</strong>, <strong>last_name</strong>, <strong>email</strong></li>
                            <li>Optional columns: middle_name, phone, company, position, tags, notes, status</li>
                            <li>Duplicate emails will be skipped</li>
                        </ul>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Sample CSV Format</h6>
                        </div>
                        <div class="card-body">
                            <code>
                                first_name,last_name,email,phone,company,position,tags,status<br>
                                John,Doe,john.doe@example.com,+1234567890,Acme Corp,Manager,"client,vip",active<br>
                                Jane,Smith,jane.smith@example.com,+0987654321,Tech Inc,Developer,partner,active
                            </code>
                        </div>
                    </div>

                    <form method="POST" action="/index.php?page=contact-import" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="import_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="import_file" name="import_file"
                                   accept=".csv" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/index.php?page=contacts" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Import Contacts
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
