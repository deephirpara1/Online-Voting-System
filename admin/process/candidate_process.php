<?php
/**
 * Candidate Process Handler
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireAdmin();

if (!validateCsrfToken()) {
    setFlash('error', 'Invalid security token.');
    redirect(getBaseUrl() . '/admin/candidates.php');
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createCandidate();
        break;
    case 'update':
        updateCandidate();
        break;
    case 'delete':
        deleteCandidate();
        break;
    default:
        redirect(getBaseUrl() . '/admin/candidates.php');
}

function createCandidate(): void
{
    $fullName   = trim($_POST['full_name'] ?? '');
    $electionId = intval($_POST['election_id'] ?? 0);
    $party      = trim($_POST['party'] ?? '');
    $symbol     = trim($_POST['symbol'] ?? '');
    $manifesto  = trim($_POST['manifesto'] ?? '');

    if (empty($fullName) || $electionId <= 0) {
        setFlash('error', 'Name and election are required.');
        redirect(getBaseUrl() . '/admin/candidates.php');
        return;
    }

    // Handle photo upload
    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoName = uploadFile($_FILES['photo'], CANDIDATE_PHOTOS);
        if ($photoName === false) {
            setFlash('error', 'Photo upload failed. Check file type and size (max 2MB).');
            redirect(getBaseUrl() . '/admin/candidates.php');
            return;
        }
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO candidates (election_id, full_name, party, symbol, photo, manifesto)
             VALUES (:election_id, :full_name, :party, :symbol, :photo, :manifesto)'
        );
        $stmt->execute([
            ':election_id' => $electionId,
            ':full_name'   => $fullName,
            ':party'       => $party,
            ':symbol'      => $symbol,
            ':photo'       => $photoName,
            ':manifesto'   => $manifesto,
        ]);

        auditLog('admin', $_SESSION['admin_id'], 'CANDIDATE_CREATED', 'Added candidate: ' . $fullName);
        setFlash('success', 'Candidate added successfully!');
    } catch (PDOException $e) {
        error_log('Create candidate error: ' . $e->getMessage());
        setFlash('error', 'Failed to add candidate.');
    }

    redirect(getBaseUrl() . '/admin/candidates.php');
}

function updateCandidate(): void
{
    $id         = intval($_POST['id'] ?? 0);
    $fullName   = trim($_POST['full_name'] ?? '');
    $electionId = intval($_POST['election_id'] ?? 0);
    $party      = trim($_POST['party'] ?? '');
    $symbol     = trim($_POST['symbol'] ?? '');
    $manifesto  = trim($_POST['manifesto'] ?? '');

    if ($id <= 0 || empty($fullName) || $electionId <= 0) {
        setFlash('error', 'Name and election are required.');
        redirect(getBaseUrl() . '/admin/candidates.php');
        return;
    }

    try {
        $db = Database::getConnection();

        // Handle photo upload
        $photoSql = '';
        $params = [
            ':full_name'   => $fullName,
            ':election_id' => $electionId,
            ':party'       => $party,
            ':symbol'      => $symbol,
            ':manifesto'   => $manifesto,
            ':id'          => $id,
        ];

        if (!empty($_FILES['photo']['name'])) {
            $photoName = uploadFile($_FILES['photo'], CANDIDATE_PHOTOS);
            if ($photoName === false) {
                setFlash('error', 'Photo upload failed.');
                redirect(getBaseUrl() . '/admin/candidates.php');
                return;
            }

            // Delete old photo
            $stmt = $db->prepare('SELECT photo FROM candidates WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $old = $stmt->fetch();
            if ($old && $old['photo']) {
                deleteUploadedFile(CANDIDATE_PHOTOS . $old['photo']);
            }

            $photoSql = ', photo = :photo';
            $params[':photo'] = $photoName;
        }

        $stmt = $db->prepare(
            "UPDATE candidates SET full_name = :full_name, election_id = :election_id,
             party = :party, symbol = :symbol, manifesto = :manifesto{$photoSql}
             WHERE id = :id"
        );
        $stmt->execute($params);

        auditLog('admin', $_SESSION['admin_id'], 'CANDIDATE_UPDATED', 'Updated candidate ID: ' . $id);
        setFlash('success', 'Candidate updated successfully!');
    } catch (PDOException $e) {
        error_log('Update candidate error: ' . $e->getMessage());
        setFlash('error', 'Failed to update candidate.');
    }

    redirect(getBaseUrl() . '/admin/candidates.php');
}

function deleteCandidate(): void
{
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        setFlash('error', 'Invalid candidate.');
        redirect(getBaseUrl() . '/admin/candidates.php');
        return;
    }

    try {
        $db = Database::getConnection();

        // Delete photo file
        $stmt = $db->prepare('SELECT photo FROM candidates WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $candidate = $stmt->fetch();
        if ($candidate && $candidate['photo']) {
            deleteUploadedFile(CANDIDATE_PHOTOS . $candidate['photo']);
        }

        $stmt = $db->prepare('DELETE FROM candidates WHERE id = :id');
        $stmt->execute([':id' => $id]);

        auditLog('admin', $_SESSION['admin_id'], 'CANDIDATE_DELETED', 'Deleted candidate ID: ' . $id);
        setFlash('success', 'Candidate deleted successfully!');
    } catch (PDOException $e) {
        error_log('Delete candidate error: ' . $e->getMessage());
        setFlash('error', 'Failed to delete candidate.');
    }

    redirect(getBaseUrl() . '/admin/candidates.php');
}
