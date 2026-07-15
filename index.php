<?php
/**
 * VoteSecure — Landing Page
 * Routes users to admin or voter login.
 */

define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

// If already logged in, redirect to the appropriate dashboard
if (isAdminLoggedIn()) {
    redirect(getBaseUrl() . '/admin/index.php');
}
if (isVoterLoggedIn()) {
    redirect(getBaseUrl() . '/voter/index.php');
}

$pageTitle = 'Welcome';
require_once BASE_PATH . 'includes/header.php';
?>

<div class="landing-page">
    <!-- Floating particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <!-- Navigation -->
    <nav class="landing-nav">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="landing-brand">
                <i class="fas fa-shield-halved"></i>
                <?= APP_NAME ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= getBaseUrl() ?>/admin/login.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="fas fa-lock me-1"></i> Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="landing-content">
            <h1>Secure <span>Online Voting</span> Made Simple</h1>
            <p>
                A modern, transparent, and secure voting platform designed for fair elections.
                Cast your vote with confidence — every vote counts, every voice matters.
            </p>
            <div class="landing-buttons">
                <a href="<?= getBaseUrl() ?>/voter/login.php" class="btn-landing btn-landing-primary">
                    <i class="fas fa-vote-yea"></i>
                    Voter Login
                </a>
                <a href="<?= getBaseUrl() ?>/admin/login.php" class="btn-landing btn-landing-outline">
                    <i class="fas fa-user-shield"></i>
                    Admin Login
                </a>
            </div>
        </div>
    </section>

    <!-- Feature Cards -->
    <section class="landing-features">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h5>End-to-End Security</h5>
                        <p>Encrypted sessions, CSRF protection, and tamper-proof vote storage ensure election integrity.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(28, 200, 138, 0.15); color: #1cc88a;">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h5>One Vote Guarantee</h5>
                        <p>Advanced duplicate detection ensures each voter can only vote once per election — no exceptions.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: rgba(246, 194, 62, 0.15); color: #f6c23e;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5>Real-Time Results</h5>
                        <p>Live dashboards with interactive charts provide instant visibility into election outcomes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. Built with <i class="fas fa-heart text-danger"></i> for democracy.</p>
    </footer>
</div>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
