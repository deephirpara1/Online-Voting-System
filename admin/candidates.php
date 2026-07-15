<?php
/**
 * Candidate Management Page
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

// Fetch candidates with election title
$stmt = $db->query(
    "SELECT c.*, e.title as election_title, e.status as election_status
     FROM candidates c
     JOIN elections e ON c.election_id = e.id
     ORDER BY c.created_at DESC"
);
$candidates = $stmt->fetchAll();

// Fetch elections for the dropdown
$stmt = $db->query("SELECT id, title, status FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll();

$pageTitle = 'Candidates';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Candidate Management</h1>
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
                <h6><i class="fas fa-user-tie me-2 text-primary"></i>All Candidates</h6>
                <button class="btn btn-primary-gradient btn-sm" data-bs-toggle="modal" data-bs-target="#candidateModal" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i> Add Candidate
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom" id="candidatesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Party</th>
                                <th>Symbol</th>
                                <th>Election</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $i => $c): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <?php if ($c['photo']): ?>
                                            <img src="<?= getBaseUrl() ?>/uploads/candidates/<?= sanitize($c['photo']) ?>" 
                                                 alt="<?= sanitize($c['full_name']) ?>"
                                                 style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:14px;">
                                                <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($c['full_name']) ?></strong>
                                        <?php if ($c['manifesto']): ?>
                                            <br><small class="text-muted"><?= sanitize(truncate($c['manifesto'], 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= sanitize($c['party'] ?? '—') ?></td>
                                    <td style="font-size:1.3rem;"><?= $c['symbol'] ?? '—' ?></td>
                                    <td>
                                        <?= sanitize($c['election_title']) ?>
                                        <?= electionStatusBadge($c['election_status']) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn-action edit" title="Edit"
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/candidate_process.php" class="d-inline delete-form">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

<!-- Candidate Modal -->
<div class="modal fade" id="candidateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= getBaseUrl() ?>/admin/process/candidate_process.php" enctype="multipart/form-data" id="candidateForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="candidateId" value="">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus-circle me-2"></i>Add Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-custom" id="full_name" name="full_name" required maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="election_id" class="form-label-custom">Election <span class="text-danger">*</span></label>
                            <select class="form-select form-control-custom" id="election_id" name="election_id" required>
                                <option value="">Select Election</option>
                                <?php foreach ($elections as $el): ?>
                                    <option value="<?= $el['id'] ?>"><?= sanitize($el['title']) ?> (<?= ucfirst($el['status']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="party" class="form-label-custom">Party</label>
                            <input type="text" class="form-control form-control-custom" id="party" name="party" maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="symbol" class="form-label-custom">Symbol (emoji)</label>
                            <input type="text" class="form-control form-control-custom" id="symbol" name="symbol" maxlength="10"
                                   placeholder="e.g., ⭐ 🌿 🤝">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="manifesto" class="form-label-custom">Manifesto</label>
                        <textarea class="form-control form-control-custom" id="manifesto" name="manifesto" rows="3"
                                  placeholder="Candidate's campaign promises and goals..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="photo" class="form-label-custom">Photo</label>
                        <input type="file" class="form-control form-control-custom" id="photo" name="photo" accept="image/*"
                               onchange="previewImage(this, '#photoPreview')">
                        <small class="text-muted">Max 2MB. Accepted: JPG, PNG, GIF, WEBP</small>
                        <div class="mt-2">
                            <img id="photoPreview" src="#" alt="Preview" style="display:none; max-width:120px; border-radius:8px; border:2px solid #e9ecef;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-gradient">
                        <i class="fas fa-save me-1"></i> Save Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initDataTable('#candidatesTable');

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            confirmDelete('this candidate').then(confirmed => {
                if (confirmed) form.submit();
            });
        });
    });
});

function openCreateModal() {
    document.getElementById('formAction').value = 'create';
    document.getElementById('candidateId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Add Candidate';
    document.getElementById('candidateForm').reset();
    document.getElementById('photoPreview').style.display = 'none';
}

function openEditModal(candidate) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('candidateId').value = candidate.id;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Candidate';
    document.getElementById('full_name').value = candidate.full_name;
    document.getElementById('election_id').value = candidate.election_id;
    document.getElementById('party').value = candidate.party || '';
    document.getElementById('symbol').value = candidate.symbol || '';
    document.getElementById('manifesto').value = candidate.manifesto || '';

    const preview = document.getElementById('photoPreview');
    if (candidate.photo) {
        preview.src = '<?= getBaseUrl() ?>/uploads/candidates/' + candidate.photo;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('candidateModal'));
    modal.show();
}
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
