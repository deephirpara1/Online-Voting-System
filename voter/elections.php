<?php
/**
 * Active Elections List (Voter)
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$db = Database::getConnection();
$voterId = $_SESSION['voter_id'];

// Get active elections with voter's vote status
$stmt = $db->prepare(
    "SELECT e.*, 
            (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) as candidate_count,
            (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id AND v.voter_id = :vid) as has_voted
     FROM elections e
     WHERE e.status = 'active'
     ORDER BY e.start_date ASC"
);
$stmt->execute([':vid' => $voterId]);
$elections = $stmt->fetchAll();

$pageTitle = 'Active Elections';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Active Elections</h1>
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

        <?php if (empty($elections)): ?>
            <div class="card-custom animate-fade-in-up">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-inbox d-block"></i>
                        <h5>No Active Elections</h5>
                        <p>There are no elections currently open for voting. Check back later!</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4 stagger">
                <?php foreach ($elections as $election): ?>
                    <div class="col-lg-6 animate-fade-in-up">
                        <div class="card-custom h-100">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-poll me-2 text-success"></i><?= sanitize($election['title']) ?>
                                </h6>
                                <?= electionStatusBadge($election['status']) ?>
                            </div>
                            <div class="card-body">
                                <?php if ($election['description']): ?>
                                    <p class="text-muted mb-3"><?= sanitize($election['description']) ?></p>
                                <?php endif; ?>

                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Candidates</small>
                                        <strong class="text-primary"><?= $election['candidate_count'] ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Starts</small>
                                        <strong><?= formatDate($election['start_date'], 'M d') ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Ends</small>
                                        <strong><?= formatDate($election['end_date'], 'M d') ?></strong>
                                    </div>
                                </div>

                                <?php if ($election['has_voted'] > 0): ?>
                                    <div class="alert alert-success py-2 mb-0 text-center">
                                        <i class="fas fa-check-circle me-1"></i> You have already voted in this election.
                                    </div>
                                <?php else: ?>
                                    <a href="<?= getBaseUrl() ?>/voter/vote.php?election_id=<?= $election['id'] ?>" 
                                       class="btn btn-primary-gradient w-100">
                                        <i class="fas fa-vote-yea me-2"></i> Cast Your Vote
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
