<?php
/**
 * Voter Results Page
 * Shows results only for ended elections.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$db = Database::getConnection();

// Only show ended elections
$stmt = $db->query("SELECT id, title FROM elections WHERE status = 'ended' ORDER BY end_date DESC");
$elections = $stmt->fetchAll();

$selectedElectionId = intval($_GET['election_id'] ?? ($elections[0]['id'] ?? 0));
$selectedElection = null;
$resultData = [];
$totalVotes = 0;

if ($selectedElectionId > 0) {
    // Verify it's ended
    $stmt = $db->prepare("SELECT * FROM elections WHERE id = :id AND status = 'ended'");
    $stmt->execute([':id' => $selectedElectionId]);
    $selectedElection = $stmt->fetch();

    if ($selectedElection) {
        $stmt = $db->prepare(
            "SELECT c.*, COUNT(v.id) as votes
             FROM candidates c
             LEFT JOIN votes v ON c.id = v.candidate_id
             WHERE c.election_id = :eid
             GROUP BY c.id
             ORDER BY votes DESC"
        );
        $stmt->execute([':eid' => $selectedElectionId]);
        $resultData = $stmt->fetchAll();
        $totalVotes = array_sum(array_column($resultData, 'votes'));
    }
}

$pageTitle = 'Election Results';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Election Results</h1>
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
                        <i class="fas fa-chart-pie d-block"></i>
                        <h5>No Results Available</h5>
                        <p>Results will appear here after an election has ended.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Election Selector -->
            <div class="card-custom mb-4 animate-fade-in-up">
                <div class="card-body">
                    <form method="GET">
                        <label class="form-label-custom">Select Election</label>
                        <select class="form-select form-control-custom" name="election_id" onchange="this.form.submit()">
                            <?php foreach ($elections as $el): ?>
                                <option value="<?= $el['id'] ?>" <?= $el['id'] == $selectedElectionId ? 'selected' : '' ?>>
                                    <?= sanitize($el['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>

            <?php if ($selectedElection && !empty($resultData)): ?>
                <!-- Chart -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6 animate-fade-in-up">
                        <div class="card-custom h-100">
                            <div class="card-header">
                                <h6><i class="fas fa-chart-pie me-2 text-info"></i>Vote Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="voterResultsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 animate-fade-in-up">
                        <div class="card-custom h-100">
                            <div class="card-header">
                                <h6><i class="fas fa-trophy me-2 text-warning"></i>Results</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($resultData as $i => $row): ?>
                                    <?php $pct = $totalVotes > 0 ? round(($row['votes'] / $totalVotes) * 100, 1) : 0; ?>
                                    <div class="result-bar-container">
                                        <div class="result-bar-label">
                                            <span>
                                                <?php if ($i === 0 && $totalVotes > 0): ?>
                                                    <i class="fas fa-crown text-warning me-1"></i>
                                                <?php endif; ?>
                                                <?= sanitize($row['full_name']) ?>
                                                <small class="text-muted">(<?= sanitize($row['party'] ?? 'Independent') ?>)</small>
                                            </span>
                                            <span><?= $row['votes'] ?> votes (<?= $pct ?>%)</span>
                                        </div>
                                        <div class="result-bar">
                                            <div class="result-bar-fill" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="text-center mt-3 pt-3 border-top">
                                    <strong class="text-muted">Total Votes: <?= number_format($totalVotes) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedElection && !empty($resultData)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('voterResultsChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($resultData, 'full_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_map('intval', array_column($resultData, 'votes'))) ?>,
                backgroundColor: chartColors,
                borderWidth: 0,
                hoverOffset: 10,
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '60%',
            plugins: {
                ...chartDefaults.plugins,
                legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 }, padding: 12, usePointStyle: true } }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
