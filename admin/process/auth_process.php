<?php
/**
 * Admin Auth Process Handler
 * Handles login, logout, and password change.
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleAdminLogin();
        break;
    case 'change_password':
        handleAdminChangePassword();
        break;
    default:
        redirect(getBaseUrl() . '/admin/login.php');
}

/**
 * Handle admin login.
 */
function handleAdminLogin(): void
{
    // Validate CSRF
    if (!validateCsrfToken()) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect(getBaseUrl() . '/admin/login.php');
        return;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        setFlash('error', 'Please fill in all fields.');
        redirect(getBaseUrl() . '/admin/login.php');
        return;
    }

    // Rate limiting check
    $rateLimitKey = 'admin_' . $username;
    if (!checkRateLimit($rateLimitKey)) {
        $waitSec = getRateLimitReset($rateLimitKey);
        $waitMin = ceil($waitSec / 60);
        setFlash('error', "Too many login attempts. Please try again in {$waitMin} minute(s).");
        redirect(getBaseUrl() . '/admin/login.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, username, password, full_name, email FROM admins WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful — clear rate limit
            clearRateLimit($rateLimitKey);
            regenerateSession();

            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['user_type']  = 'admin';

            // Audit log
            auditLog('admin', $admin['id'], 'LOGIN', 'Admin logged in successfully');

            setFlash('success', 'Welcome back, ' . $admin['full_name'] . '!');
            redirect(getBaseUrl() . '/admin/index.php');
        } else {
            // Login failed — record attempt
            recordFailedLogin($rateLimitKey);
            auditLog('admin', 0, 'LOGIN_FAILED', 'Failed login attempt for username: ' . $username);

            setFlash('error', 'Invalid username or password.');
            redirect(getBaseUrl() . '/admin/login.php');
        }
    } catch (PDOException $e) {
        error_log('Admin login error: ' . $e->getMessage());
        setFlash('error', 'A system error occurred. Please try again later.');
        redirect(getBaseUrl() . '/admin/login.php');
    }
}

/**
 * Handle admin password change.
 */
function handleAdminChangePassword(): void
{
    requireAdmin();

    if (!validateCsrfToken()) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect(getBaseUrl() . '/admin/change_password.php');
        return;
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlash('error', 'All fields are required.');
        redirect(getBaseUrl() . '/admin/change_password.php');
        return;
    }

    if (strlen($newPassword) < 6) {
        setFlash('error', 'New password must be at least 6 characters long.');
        redirect(getBaseUrl() . '/admin/change_password.php');
        return;
    }

    if ($newPassword !== $confirmPassword) {
        setFlash('error', 'New passwords do not match.');
        redirect(getBaseUrl() . '/admin/change_password.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT password FROM admins WHERE id = :id');
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect(getBaseUrl() . '/admin/change_password.php');
            return;
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE admins SET password = :password WHERE id = :id');
        $stmt->execute([
            ':password' => $hashedPassword,
            ':id'       => $_SESSION['admin_id'],
        ]);

        auditLog('admin', $_SESSION['admin_id'], 'PASSWORD_CHANGED', 'Admin changed their password');

        setFlash('success', 'Password changed successfully!');
        redirect(getBaseUrl() . '/admin/change_password.php');
    } catch (PDOException $e) {
        error_log('Admin password change error: ' . $e->getMessage());
        setFlash('error', 'A system error occurred. Please try again later.');
        redirect(getBaseUrl() . '/admin/change_password.php');
    }
}
