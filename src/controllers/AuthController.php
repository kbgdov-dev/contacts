<?php
/**
 * Authentication Controller
 */

require_once SRC_PATH . '/models/User.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Login
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/index.php?page=login');
            }

            // Validate inputs
            if (empty($email) || empty($password)) {
                setFlashMessage('error', 'Email and password are required.');
                require_once SRC_PATH . '/views/auth/login.php';
                return;
            }

            // Find user
            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                setFlashMessage('error', 'Invalid email or password.');
                require_once SRC_PATH . '/views/auth/login.php';
                return;
            }

            // Verify password
            if (!$this->userModel->verifyPassword($password, $user['password'])) {
                setFlashMessage('error', 'Invalid email or password.');
                require_once SRC_PATH . '/views/auth/login.php';
                return;
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                setFlashMessage('error', 'Your account is inactive. Please contact administrator.');
                require_once SRC_PATH . '/views/auth/login.php';
                return;
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];

            // Log activity
            logActivity('user_login', 'user', $user['id'], 'User logged in');

            // Redirect to dashboard
            setFlashMessage('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect('/index.php?page=dashboard');
        }

        // Show login form
        require_once SRC_PATH . '/views/auth/login.php';
    }

    /**
     * Register
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $firstName = sanitize($_POST['first_name'] ?? '');
            $lastName = sanitize($_POST['last_name'] ?? '');
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/index.php?page=register');
            }

            // Validate inputs
            $errors = [];

            if (empty($firstName)) {
                $errors[] = 'First name is required.';
            }

            if (empty($lastName)) {
                $errors[] = 'Last name is required.';
            }

            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!isValidEmail($email)) {
                $errors[] = 'Invalid email format.';
            } elseif ($this->userModel->emailExists($email)) {
                $errors[] = 'Email already exists.';
            }

            if (empty($password)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    setFlashMessage('error', $error);
                }
                require_once SRC_PATH . '/views/auth/register.php';
                return;
            }

            // Create user
            if ($this->userModel->create($email, $password, $firstName, $lastName)) {
                logActivity('user_register', 'user', null, "New user registered: $email");
                setFlashMessage('success', 'Registration successful! Please login.');
                redirect('/index.php?page=login');
            } else {
                setFlashMessage('error', 'Registration failed. Please try again.');
                require_once SRC_PATH . '/views/auth/register.php';
                return;
            }
        }

        // Show registration form
        require_once SRC_PATH . '/views/auth/register.php';
    }

    /**
     * Logout
     */
    public function logout()
    {
        logActivity('user_logout', 'user', getCurrentUserId(), 'User logged out');

        session_unset();
        session_destroy();

        setFlashMessage('success', 'You have been logged out successfully.');
        redirect('/index.php?page=login');
    }

    /**
     * Forgot password
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/index.php?page=forgot-password');
            }

            // Validate email
            if (empty($email) || !isValidEmail($email)) {
                setFlashMessage('error', 'Valid email is required.');
                require_once SRC_PATH . '/views/auth/forgot-password.php';
                return;
            }

            // Check if user exists
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                // Generate reset token
                $token = generateToken(32);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token
                $this->userModel->setResetToken($email, $token, $expires);

                // Send email (TODO: implement email sending)
                $resetLink = APP_URL . "/index.php?page=reset-password&token=" . $token;

                // For now, just show the link (in production, send via email)
                setFlashMessage('success', 'Password reset instructions have been sent to your email. Reset link: ' . $resetLink);

                logActivity('password_reset_requested', 'user', $user['id'], 'Password reset requested');
            } else {
                // Don't reveal if email exists or not (security best practice)
                setFlashMessage('success', 'If the email exists, password reset instructions have been sent.');
            }

            redirect('/index.php?page=login');
        }

        // Show forgot password form
        require_once SRC_PATH . '/views/auth/forgot-password.php';
    }

    /**
     * Reset password
     */
    public function resetPassword()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            setFlashMessage('error', 'Invalid reset token.');
            redirect('/index.php?page=login');
        }

        // Verify token
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            setFlashMessage('error', 'Invalid or expired reset token.');
            redirect('/index.php?page=login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';

            // Validate CSRF token
            if (!verifyCsrfToken($csrfToken)) {
                setFlashMessage('error', 'Invalid request. Please try again.');
                redirect('/index.php?page=reset-password&token=' . $token);
            }

            // Validate password
            if (empty($password) || strlen($password) < 6) {
                setFlashMessage('error', 'Password must be at least 6 characters.');
                require_once SRC_PATH . '/views/auth/reset-password.php';
                return;
            }

            if ($password !== $confirmPassword) {
                setFlashMessage('error', 'Passwords do not match.');
                require_once SRC_PATH . '/views/auth/reset-password.php';
                return;
            }

            // Update password
            if ($this->userModel->updatePassword($user['id'], $password)) {
                $this->userModel->clearResetToken($user['id']);

                logActivity('password_reset_completed', 'user', $user['id'], 'Password reset completed');

                setFlashMessage('success', 'Password has been reset successfully. Please login.');
                redirect('/index.php?page=login');
            } else {
                setFlashMessage('error', 'Failed to reset password. Please try again.');
                require_once SRC_PATH . '/views/auth/reset-password.php';
                return;
            }
        }

        // Show reset password form
        require_once SRC_PATH . '/views/auth/reset-password.php';
    }
}
