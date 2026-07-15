<?php
/**
 * Admin Logout
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

// Log the logout action before destroying the session
if (isAdminLoggedIn()) {
    auditLog('admin', $_SESSION['admin_id'], 'LOGOUT', 'Admin logged out');
}

destroySession(getBaseUrl() . '/admin/login.php');
