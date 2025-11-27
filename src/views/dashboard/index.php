<?php
$pageTitle = 'Dashboard - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-speedometer2"></i> Dashboard
            </h1>

            <!-- Contact Statistics -->
            <h5 class="mb-3">Contact Statistics</h5>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Contacts</h6>
                                    <h3 class="mb-0"><?= number_format($contactStats['total']) ?></h3>
                                </div>
                                <i class="bi bi-people-fill stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Active</h6>
                                    <h3 class="mb-0"><?= number_format($contactStats['active']) ?></h3>
                                </div>
                                <i class="bi bi-check-circle-fill stat-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Inactive</h6>
                                    <h3 class="mb-0"><?= number_format($contactStats['inactive']) ?></h3>
                                </div>
                                <i class="bi bi-dash-circle-fill stat-icon text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Unsubscribed</h6>
                                    <h3 class="mb-0"><?= number_format($contactStats['unsubscribed']) ?></h3>
                                </div>
                                <i class="bi bi-x-circle-fill stat-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Statistics -->
            <h5 class="mb-3">Campaign Statistics</h5>
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['total']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Draft</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['draft']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Scheduled</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['scheduled']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Sending</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['sending']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Sent</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['sent']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Cancelled</h6>
                            <h3 class="mb-0"><?= number_format($campaignStats['cancelled']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <!-- Recent Contacts -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-person-lines-fill"></i> Recent Contacts
                            </h6>
                            <a href="/public/index.php?page=contacts" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentContacts)): ?>
                                <p class="text-muted">No contacts yet. <a href="/public/index.php?page=contact-create">Create your first contact</a></p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentContacts as $contact): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($contact['email']) ?>
                                                </small>
                                            </div>
                                            <span class="badge status-<?= $contact['status'] ?>"><?= ucfirst($contact['status']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bi bi-envelope-fill"></i> Recent Campaigns
                            </h6>
                            <a href="/public/index.php?page=campaigns" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentCampaigns)): ?>
                                <p class="text-muted">No campaigns yet. <a href="/public/index.php?page=campaign-create">Create your first campaign</a></p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentCampaigns as $campaign): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= htmlspecialchars($campaign['name']) ?></strong><br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($campaign['subject']) ?>
                                                </small>
                                            </div>
                                            <span class="badge status-<?= $campaign['status'] ?>"><?= ucfirst($campaign['status']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-lightning-fill"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="/public/index.php?page=contact-create" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="bi bi-person-plus"></i> New Contact
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/public/index.php?page=contact-import" class="btn btn-outline-success w-100 mb-2">
                                        <i class="bi bi-upload"></i> Import Contacts
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/public/index.php?page=campaign-create" class="btn btn-outline-info w-100 mb-2">
                                        <i class="bi bi-envelope-plus"></i> New Campaign
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="/public/index.php?page=contacts" class="btn btn-outline-secondary w-100 mb-2">
                                        <i class="bi bi-list-ul"></i> View All Contacts
                                    </a>
                                </div>
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
