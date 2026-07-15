<?php
/**
 * Voter Change Password Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$pageTitle = 'Change Password';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Change Password</h1>
        </div>
        <div class="dropdown user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['voter_name'] ?? 'V', 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($_SESSION['voter_name'] ?? 'Voter') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/voter/profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>/voter/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card-custom animate-fade-in-up">
                    <div class="card-header">
                        <h6><i class="fas fa-key me-2 text-primary"></i>Update Your Password</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= getBaseUrl() ?>/voter/process/auth_process.php" method="POST" id="changePasswordForm">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label-custom">Current Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control form-control-custom" id="current_password" name="current_password" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted"
                                            onclick="togglePassword('current_password', this)" style="text-decoration:none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label-custom">New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control form-control-custom" id="new_password" name="new_password" minlength="6" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted"
                                            onclick="togglePassword('new_password', this)" style="text-decoration:none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label-custom">Confirm New Password</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control form-control-custom" id="confirm_password" name="confirm_password" minlength="6" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted"
                                            onclick="togglePassword('confirm_password', this)" style="text-decoration:none;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-gradient w-100" id="changePasswordBtn">
                                <i class="fas fa-save me-2"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPwd = document.getElementById('new_password').value;
    const confirmPwd = document.getElementById('confirm_password').value;
    if (newPwd !== confirmPwd) {
        e.preventDefault();
        showToast('New passwords do not match', 'error');
        return;
    }
    if (newPwd.length < 6) {
        e.preventDefault();
        showToast('Password must be at least 6 characters', 'error');
        return;
    }
    setButtonLoading(document.getElementById('changePasswordBtn'), true);
});
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
