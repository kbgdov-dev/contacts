<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5 text-center">
                        <?php if ($success): ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Unsubscribed Successfully</h2>
                            <p class="text-muted">
                                You have been successfully unsubscribed from our mailing list.
                                You will no longer receive emails from us.
                            </p>
                            <p class="mt-4">
                                <small class="text-muted">
                                    If you unsubscribed by mistake, please contact us.
                                </small>
                            </p>
                        <?php elseif ($contact): ?>
                            <i class="bi bi-envelope-x" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Unsubscribe from Emails</h2>
                            <p class="text-muted mb-4">
                                Are you sure you want to unsubscribe from our mailing list?
                            </p>
                            <div class="alert alert-info">
                                <strong><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></strong><br>
                                <?= htmlspecialchars($contact['email']) ?>
                            </div>
                            <form method="POST" action="/index.php?page=unsubscribe&token=<?= htmlspecialchars($token) ?>">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-x-circle"></i> Yes, Unsubscribe Me
                                </button>
                            </form>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Invalid Link</h2>
                            <p class="text-muted">
                                The unsubscribe link is invalid or has expired.
                                Please contact us if you continue to receive unwanted emails.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
