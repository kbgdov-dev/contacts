<?php
$pageTitle = 'View Campaign - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($campaign['name']) ?>
                </h1>
                <div>
                    <span class="badge status-<?= $campaign['status'] ?> fs-6">
                        <?= ucfirst($campaign['status']) ?>
                    </span>
                    <?php if ($campaign['status'] === 'draft'): ?>
                        <a href="/public/index.php?page=campaign-edit&id=<?= $campaign['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="/public/index.php?page=campaigns" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Campaigns
                    </a>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Recipients</h6>
                            <h3 class="mb-0"><?= number_format($campaign['total_recipients']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Sent</h6>
                            <h3 class="mb-0"><?= number_format($campaign['sent_count']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Opened</h6>
                            <h3 class="mb-0"><?= number_format($campaign['opened_count']) ?></h3>
                            <?php if ($campaign['sent_count'] > 0): ?>
                                <small class="text-muted"><?= round(($campaign['opened_count'] / $campaign['sent_count']) * 100, 1) ?>%</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Clicked</h6>
                            <h3 class="mb-0"><?= number_format($campaign['clicked_count']) ?></h3>
                            <?php if ($campaign['sent_count'] > 0): ?>
                                <small class="text-muted"><?= round(($campaign['clicked_count'] / $campaign['sent_count']) * 100, 1) ?>%</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Details -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Campaign Details</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>Campaign Name:</th>
                                    <td><?= htmlspecialchars($campaign['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Subject:</th>
                                    <td><?= htmlspecialchars($campaign['subject']) ?></td>
                                </tr>
                                <tr>
                                    <th>Sender:</th>
                                    <td><?= htmlspecialchars($campaign['sender_name'] . ' <' . $campaign['sender_email'] . '>') ?></td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td><?= formatDate($campaign['created_at']) ?></td>
                                </tr>
                                <?php if ($campaign['sent_at']): ?>
                                    <tr>
                                        <th>Sent:</th>
                                        <td><?= formatDate($campaign['sent_at']) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Email Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="border p-3" style="background-color: #f8f9fa;">
                                <strong>Subject:</strong> <?= htmlspecialchars($campaign['subject']) ?><hr>
                                <?= nl2br(htmlspecialchars($campaign['body'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Recipients (<?= count($recipients) ?>)</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recipients)): ?>
                                <p class="text-muted">No recipients added yet.</p>
                            <?php else: ?>
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recipients as $recipient): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($recipient['email']) ?></td>
                                                    <td>
                                                        <span class="badge status-<?= $recipient['status'] ?>">
                                                            <?= ucfirst($recipient['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
