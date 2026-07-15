<?php
/**
 * Voter Management Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

$stmt = $db->query(
    "SELECT v.*, (SELECT COUNT(*) FROM votes WHERE voter_id = v.id) as votes_cast
     FROM voters v ORDER BY v.created_at DESC"
);
$voters = $stmt->fetchAll();

$pageTitle = 'Voters';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Voter Management</h1>
        </div>
        <div class="dropdown user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/admin/change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <div class="card-custom animate-fade-in-up">
            <div class="card-header">
                <h6><i class="fas fa-users me-2 text-primary"></i>All Voters</h6>
                <button class="btn btn-primary-gradient btn-sm" data-bs-toggle="modal" data-bs-target="#voterModal" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i> Add Voter
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="votersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Voter ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Votes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($voters as $i => $v): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><code><?= sanitize($v['voter_id']) ?></code></td>
                                    <td><strong><?= sanitize($v['full_name']) ?></strong></td>
                                    <td><?= sanitize($v['username']) ?></td>
                                    <td><?= sanitize($v['email']) ?></td>
                                    <td><?= sanitize($v['phone'] ?? '—') ?></td>
                                    <td><span class="badge bg-primary"><?= $v['votes_cast'] ?></span></td>
                                    <td>
                                        <?php if ($v['is_active']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn-action edit" title="Edit"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($v)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Toggle Active/Inactive -->
                                            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/voter_process.php" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                                <input type="hidden" name="is_active" value="<?= $v['is_active'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn-action <?= $v['is_active'] ? 'delete' : 'view' ?>" 
                                                        title="<?= $v['is_active'] ? 'Disable' : 'Enable' ?>">
                                                    <i class="fas <?= $v['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/voter_process.php" class="d-inline delete-form">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                                <button type="submit" class="btn-action delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Voter Modal -->
<div class="modal fade" id="voterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/voter_process.php" id="voterForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="voterId" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-custom" id="full_name" name="full_name" required maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label-custom">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-custom" id="username" name="username" required maxlength="50">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label-custom">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-custom" id="email" name="email" required maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label-custom">Phone</label>
                            <input type="tel" class="form-control form-control-custom" id="phone" name="phone" maxlength="20">
                        </div>
                    </div>
                    <div class="mb-3" id="passwordField">
                        <label for="password" class="form-label-custom">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-custom" id="password" name="password" minlength="6">
                        <small class="text-muted">Minimum 6 characters. Leave blank when editing to keep current password.</small>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label-custom">Address</label>
                        <textarea class="form-control form-control-custom" id="address" name="address" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-gradient">
                        <i class="fas fa-save me-1"></i> Save Voter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#votersTable');

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmDelete('this voter').then(confirmed => {
                if (confirmed) form.submit();
            });
        });
    });
});

function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('voterId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Add Voter';
    document.getElementById('voterForm').reset();
    document.getElementById('password').required = true;
}

function openEditModal(voter) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('voterId').value = voter.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Voter';
    document.getElementById('full_name').value = voter.full_name;
    document.getElementById('username').value = voter.username;
    document.getElementById('email').value = voter.email;
    document.getElementById('phone').value = voter.phone || '';
    document.getElementById('address').value = voter.address || '';
    document.getElementById('password').required = false;
    document.getElementById('password').value = '';

    const modal = new bootstrap.Modal(document.getElementById('voterModal'));
    modal.show();
}
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
