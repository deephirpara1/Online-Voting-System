<?php
/**
 * Admin Login Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect(getBaseUrl() . '/admin/index.php');
}

$pageTitle = 'Admin Login';
require_once BASE_PATH . 'includes/header.php';
?>

<div class="login-page">
    <div class="login-card animate-fade-in-up">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h4>Admin Login</h4>
            <p>Sign in to the admin dashboard</p>
        </div>

        <?= renderFlashMessages() ?>

        <form action="<?= getBaseUrl() ?>/admin/process/auth_process.php" method="POST" id="adminLoginForm" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="login">

            <div class="mb-3 position-relative">
                <label for="username" class="form-label-custom">Username</label>
                <div class="position-relative">
                    <i class="fas fa-user input-icon" style="top: 50%; left: 1rem; position: absolute; transform: translateY(-50%); color: #adb5bd; z-index: 5;"></i>
                    <input type="text" class="form-control form-control-custom" id="username" name="username"
                           placeholder="Enter your username" required autofocus
                           style="padding-left: 2.75rem;">
                </div>
            </div>

            <div class="mb-4 position-relative">
                <label for="password" class="form-label-custom">Password</label>
                <div class="position-relative">
                    <i class="fas fa-lock input-icon" style="top: 50%; left: 1rem; position: absolute; transform: translateY(-50%); color: #adb5bd; z-index: 5;"></i>
                    <input type="password" class="form-control form-control-custom" id="password" name="password"
                           placeholder="Enter your password" required
                           style="padding-left: 2.75rem;">
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted" 
                            onclick="togglePassword('password', this)" style="z-index: 5; text-decoration: none;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In
            </button>
        </form>

        <a href="<?= getBaseUrl() ?>/index.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i> Back to Home
        </a>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('loginBtn');
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!username || !password) {
        e.preventDefault();
        showToast('Please fill in all fields', 'error');
        return;
    }
    setButtonLoading(btn, true);
});
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
