<?php
$pageTitle = 'Campaigns - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="bi bi-envelope-fill"></i> Campaigns
                </h1>
                <a href="/public/index.php?page=campaign-create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Campaign
                </a>
            </div>

            <?php if (empty($campaigns)): ?>
                <div class="alert alert-info">
                    No campaigns yet. <a href="/public/index.php?page=campaign-create">Create your first campaign</a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Recipients</th>
                                        <th>Sent</th>
                                        <th>Opened</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($campaign['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($campaign['subject']) ?></td>
                                            <td>
                                                <span class="badge status-<?= $campaign['status'] ?>">
                                                    <?= ucfirst($campaign['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($campaign['total_recipients']) ?></td>
                                            <td><?= number_format($campaign['sent_count']) ?></td>
                                            <td><?= number_format($campaign['opened_count']) ?></td>
                                            <td><?= formatDate($campaign['created_at'], 'd.m.Y') ?></td>
                                            <td class="table-actions">
                                                <a href="/public/index.php?page=campaign-view&id=<?= $campaign['id'] ?>"
                                                   class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($campaign['status'] === 'draft'): ?>
                                                    <a href="/public/index.php?page=campaign-edit&id=<?= $campaign['id'] ?>"
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
