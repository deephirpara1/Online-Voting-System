<?php
/**
 * Audit Logs Viewer (Admin)
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

$stmt = $db->query(
    "SELECT al.*, 
            CASE 
                WHEN al.user_type = 'admin' THEN (SELECT full_name FROM admins WHERE id = al.user_id)
                WHEN al.user_type = 'voter' THEN (SELECT full_name FROM voters WHERE id = al.user_id)
            END as user_name
     FROM audit_logs al 
     ORDER BY al.created_at DESC 
     LIMIT 500"
);
$logs = $stmt->fetchAll();

$pageTitle = 'Audit Logs';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Audit Logs</h1>
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
        <div class="card-custom animate-fade-in-up">
            <div class="card-header">
                <h6><i class="fas fa-clipboard-list me-2 text-primary"></i>System Activity Log</h6>
                <span class="badge bg-primary"><?= count($logs) ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="logsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Time</th>
                                <th>User Type</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $i => $log): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <small><?= formatDate($log['created_at'], 'M d, Y') ?></small>
                                        <br><small class="text-muted"><?= formatDate($log['created_at'], 'h:i:s A') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $log['user_type'] === 'admin' ? 'bg-danger' : 'bg-info' ?>">
                                            <?= ucfirst($log['user_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= sanitize($log['user_name'] ?? 'Unknown (ID: ' . $log['user_id'] . ')') ?></td>
                                    <td><code><?= sanitize($log['action']) ?></code></td>
                                    <td><small><?= sanitize(truncate($log['description'] ?? '', 80)) ?></small></td>
                                    <td><code class="text-muted"><?= sanitize($log['ip_address'] ?? '') ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#logsTable', { order: [[1, 'desc']] });
});
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
