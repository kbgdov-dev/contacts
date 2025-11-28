<?php
$pageTitle = 'Create Campaign - ' . APP_NAME;
ob_start();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-envelope-plus"></i> Create New Campaign
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="/index.php?page=campaign-create" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sender_name" class="form-label">Sender Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name"
                                       value="<?= htmlspecialchars($_POST['sender_name'] ?? $defaultSenderName ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sender_email" class="form-label">Sender Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="sender_email" name="sender_email"
                                       value="<?= htmlspecialchars($_POST['sender_email'] ?? $defaultSenderEmail ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                   value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                            <small class="text-muted">
                                You can use placeholders: {first_name}, {last_name}, {email}, {company}
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Email Body <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="body" name="body" rows="15" required><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
                            <small class="text-muted">
                                You can use HTML and placeholders: {first_name}, {last_name}, {email}, {company}
                            </small>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="previewEmail()">
                                <i class="bi bi-eye"></i> Preview Email
                            </button>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/index.php?page=campaigns" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Campaign
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
