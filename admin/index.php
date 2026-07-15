<?php
/**
 * Admin Dashboard
 * Shows statistics, charts, and recent activity.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

$db = Database::getConnection();

// ─── Fetch Statistics ────────────────────────────────────────────────────────
$stats = [];

// Total voters
$stmt = $db->query('SELECT COUNT(*) as total FROM voters');
$stats['total_voters'] = $stmt->fetch()['total'];

// Active voters
$stmt = $db->query('SELECT COUNT(*) as total FROM voters WHERE is_active = 1');
$stats['active_voters'] = $stmt->fetch()['total'];

// Total candidates
$stmt = $db->query('SELECT COUNT(*) as total FROM candidates');
$stats['total_candidates'] = $stmt->fetch()['total'];

// Total elections
$stmt = $db->query('SELECT COUNT(*) as total FROM elections');
$stats['total_elections'] = $stmt->fetch()['total'];

// Active elections
$stmt = $db->query("SELECT COUNT(*) as total FROM elections WHERE status = 'active'");
$stats['active_elections'] = $stmt->fetch()['total'];

// Total votes cast
$stmt = $db->query('SELECT COUNT(*) as total FROM votes');
$stats['total_votes'] = $stmt->fetch()['total'];

// Voting percentage
$stats['vote_percentage'] = $stats['total_voters'] > 0
    ? round(($stats['total_votes'] / $stats['total_voters']) * 100, 1)
    : 0;

// ─── Election-wise vote data for chart ───────────────────────────────────────
$stmt = $db->query(
    "SELECT e.title, COUNT(v.id) as vote_count 
     FROM elections e 
     LEFT JOIN votes v ON e.id = v.election_id 
     GROUP BY e.id, e.title 
     ORDER BY e.id DESC 
     LIMIT 6"
);
$chartData = $stmt->fetchAll();

// ─── Recent Activity ─────────────────────────────────────────────────────────
$stmt = $db->query(
    "SELECT al.*, 
            CASE 
                WHEN al.user_type = 'admin' THEN (SELECT full_name FROM admins WHERE id = al.user_id)
                WHEN al.user_type = 'voter' THEN (SELECT full_name FROM voters WHERE id = al.user_id)
            END as user_name
     FROM audit_logs al 
     ORDER BY al.created_at DESC 
     LIMIT 10"
);
$recentActivity = $stmt->fetchAll();

// ─── Election Status Distribution ────────────────────────────────────────────
$stmt = $db->query(
    "SELECT status, COUNT(*) as count FROM elections GROUP BY status"
);
$electionStatus = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Top Navbar -->
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="navbar-title mb-0">Dashboard</h1>
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

    <!-- Content -->
    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4 stagger">
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card blue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Total Voters</div>
                            <div class="stat-value"><?= number_format($stats['total_voters']) ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card green">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Active Voters</div>
                            <div class="stat-value"><?= number_format($stats['active_voters']) ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card cyan">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Candidates</div>
                            <div class="stat-value"><?= number_format($stats['total_candidates']) ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card orange">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Elections</div>
                            <div class="stat-value"><?= number_format($stats['total_elections']) ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-poll"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card indigo">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Votes Cast</div>
                            <div class="stat-value"><?= number_format($stats['total_votes']) ?></div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-vote-yea"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6 animate-fade-in-up">
                <div class="stat-card red">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-label">Vote Rate</div>
                            <div class="stat-value"><?= $stats['vote_percentage'] ?>%</div>
                        </div>
                        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Votes per Election Bar Chart -->
            <div class="col-lg-8 animate-fade-in-up">
                <div class="card-custom">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-bar me-2 text-primary"></i>Votes per Election</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="votesBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Election Status Doughnut Chart -->
            <div class="col-lg-4 animate-fade-in-up">
                <div class="card-custom">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-pie me-2 text-success"></i>Election Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusDoughnutChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12 animate-fade-in-up">
                <div class="card-custom">
                    <div class="card-header">
                        <h6><i class="fas fa-clock me-2 text-info"></i>Recent Activity</h6>
                        <a href="<?= getBaseUrl() ?>/admin/audit_logs.php" class="btn btn-sm btn-outline-primary rounded-pill">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentActivity)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox d-block"></i>
                                <h5>No Activity Yet</h5>
                                <p>Activity will appear here once users start interacting with the system.</p>
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-2">
                                <?php foreach ($recentActivity as $activity): ?>
                                    <?php
                                    $iconClass = match(true) {
                                        str_contains($activity['action'], 'VOTE')     => 'vote',
                                        str_contains($activity['action'], 'ELECTION') => 'election',
                                        str_contains($activity['action'], 'LOGIN') || str_contains($activity['action'], 'LOGOUT') || str_contains($activity['action'], 'PASSWORD') => 'security',
                                        default => 'user',
                                    };
                                    $iconSymbol = match($iconClass) {
                                        'vote'     => 'fa-vote-yea',
                                        'election' => 'fa-poll',
                                        'security' => 'fa-shield-alt',
                                        default    => 'fa-user',
                                    };
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?= $iconClass ?>">
                                            <i class="fas <?= $iconSymbol ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6><?= sanitize($activity['action']) ?></h6>
                                            <p>
                                                <?= sanitize($activity['user_name'] ?? 'Unknown') ?>
                                                <?= $activity['description'] ? ' — ' . sanitize(truncate($activity['description'], 80)) : '' ?>
                                            </p>
                                        </div>
                                        <span class="activity-time"><?= timeAgo($activity['created_at']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Votes per Election Bar Chart ────────────────────────────────────
    const barCtx = document.getElementById('votesBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($chartData, 'title')) ?>,
            datasets: [{
                label: 'Votes',
                data: <?= json_encode(array_map('intval', array_column($chartData, 'vote_count'))) ?>,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(28, 200, 138, 0.8)',
                    'rgba(246, 194, 62, 0.8)',
                    'rgba(231, 74, 59, 0.8)',
                    'rgba(54, 185, 204, 0.8)'
                ],
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 40,
            }]
        },
        options: {
            ...chartDefaults,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { 
                        stepSize: 1,
                        font: { family: 'Inter' } 
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { family: 'Inter', size: 11 } },
                    grid: { display: false }
                }
            },
            plugins: {
                ...chartDefaults.plugins,
                legend: { display: false }
            }
        }
    });

    // ─── Election Status Doughnut Chart ──────────────────────────────────
    const statusData = <?= json_encode($electionStatus) ?>;
    const statusLabels = statusData.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1));
    const statusValues = statusData.map(s => parseInt(s.count));
    const statusColors = statusData.map(s => {
        if (s.status === 'active') return '#1cc88a';
        if (s.status === 'upcoming') return '#f6c23e';
        return '#858796';
    });

    const doughnutCtx = document.getElementById('statusDoughnutChart').getContext('2d');
    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.length ? statusLabels : ['No Elections'],
            datasets: [{
                data: statusValues.length ? statusValues : [1],
                backgroundColor: statusColors.length ? statusColors : ['#e9ecef'],
                borderWidth: 0,
                hoverOffset: 8,
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '65%',
            plugins: {
                ...chartDefaults.plugins,
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 12 },
                        padding: 15,
                        usePointStyle: true,
                    }
                }
            }
        }
    });
});
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
