<?php
/**
 * Voter Dashboard
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$db = Database::getConnection();

// Voter stats
$voterId = $_SESSION['voter_id'];

// Active elections count
$stmt = $db->query("SELECT COUNT(*) as total FROM elections WHERE status = 'active'");
$activeElections = $stmt->fetch()['total'];

// My votes count
$stmt = $db->prepare('SELECT COUNT(*) as total FROM votes WHERE voter_id = :vid');
$stmt->execute([':vid' => $voterId]);
$myVotes = $stmt->fetch()['total'];

// Ended elections count
$stmt = $db->query("SELECT COUNT(*) as total FROM elections WHERE status = 'ended'");
$endedElections = $stmt->fetch()['total'];

// Elections I haven't voted in yet
$stmt = $db->prepare(
    "SELECT COUNT(*) as total FROM elections e 
     WHERE e.status = 'active' 
     AND e.id NOT IN (SELECT election_id FROM votes WHERE voter_id = :vid)"
);
$stmt->execute([':vid' => $voterId]);
$pendingVotes = $stmt->fetch()['total'];

// Recent elections
$stmt = $db->query("SELECT * FROM elections ORDER BY created_at DESC LIMIT 5");
$recentElections = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Welcome, <?= sanitize($_SESSION['voter_name'] ?? 'Voter') ?></h1>
        </div>
        <div class="dropdown user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['voter_name'] ?? 'V', 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($_SESSION['voter_name'] ?? 'Voter') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/voter/profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/voter/change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>/voter/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4 stagger">
            <div class="col-md-3 col-sm-6 animate-fade-in-up">
                <div class="stat-card green">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Active Elections</div>
                            <div class="stat-value"><?= $activeElections ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-poll"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 animate-fade-in-up">
                <div class="stat-card orange">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Pending Votes</div>
                            <div class="stat-value"><?= $pendingVotes ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 animate-fade-in-up">
                <div class="stat-card blue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">My Votes</div>
                            <div class="stat-value"><?= $myVotes ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-vote-yea"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 animate-fade-in-up">
                <div class="stat-card cyan">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Ended Elections</div>
                            <div class="stat-value"><?= $endedElections ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-flag-checkered"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions + Recent Elections -->
        <div class="row g-4">
            <div class="col-lg-4 animate-fade-in-up">
                <div class="card-custom h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?= getBaseUrl() ?>/voter/elections.php" class="btn btn-primary-gradient">
                                <i class="fas fa-vote-yea me-2"></i> Vote Now
                            </a>
                            <a href="<?= getBaseUrl() ?>/voter/results.php" class="btn btn-outline-secondary">
                                <i class="fas fa-chart-pie me-2"></i> View Results
                            </a>
                            <a href="<?= getBaseUrl() ?>/voter/profile.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-circle me-2"></i> My Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 animate-fade-in-up">
                <div class="card-custom h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-list me-2 text-info"></i>Recent Elections</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentElections)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox d-block"></i>
                                <h5>No Elections Yet</h5>
                                <p>Elections will appear here when they are created by the administrator.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Period</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentElections as $el): ?>
                                            <tr>
                                                <td><strong><?= sanitize($el['title']) ?></strong></td>
                                                <td><small><?= formatDate($el['start_date'], 'M d') ?> — <?= formatDate($el['end_date'], 'M d, Y') ?></small></td>
                                                <td><?= electionStatusBadge($el['status']) ?></td>
                                                <td>
                                                    <?php if ($el['status'] === 'active'): ?>
                                                        <a href="<?= getBaseUrl() ?>/voter/vote.php?election_id=<?= $el['id'] ?>" class="btn btn-sm btn-primary-gradient">
                                                            <i class="fas fa-vote-yea me-1"></i> Vote
                                                        </a>
                                                    <?php elseif ($el['status'] === 'ended'): ?>
                                                        <a href="<?= getBaseUrl() ?>/voter/results.php?election_id=<?= $el['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-chart-bar me-1"></i> Results
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>Upcoming</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
