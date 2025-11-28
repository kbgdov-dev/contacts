<?php
$pageTitle = 'Settings - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-gear-fill"></i> Settings
            </h1>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-envelope-fill"></i> SMTP Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/index.php?page=settings-save">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                           value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>"
                                           placeholder="smtp.example.com" required>
                                    <small class="form-text text-muted">The hostname of your SMTP server</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                               value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>"
                                               placeholder="587">
                                        <small class="form-text text-muted">Common ports: 587 (TLS), 465 (SSL), 25</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_encryption" class="form-label">Encryption</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                            <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                            <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                           value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>"
                                           placeholder="your-email@example.com">
                                    <small class="form-text text-muted">Leave empty if authentication is not required</small>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                           value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>"
                                           placeholder="Enter password">
                                    <small class="form-text text-muted">Your SMTP password or app-specific password</small>
                                </div>

                                <hr class="my-4">

                                <h6 class="mb-3">Sender Information</h6>

                                <div class="mb-3">
                                    <label for="smtp_from_email" class="form-label">From Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email"
                                           value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>"
                                           placeholder="noreply@example.com" required>
                                    <small class="form-text text-muted">Email address that will appear as sender</small>
                                </div>

                                <div class="mb-3">
                                    <label for="smtp_from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                                           value="<?= htmlspecialchars($settings['smtp_from_name'] ?? '') ?>"
                                           placeholder="Your Company Name">
                                    <small class="form-text text-muted">Name that will appear as sender</small>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Settings
                                    </button>
                                    <a href="/index.php?page=dashboard" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle-fill"></i> Help
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Common SMTP Providers:</h6>

                            <div class="mb-3">
                                <strong>Gmail</strong>
                                <ul class="small">
                                    <li>Host: smtp.gmail.com</li>
                                    <li>Port: 587</li>
                                    <li>Encryption: TLS</li>
                                    <li>Note: Use App Password</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <strong>Outlook/Office365</strong>
                                <ul class="small">
                                    <li>Host: smtp.office365.com</li>
                                    <li>Port: 587</li>
                                    <li>Encryption: TLS</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <strong>SendGrid</strong>
                                <ul class="small">
                                    <li>Host: smtp.sendgrid.net</li>
                                    <li>Port: 587</li>
                                    <li>Username: apikey</li>
                                    <li>Password: Your API Key</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <strong>Mailgun</strong>
                                <ul class="small">
                                    <li>Host: smtp.mailgun.org</li>
                                    <li>Port: 587</li>
                                    <li>Encryption: TLS</li>
                                </ul>
                            </div>

                            <div class="alert alert-info">
                                <small>
                                    <i class="bi bi-lightbulb-fill"></i>
                                    <strong>Tip:</strong> For Gmail, you need to create an
                                    <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a>
                                    if 2FA is enabled.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
