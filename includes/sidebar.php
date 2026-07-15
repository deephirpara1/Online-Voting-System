<?php
/**
 * Admin Sidebar Navigation
 */

// Determine the current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="sidebar-brand-text">
            <?= APP_NAME ?>
            <small>Admin Panel</small>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-heading">Main</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/index.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Management</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'elections' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/elections.php">
                <i class="fas fa-poll"></i>
                <span>Elections</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'candidates' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/candidates.php">
                <i class="fas fa-user-tie"></i>
                <span>Candidates</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'voters' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/voters.php">
                <i class="fas fa-users"></i>
                <span>Voters</span>
            </a>
        </li>
    </ul>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Reports</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'results' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/results.php">
                <i class="fas fa-chart-bar"></i>
                <span>Results</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'audit_logs' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/audit_logs.php">
                <i class="fas fa-clipboard-list"></i>
                <span>Audit Logs</span>
            </a>
        </li>
    </ul>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Account</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'change_password' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/admin/change_password.php">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?= getBaseUrl() ?>/admin/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
