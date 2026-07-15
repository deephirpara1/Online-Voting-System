<?php
/**
 * Voter Sidebar Navigation
 */

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
            <small>Voter Portal</small>
        </div>
    </div>

    <!-- Navigation -->
    <div class="sidebar-heading">Main</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/voter/index.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Voting</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'elections' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/voter/elections.php">
                <i class="fas fa-vote-yea"></i>
                <span>Active Elections</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'results' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/voter/results.php">
                <i class="fas fa-chart-pie"></i>
                <span>Results</span>
            </a>
        </li>
    </ul>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Account</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/voter/profile.php">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage === 'change_password' ? 'active' : '' ?>" href="<?= getBaseUrl() ?>/voter/change_password.php">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?= getBaseUrl() ?>/voter/logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
