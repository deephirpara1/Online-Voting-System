<?php
/**
 * Admin Results Page
 * View live voting statistics and export results to CSV.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

// ─── CSV Export ──────────────────────────────────────────────────────────────
if (isset($_GET['export']) && isset($_GET['election_id'])) {
    $eid = intval($_GET['election_id']);
    $stmt = $db->prepare(
        "SELECT c.full_name, c.party, COUNT(v.id) as votes
         FROM candidates c
         LEFT JOIN votes v ON c.id = v.candidate_id
         WHERE c.election_id = :eid
         GROUP BY c.id, c.full_name, c.party
         ORDER BY votes DESC"
    );
    $stmt->execute([':eid' => $eid]);
    $results = $stmt->fetchAll();

    // Get election title
    $stmt2 = $db->prepare('SELECT title FROM elections WHERE id = :id');
    $stmt2->execute([':id' => $eid]);
    $elTitle = $stmt2->fetch()['title'] ?? 'Election';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="results_' . preg_replace('/[^a-zA-Z0-9]/', '_', $elTitle) . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Rank', 'Candidate', 'Party', 'Votes', 'Percentage']);

    $totalVotes = array_sum(array_column($results, 'votes'));
    foreach ($results as $i => $row) {
        $pct = $totalVotes > 0 ? round(($row['votes'] / $totalVotes) * 100, 1) : 0;
        fputcsv($output, [$i + 1, $row['full_name'], $row['party'] ?? 'Independent', $row['votes'], $pct . '%']);
    }
    fclose($output);
    exit;
}

// ─── Page Data ───────────────────────────────────────────────────────────────
$stmt = $db->query("SELECT id, title, status FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll();

$selectedElectionId = intval($_GET['election_id'] ?? ($elections[0]['id'] ?? 0));
$selectedElection = null;
$resultData = [];
$totalVotes = 0;

if ($selectedElectionId > 0) {
    $stmt = $db->prepare('SELECT * FROM elections WHERE id = :id');
    $stmt->execute([':id' => $selectedElectionId]);
    $selectedElection = $stmt->fetch();

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

$pageTitle = 'Results';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0">Election Results</h1>
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

        <!-- Election Selector -->
        <div class="card-custom mb-4 animate-fade-in-up">
            <div class="card-body">
                <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                    <div class="flex-grow-1">
                        <label class="form-label-custom">Select Election</label>
                        <select class="form-select form-control-custom" name="election_id" onchange="this.form.submit()">
                            <?php foreach ($elections as $el): ?>
                                <option value="<?= $el['id'] ?>" <?= $el['id'] == $selectedElectionId ? 'selected' : '' ?>>
                                    <?= sanitize($el['title']) ?> (<?= ucfirst($el['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selectedElectionId > 0): ?>
                        <a href="<?= getBaseUrl() ?>/admin/results.php?export=1&election_id=<?= $selectedElectionId ?>" 
                           class="btn btn-primary-gradient">
                            <i class="fas fa-download me-1"></i> Export CSV
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($selectedElection && !empty($resultData)): ?>
            <!-- Results Summary -->
            <div class="row g-3 mb-4 stagger">
                <div class="col-md-4 animate-fade-in-up">
                    <div class="stat-card blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Total Votes</div>
                                <div class="stat-value"><?= number_format($totalVotes) ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-vote-yea"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-in-up">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Candidates</div>
                                <div class="stat-value"><?= count($resultData) ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 animate-fade-in-up">
                    <div class="stat-card orange">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Status</div>
                                <div class="stat-value" style="font-size:1.2rem;"><?= electionStatusBadge($selectedElection['status']) ?></div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-info-circle"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts & Results -->
            <div class="row g-4 mb-4">
                <div class="col-lg-5 animate-fade-in-up">
                    <div class="card-custom h-100">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-pie me-2 text-info"></i>Vote Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="resultsPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 animate-fade-in-up">
                    <div class="card-custom h-100">
                        <div class="card-header">
                            <h6><i class="fas fa-chart-bar me-2 text-primary"></i>Votes by Candidate</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="resultsBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card-custom animate-fade-in-up">
                <div class="card-header">
                    <h6><i class="fas fa-trophy me-2 text-warning"></i>Detailed Results</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Candidate</th>
                                    <th>Party</th>
                                    <th>Votes</th>
                                    <th>Percentage</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultData as $i => $row): ?>
                                    <?php $pct = $totalVotes > 0 ? round(($row['votes'] / $totalVotes) * 100, 1) : 0; ?>
                                    <tr>
                                        <td>
                                            <?php if ($i === 0 && $totalVotes > 0): ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i>1st</span>
                                            <?php else: ?>
                                                <?= $i + 1 ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= sanitize($row['full_name']) ?></strong></td>
                                        <td><?= sanitize($row['party'] ?? 'Independent') ?> <?= $row['symbol'] ?? '' ?></td>
                                        <td><span class="badge bg-primary"><?= number_format($row['votes']) ?></span></td>
                                        <td><strong><?= $pct ?>%</strong></td>
                                        <td style="min-width: 150px;">
                                            <div class="result-bar">
                                                <div class="result-bar-fill" style="width: <?= $pct ?>%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif (empty($elections)): ?>
            <div class="card-custom">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-poll d-block"></i>
                        <h5>No Elections</h5>
                        <p>Create an election first to see results.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($selectedElection && !empty($resultData)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode(array_column($resultData, 'full_name')) ?>;
    const votes = <?= json_encode(array_map('intval', array_column($resultData, 'votes'))) ?>;

    // Pie Chart
    new Chart(document.getElementById('resultsPieChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: votes,
                backgroundColor: chartColors.slice(0, labels.length),
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

    // Bar Chart
    new Chart(document.getElementById('resultsBarChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votes',
                data: votes,
                backgroundColor: chartColors.slice(0, labels.length).map(c => c + 'cc'),
                borderRadius: 8,
                barThickness: 35,
            }]
        },
        options: {
            ...chartDefaults,
            indexAxis: 'y',
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter' } }, grid: { color: 'rgba(0,0,0,0.05)' } },
                y: { ticks: { font: { family: 'Inter', size: 12 } }, grid: { display: false } }
            },
            plugins: { ...chartDefaults.plugins, legend: { display: false } }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
