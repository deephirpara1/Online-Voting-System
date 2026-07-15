<?php
/**
 * Voter Login Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isVoterLoggedIn()) {
    redirect(getBaseUrl() . '/voter/index.php');
}

$pageTitle = 'Voter Login';
require_once BASE_PATH . 'includes/header.php';
?>

<div class="login-page">
    <div class="login-card animate-fade-in-up">
        <div class="login-header">
            <div class="login-icon" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); box-shadow: 0 0.5rem 1.5rem rgba(28,200,138,0.25);">
                <i class="fas fa-vote-yea"></i>
            </div>
            <h4>Voter Login</h4>
            <p>Sign in to cast your vote</p>
        </div>

        <?= renderFlashMessages() ?>

        <form action="<?= getBaseUrl() ?>/voter/process/auth_process.php" method="POST" id="voterLoginForm" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="login">

            <div class="mb-3">
                <label for="username" class="form-label-custom">Username</label>
                <div class="position-relative">
                    <i class="fas fa-user" style="position:absolute; top:50%; left:1rem; transform:translateY(-50%); color:#adb5bd; z-index:5;"></i>
                    <input type="text" class="form-control form-control-custom" id="username" name="username"
                           placeholder="Enter your username" required autofocus
                           style="padding-left: 2.75rem;">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label-custom">Password</label>
                <div class="position-relative">
                    <i class="fas fa-lock" style="position:absolute; top:50%; left:1rem; transform:translateY(-50%); color:#adb5bd; z-index:5;"></i>
                    <input type="password" class="form-control form-control-custom" id="password" name="password"
                           placeholder="Enter your password" required
                           style="padding-left: 2.75rem;">
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted"
                            onclick="togglePassword('password', this)" style="z-index:5; text-decoration:none;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login" id="loginBtn" style="background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);">
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

document.getElementById('voterLoginForm').addEventListener('submit', function(e) {
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
