<?php
/**
 * Election Process Handler
 * Handles create, update, delete, start, and end operations.
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

if (!validateCsrfToken()) {
    setFlash('error', 'Invalid security token. Please try again.');
    redirect(getBaseUrl() . '/admin/elections.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createElection();
        break;
    case 'update':
        updateElection();
        break;
    case 'delete':
        deleteElection();
        break;
    case 'start':
        changeElectionStatus('active');
        break;
    case 'end':
        changeElectionStatus('ended');
        break;
    default:
        redirect(getBaseUrl() . '/admin/elections.php');
}

function createElection(): void
{
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate   = $_POST['start_date'] ?? '';
    $endDate     = $_POST['end_date'] ?? '';

    if (empty($title) || empty($startDate) || empty($endDate)) {
        setFlash('error', 'Title, start date, and end date are required.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    if (strtotime($endDate) <= strtotime($startDate)) {
        setFlash('error', 'End date must be after start date.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO elections (title, description, start_date, end_date, status, created_by)
             VALUES (:title, :description, :start_date, :end_date, :status, :created_by)'
        );

        // Determine initial status
        $now = time();
        $start = strtotime($startDate);
        if ($start <= $now) {
            $status = 'active';
        } else {
            $status = 'upcoming';
        }

        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':start_date'  => $startDate,
            ':end_date'    => $endDate,
            ':status'      => $status,
            ':created_by'  => $_SESSION['admin_id'],
        ]);

        auditLog('admin', $_SESSION['admin_id'], 'ELECTION_CREATED', 'Created election: ' . $title);
        setFlash('success', 'Election created successfully!');
    } catch (PDOException $e) {
        error_log('Create election error: ' . $e->getMessage());
        setFlash('error', 'Failed to create election. Please try again.');
    }

    redirect(getBaseUrl() . '/admin/elections.php');
}

function updateElection(): void
{
    $id          = intval($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate   = $_POST['start_date'] ?? '';
    $endDate     = $_POST['end_date'] ?? '';

    if ($id <= 0 || empty($title) || empty($startDate) || empty($endDate)) {
        setFlash('error', 'All required fields must be filled.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    if (strtotime($endDate) <= strtotime($startDate)) {
        setFlash('error', 'End date must be after start date.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE elections SET title = :title, description = :description, 
             start_date = :start_date, end_date = :end_date
             WHERE id = :id'
        );
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':start_date'  => $startDate,
            ':end_date'    => $endDate,
            ':id'          => $id,
        ]);

        auditLog('admin', $_SESSION['admin_id'], 'ELECTION_UPDATED', 'Updated election ID: ' . $id);
        setFlash('success', 'Election updated successfully!');
    } catch (PDOException $e) {
        error_log('Update election error: ' . $e->getMessage());
        setFlash('error', 'Failed to update election.');
    }

    redirect(getBaseUrl() . '/admin/elections.php');
}

function deleteElection(): void
{
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        setFlash('error', 'Invalid election.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    try {
        $db = Database::getConnection();

        // Check for existing votes
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM votes WHERE election_id = :id');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetch()['count'] > 0) {
            setFlash('error', 'Cannot delete an election that has votes. End it instead.');
            redirect(getBaseUrl() . '/admin/elections.php');
            return;
        }

        $stmt = $db->prepare('DELETE FROM elections WHERE id = :id');
        $stmt->execute([':id' => $id]);

        auditLog('admin', $_SESSION['admin_id'], 'ELECTION_DELETED', 'Deleted election ID: ' . $id);
        setFlash('success', 'Election deleted successfully!');
    } catch (PDOException $e) {
        error_log('Delete election error: ' . $e->getMessage());
        setFlash('error', 'Failed to delete election.');
    }

    redirect(getBaseUrl() . '/admin/elections.php');
}

function changeElectionStatus(string $newStatus): void
{
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        setFlash('error', 'Invalid election.');
        redirect(getBaseUrl() . '/admin/elections.php');
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE elections SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $id]);

        $actionLabel = $newStatus === 'active' ? 'ELECTION_STARTED' : 'ELECTION_ENDED';
        auditLog('admin', $_SESSION['admin_id'], $actionLabel, 'Election ID: ' . $id . ' status changed to ' . $newStatus);

        $msg = $newStatus === 'active' ? 'Election started! Voting is now open.' : 'Election ended. Voting is closed.';
        setFlash('success', $msg);
    } catch (PDOException $e) {
        error_log('Change election status error: ' . $e->getMessage());
        setFlash('error', 'Failed to update election status.');
    }

    redirect(getBaseUrl() . '/admin/elections.php');
}
