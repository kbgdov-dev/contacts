<?php
$pageTitle = 'Edit Campaign - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil"></i> Edit Campaign
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="/public/index.php?page=campaign-edit&id=<?= $campaign['id'] ?>" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="update">

                        <div class="mb-3">
                            <label for="name" class="form-label">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= htmlspecialchars($_POST['name'] ?? $campaign['name']) ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sender_name" class="form-label">Sender Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name"
                                       value="<?= htmlspecialchars($_POST['sender_name'] ?? $campaign['sender_name']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sender_email" class="form-label">Sender Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="sender_email" name="sender_email"
                                       value="<?= htmlspecialchars($_POST['sender_email'] ?? $campaign['sender_email']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject"
                                   value="<?= htmlspecialchars($_POST['subject'] ?? $campaign['subject']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Email Body <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="body" name="body" rows="10" required><?= htmlspecialchars($_POST['body'] ?? $campaign['body']) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/public/index.php?page=campaigns" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Campaign Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Campaign Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="campaign-stats">
                        <div>
                            <div class="campaign-stat-value"><?= number_format($stats['total_recipients']) ?></div>
                            <div class="campaign-stat-label">Total Recipients</div>
                        </div>
                        <div>
                            <div class="campaign-stat-value"><?= number_format($stats['sent']) ?></div>
                            <div class="campaign-stat-label">Sent</div>
                        </div>
                        <div>
                            <div class="campaign-stat-value"><?= number_format($stats['opened']) ?></div>
                            <div class="campaign-stat-label">Opened</div>
                        </div>
                        <div>
                            <div class="campaign-stat-value"><?= number_format($stats['failed']) ?></div>
                            <div class="campaign-stat-label">Failed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Recipients -->
            <?php if ($campaign['status'] === 'draft'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Add Recipients</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/public/index.php?page=campaign-edit&id=<?= $campaign['id'] ?>">
                            <input type="hidden" name="action" value="add_recipients">

                            <div class="mb-3">
                                <label class="form-label">Recipient Type</label>
                                <select class="form-select" name="recipient_type" id="recipientType" onchange="toggleRecipientOptions()">
                                    <option value="all">All Active Contacts</option>
                                    <option value="selected">Selected Contacts</option>
                                    <option value="tags">By Tags</option>
                                </select>
                            </div>

                            <div id="selectedContactsDiv" style="display:none;" class="mb-3">
                                <label class="form-label">Select Contacts</label>
                                <select class="form-select" name="selected_contacts[]" multiple size="5">
                                    <?php foreach ($contacts as $contact): ?>
                                        <option value="<?= $contact['id'] ?>"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name'] . ' (' . $contact['email'] . ')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="tagsDiv" style="display:none;" class="mb-3">
                                <label class="form-label">Tags</label>
                                <input type="text" class="form-control" name="tags" placeholder="client, vip, partner">
                            </div>

                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-person-plus"></i> Add Recipients
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Send Campaign -->
                <?php if (count($recipients) > 0): ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Ready to Send</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">Campaign is ready to send to <?= count($recipients) ?> recipients.</p>
                            <form method="POST" action="/public/index.php?page=campaign-send" onsubmit="return confirm('Are you sure you want to send this campaign?')">
                                <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send"></i> Send Campaign Now
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleRecipientOptions() {
    const type = document.getElementById('recipientType').value;
    document.getElementById('selectedContactsDiv').style.display = type === 'selected' ? 'block' : 'none';
    document.getElementById('tagsDiv').style.display = type === 'tags' ? 'block' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
