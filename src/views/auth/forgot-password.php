<?php
$pageTitle = 'Forgot Password - ' . APP_NAME;
ob_start();
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">
                        <i class="bi bi-key"></i> Forgot Password
                    </h2>

                    <p class="text-muted text-center mb-4">
                        Enter your email address and we'll send you instructions to reset your password.
                    </p>

                    <form method="POST" action="/index.php?page=forgot-password">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-envelope"></i> Send Reset Link
                        </button>
                    </form>

                    <hr class="my-4">

                    <p class="text-center mb-0">
                        <a href="/index.php?page=login" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once SRC_PATH . '/views/layouts/main.php';
?>
