<?php
/**
 * Voter Profile Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$db = Database::getConnection();
$stmt = $db->prepare('SELECT * FROM voters WHERE id = :id');
$stmt->execute([':id' => $_SESSION['voter_id']]);
$voter = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!validateCsrfToken()) {
        setFlash('error', 'Invalid security token.');
        redirect(getBaseUrl() . '/voter/profile.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if (empty($fullName) || empty($email)) {
        setFlash('error', 'Name and email are required.');
        redirect(getBaseUrl() . '/voter/profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email format.');
        redirect(getBaseUrl() . '/voter/profile.php');
    }

    try {
        // Check email uniqueness
        $stmt = $db->prepare('SELECT id FROM voters WHERE email = :e AND id != :id');
        $stmt->execute([':e' => $email, ':id' => $_SESSION['voter_id']]);
        if ($stmt->fetch()) {
            setFlash('error', 'Email already in use.');
            redirect(getBaseUrl() . '/voter/profile.php');
        }

        $stmt = $db->prepare(
            'UPDATE voters SET full_name = :full_name, email = :email, phone = :phone, address = :address WHERE id = :id'
        );
        $stmt->execute([
            ':full_name' => $fullName,
            ':email'     => $email,
            ':phone'     => $phone,
            ':address'   => $address,
            ':id'        => $_SESSION['voter_id'],
        ]);

        $_SESSION['voter_name'] = $fullName;
        auditLog('voter', $_SESSION['voter_id'], 'PROFILE_UPDATED', 'Voter updated their profile');
        setFlash('success', 'Profile updated successfully!');
        redirect(getBaseUrl() . '/voter/profile.php');
    } catch (PDOException $e) {
        error_log('Profile update error: ' . $e->getMessage());
        setFlash('error', 'Failed to update profile.');
        redirect(getBaseUrl() . '/voter/profile.php');
    }
}

$pageTitle = 'My Profile';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">My Profile</h1>
        </div>
        <div class="dropdown user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['voter_name'] ?? 'V', 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($_SESSION['voter_name'] ?? 'Voter') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/voter/change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>/voter/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-custom animate-fade-in-up overflow-hidden">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($voter['full_name'], 0, 1)) ?>
                        </div>
                        <h4><?= sanitize($voter['full_name']) ?></h4>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-id-card me-1"></i> <?= sanitize($voter['voter_id']) ?>
                        </p>
                    </div>

                    <!-- Profile Body -->
                    <div class="profile-body">
                        <div class="card-custom">
                            <div class="card-header">
                                <h6><i class="fas fa-edit me-2 text-primary"></i>Edit Profile</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= getBaseUrl() ?>/voter/profile.php">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-custom" name="full_name"
                                                   value="<?= sanitize($voter['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label-custom">Username</label>
                                            <input type="text" class="form-control form-control-custom" value="<?= sanitize($voter['username']) ?>" disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label-custom">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control form-control-custom" name="email"
                                                   value="<?= sanitize($voter['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label-custom">Phone</label>
                                            <input type="tel" class="form-control form-control-custom" name="phone"
                                                   value="<?= sanitize($voter['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label-custom">Address</label>
                                        <textarea class="form-control form-control-custom" name="address" rows="2"><?= sanitize($voter['address'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary-gradient">
                                        <i class="fas fa-save me-2"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
