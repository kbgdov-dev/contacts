<?php
$pageTitle = 'Contacts - ' . APP_NAME;
ob_start();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3">
                <i class="bi bi-person-lines-fill"></i> Contacts
            </h1>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
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
                                    <h3 class="mb-0"><?= number_format($stats['active']) ?></h3>
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
                                    <h3 class="mb-0"><?= number_format($stats['inactive']) ?></h3>
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
                                    <h3 class="mb-0"><?= number_format($stats['unsubscribed']) ?></h3>
                                </div>
                                <i class="bi bi-x-circle-fill stat-icon text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="/public/index.php" class="row g-3">
                        <input type="hidden" name="page" value="contacts">

                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search..."
                                   value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>

                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="unsubscribed" <?= $filters['status'] === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-select" name="per_page">
                                <option value="50" <?= $itemsPerPage == 50 ? 'selected' : '' ?>>50 per page</option>
                                <option value="100" <?= $itemsPerPage == 100 ? 'selected' : '' ?>>100 per page</option>
                                <option value="200" <?= $itemsPerPage == 200 ? 'selected' : '' ?>>200 per page</option>
                            </select>
                        </div>

                        <div class="col-md-5 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Filter
                            </button>
                            <a href="/public/index.php?page=contacts" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Reset
                            </a>
                            <a href="/public/index.php?page=contact-create" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> New Contact
                            </a>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-download"></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="exportContacts('csv')">Export to CSV</a></li>
                                </ul>
                            </div>
                            <a href="/public/index.php?page=contact-import" class="btn btn-outline-success">
                                <i class="bi bi-upload"></i> Import
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($contacts)): ?>
                        <div class="alert alert-info">
                            No contacts found. <a href="/public/index.php?page=contact-create">Create your first contact</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Company</th>
                                        <th>Tags</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                        <tr>
                                            <td><input type="checkbox" name="selected_ids[]" value="<?= $contact['id'] ?>"></td>
                                            <td>
                                                <strong><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></strong>
                                                <?php if ($contact['position']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($contact['position']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($contact['email']) ?></td>
                                            <td><?= htmlspecialchars($contact['phone']) ?></td>
                                            <td><?= htmlspecialchars($contact['company']) ?></td>
                                            <td>
                                                <?php if ($contact['tags']): ?>
                                                    <?php foreach (explode(',', $contact['tags']) as $tag): ?>
                                                        <span class="badge bg-secondary"><?= htmlspecialchars(trim($tag)) ?></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge status-<?= $contact['status'] ?>">
                                                    <?= ucfirst($contact['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($contact['created_at'], 'd.m.Y') ?></td>
                                            <td class="table-actions">
                                                <a href="/public/index.php?page=contact-edit&id=<?= $contact['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="/public/index.php?page=contact-delete&id=<?= $contact['id'] ?>"
                                                      class="d-inline" onsubmit="return confirmDelete('Are you sure you want to delete this contact?')">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=contacts&<?= http_build_query(array_merge($filters, ['page' => $i, 'per_page' => $itemsPerPage])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
