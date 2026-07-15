<?php
/**
 * Election Management Page
 * CRUD operations for elections with DataTables.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

// Fetch all elections with vote counts
$stmt = $db->query(
    "SELECT e.*, 
            a.full_name as created_by_name,
            (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as candidate_count,
            (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) as vote_count
     FROM elections e
     LEFT JOIN admins a ON e.created_by = a.id
     ORDER BY e.created_at DESC"
);
$elections = $stmt->fetchAll();

$pageTitle = 'Elections';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Election Management</h1>
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
                <h6><i class="fas fa-poll me-2 text-primary"></i>All Elections</h6>
                <button class="btn btn-primary-gradient btn-sm" data-bs-toggle="modal" data-bs-target="#electionModal" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i> New Election
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="electionsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Candidates</th>
                                <th>Votes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($elections as $i => $election): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= sanitize($election['title']) ?></strong>
                                        <?php if ($election['description']): ?>
                                            <br><small class="text-muted"><?= sanitize(truncate($election['description'], 60)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= formatDate($election['start_date'], 'M d, Y h:i A') ?></td>
                                    <td><?= formatDate($election['end_date'], 'M d, Y h:i A') ?></td>
                                    <td><?= electionStatusBadge($election['status']) ?></td>
                                    <td><span class="badge bg-info"><?= $election['candidate_count'] ?></span></td>
                                    <td><span class="badge bg-primary"><?= $election['vote_count'] ?></span></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if ($election['status'] === 'upcoming'): ?>
                                                <form method="POST" action="<?= getBaseUrl() ?>/admin/process/election_process.php" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="start">
                                                    <input type="hidden" name="id" value="<?= $election['id'] ?>">
                                                    <button type="submit" class="btn-action view" title="Start Election" onclick="return confirm('Start this election?')">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($election['status'] === 'active'): ?>
                                                <form method="POST" action="<?= getBaseUrl() ?>/admin/process/election_process.php" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="end">
                                                    <input type="hidden" name="id" value="<?= $election['id'] ?>">
                                                    <button type="submit" class="btn-action delete" title="End Election" onclick="return confirm('End this election? Voting will be closed.')">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button class="btn-action edit" title="Edit"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($election)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <?php if ($election['vote_count'] == 0): ?>
                                                <form method="POST" action="<?= getBaseUrl() ?>/admin/process/election_process.php" class="d-inline delete-form">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $election['id'] ?>">
                                                    <button type="submit" class="btn-action delete" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

<!-- Election Modal -->
<div class="modal fade" id="electionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/election_process.php" id="electionForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="electionId" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Create Election</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label-custom">Election Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-custom" id="title" name="title" required maxlength="200"
                               placeholder="e.g., Student Council Election 2026">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label-custom">Description</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="3"
                                  placeholder="Brief description of the election..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label-custom">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control form-control-custom" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label-custom">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control form-control-custom" id="end_date" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-gradient" id="saveBtn">
                        <i class="fas fa-save me-1"></i> Save Election
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#electionsTable');

    // Delete confirmation
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmDelete('this election').then(confirmed => {
                if (confirmed) form.submit();
            });
        });
    });
});

function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('electionId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Create Election';
    document.getElementById('electionForm').reset();
}

function openEditModal(election) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('electionId').value = election.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Election';
    document.getElementById('title').value = election.title;
    document.getElementById('description').value = election.description || '';
    
    // Format dates for datetime-local input
    document.getElementById('start_date').value = election.start_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('end_date').value = election.end_date.replace(' ', 'T').substring(0, 16);

    const modal = new bootstrap.Modal(document.getElementById('electionModal'));
    modal.show();
}
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
