<?php
/**
 * Voter Process Handler (Admin side)
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

if (!validateCsrfToken()) {
    setFlash('error', 'Invalid security token.');
    redirect(getBaseUrl() . '/admin/voters.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':      createVoter(); break;
    case 'update':      updateVoter(); break;
    case 'delete':      deleteVoter(); break;
    case 'toggle_status': toggleVoterStatus(); break;
    default: redirect(getBaseUrl() . '/admin/voters.php');
}

function createVoter(): void
{
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $address  = trim($_POST['address'] ?? '');

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        setFlash('error', 'Name, username, email, and password are required.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email format.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    try {
        $db = Database::getConnection();

        // Check for duplicate username or email
        $stmt = $db->prepare('SELECT id FROM voters WHERE username = :u OR email = :e');
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            setFlash('error', 'Username or email already exists.');
            redirect(getBaseUrl() . '/admin/voters.php');
            return;
        }

        $voterId = generateVoterId();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            'INSERT INTO voters (voter_id, username, password, full_name, email, phone, address, is_active)
             VALUES (:voter_id, :username, :password, :full_name, :email, :phone, :address, 1)'
        );
        $stmt->execute([
            ':voter_id'  => $voterId,
            ':username'  => $username,
            ':password'  => $hashedPassword,
            ':full_name' => $fullName,
            ':email'     => $email,
            ':phone'     => $phone,
            ':address'   => $address,
        ]);

        auditLog('admin', $_SESSION['admin_id'], 'VOTER_CREATED', 'Created voter: ' . $fullName . ' (' . $voterId . ')');
        setFlash('success', 'Voter created successfully! Voter ID: ' . $voterId);
    } catch (PDOException $e) {
        error_log('Create voter error: ' . $e->getMessage());
        setFlash('error', 'Failed to create voter.');
    }

    redirect(getBaseUrl() . '/admin/voters.php');
}

function updateVoter(): void
{
    $id       = intval($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $address  = trim($_POST['address'] ?? '');

    if ($id <= 0 || empty($fullName) || empty($username) || empty($email)) {
        setFlash('error', 'Name, username, and email are required.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Invalid email format.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    try {
        $db = Database::getConnection();

        // Check for duplicates (excluding current voter)
        $stmt = $db->prepare('SELECT id FROM voters WHERE (username = :u OR email = :e) AND id != :id');
        $stmt->execute([':u' => $username, ':e' => $email, ':id' => $id]);
        if ($stmt->fetch()) {
            setFlash('error', 'Username or email already exists.');
            redirect(getBaseUrl() . '/admin/voters.php');
            return;
        }

        $passwordSql = '';
        $params = [
            ':full_name' => $fullName,
            ':username'  => $username,
            ':email'     => $email,
            ':phone'     => $phone,
            ':address'   => $address,
            ':id'        => $id,
        ];

        if (!empty($password)) {
            if (strlen($password) < 6) {
                setFlash('error', 'Password must be at least 6 characters.');
                redirect(getBaseUrl() . '/admin/voters.php');
                return;
            }
            $passwordSql = ', password = :password';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = $db->prepare(
            "UPDATE voters SET full_name = :full_name, username = :username, email = :email, 
             phone = :phone, address = :address{$passwordSql} WHERE id = :id"
        );
        $stmt->execute($params);

        auditLog('admin', $_SESSION['admin_id'], 'VOTER_UPDATED', 'Updated voter ID: ' . $id);
        setFlash('success', 'Voter updated successfully!');
    } catch (PDOException $e) {
        error_log('Update voter error: ' . $e->getMessage());
        setFlash('error', 'Failed to update voter.');
    }

    redirect(getBaseUrl() . '/admin/voters.php');
}

function deleteVoter(): void
{
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        setFlash('error', 'Invalid voter.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM voters WHERE id = :id');
        $stmt->execute([':id' => $id]);

        auditLog('admin', $_SESSION['admin_id'], 'VOTER_DELETED', 'Deleted voter ID: ' . $id);
        setFlash('success', 'Voter deleted successfully!');
    } catch (PDOException $e) {
        error_log('Delete voter error: ' . $e->getMessage());
        setFlash('error', 'Failed to delete voter.');
    }

    redirect(getBaseUrl() . '/admin/voters.php');
}

function toggleVoterStatus(): void
{
    $id       = intval($_POST['id'] ?? 0);
    $isActive = intval($_POST['is_active'] ?? 0);

    if ($id <= 0) {
        setFlash('error', 'Invalid voter.');
        redirect(getBaseUrl() . '/admin/voters.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE voters SET is_active = :is_active WHERE id = :id');
        $stmt->execute([':is_active' => $isActive, ':id' => $id]);

        $statusText = $isActive ? 'enabled' : 'disabled';
        auditLog('admin', $_SESSION['admin_id'], 'VOTER_STATUS_CHANGED', "Voter ID: $id $statusText");
        setFlash('success', 'Voter account ' . $statusText . ' successfully!');
    } catch (PDOException $e) {
        error_log('Toggle voter status error: ' . $e->getMessage());
        setFlash('error', 'Failed to update voter status.');
    }

    redirect(getBaseUrl() . '/admin/voters.php');
}
