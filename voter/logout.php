<?php
/**
 * Voter Logout
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

if (isVoterLoggedIn()) {
    auditLog('voter', $_SESSION['voter_id'], 'LOGOUT', 'Voter logged out');
}

destroySession(getBaseUrl() . '/voter/login.php');
