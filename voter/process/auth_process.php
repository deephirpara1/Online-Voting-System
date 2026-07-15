<?php
/**
 * Voter Auth Process Handler
 * Handles login, logout, and password change for voters.
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleVoterLogin();
        break;
    case 'change_password':
        handleVoterChangePassword();
        break;
    default:
        redirect(getBaseUrl() . '/voter/login.php');
}

/**
 * Handle voter login.
 */
function handleVoterLogin(): void
{
    if (!validateCsrfToken()) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect(getBaseUrl() . '/voter/login.php');
        return;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        setFlash('error', 'Please fill in all fields.');
        redirect(getBaseUrl() . '/voter/login.php');
        return;
    }

    // Rate limiting check
    $rateLimitKey = 'voter_' . $username;
    if (!checkRateLimit($rateLimitKey)) {
        $waitSec = getRateLimitReset($rateLimitKey);
        $waitMin = ceil($waitSec / 60);
        setFlash('error', "Too many login attempts. Please try again in {$waitMin} minute(s).");
        redirect(getBaseUrl() . '/voter/login.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT id, voter_id, username, password, full_name, email, is_active 
             FROM voters WHERE username = :username'
        );
        $stmt->execute([':username' => $username]);
        $voter = $stmt->fetch();

        if ($voter && password_verify($password, $voter['password'])) {
            // Check if account is active
            if (!$voter['is_active']) {
                auditLog('voter', $voter['id'], 'LOGIN_BLOCKED', 'Disabled voter tried to log in');
                setFlash('error', 'Your account has been disabled. Please contact the administrator.');
                redirect(getBaseUrl() . '/voter/login.php');
                return;
            }

            // Login successful — clear rate limit
            clearRateLimit($rateLimitKey);
            regenerateSession();

            $_SESSION['voter_id']      = $voter['id'];
            $_SESSION['voter_uid']     = $voter['voter_id'];
            $_SESSION['voter_name']    = $voter['full_name'];
            $_SESSION['voter_user']    = $voter['username'];
            $_SESSION['user_type']     = 'voter';

            auditLog('voter', $voter['id'], 'LOGIN', 'Voter logged in successfully');

            setFlash('success', 'Welcome back, ' . $voter['full_name'] . '!');
            redirect(getBaseUrl() . '/voter/index.php');
        } else {
            // Login failed — record attempt
            recordFailedLogin($rateLimitKey);
            auditLog('voter', 0, 'LOGIN_FAILED', 'Failed login attempt for username: ' . $username);
            setFlash('error', 'Invalid username or password.');
            redirect(getBaseUrl() . '/voter/login.php');
        }
    } catch (PDOException $e) {
        error_log('Voter login error: ' . $e->getMessage());
        setFlash('error', 'A system error occurred. Please try again later.');
        redirect(getBaseUrl() . '/voter/login.php');
    }
}

/**
 * Handle voter password change.
 */
function handleVoterChangePassword(): void
{
    requireVoter();

    if (!validateCsrfToken()) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect(getBaseUrl() . '/voter/change_password.php');
        return;
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlash('error', 'All fields are required.');
        redirect(getBaseUrl() . '/voter/change_password.php');
        return;
    }

    if (strlen($newPassword) < 6) {
        setFlash('error', 'New password must be at least 6 characters long.');
        redirect(getBaseUrl() . '/voter/change_password.php');
        return;
    }

    if ($newPassword !== $confirmPassword) {
        setFlash('error', 'New passwords do not match.');
        redirect(getBaseUrl() . '/voter/change_password.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT password FROM voters WHERE id = :id');
        $stmt->execute([':id' => $_SESSION['voter_id']]);
        $voter = $stmt->fetch();

        if (!$voter || !password_verify($currentPassword, $voter['password'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect(getBaseUrl() . '/voter/change_password.php');
            return;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE voters SET password = :password WHERE id = :id');
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id'       => $_SESSION['voter_id'],
        ]);

        auditLog('voter', $_SESSION['voter_id'], 'PASSWORD_CHANGED', 'Voter changed their password');

        setFlash('success', 'Password changed successfully!');
        redirect(getBaseUrl() . '/voter/change_password.php');
    } catch (PDOException $e) {
        error_log('Voter password change error: ' . $e->getMessage());
        setFlash('error', 'A system error occurred. Please try again later.');
        redirect(getBaseUrl() . '/voter/change_password.php');
    }
}
