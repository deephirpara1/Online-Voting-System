<?php
/**
 * Vote Process Handler
 */

define('BASE_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

if (!validateCsrfToken()) {
    setFlash('error', 'Invalid security token. Please try again.');
    redirect(getBaseUrl() . '/voter/elections.php');
}

$electionId  = intval($_POST['election_id'] ?? 0);
$candidateId = intval($_POST['candidate_id'] ?? 0);
$voterId     = $_SESSION['voter_id'];

// ─── Validation ─────────────────────────────────────────────────────────────

if ($electionId <= 0 || $candidateId <= 0) {
    setFlash('error', 'Invalid vote submission.');
    redirect(getBaseUrl() . '/voter/elections.php');
}

try {
    $db = Database::getConnection();

    // 1. Verify the election is active
    $stmt = $db->prepare("SELECT id, title FROM elections WHERE id = :id AND status = 'active'");
    $stmt->execute([':id' => $electionId]);
    $election = $stmt->fetch();

    if (!$election) {
        setFlash('error', 'This election is not active or does not exist.');
        redirect(getBaseUrl() . '/voter/elections.php');
        return;
    }

    // 2. Verify the candidate belongs to this election
    $stmt = $db->prepare('SELECT id, full_name FROM candidates WHERE id = :cid AND election_id = :eid');
    $stmt->execute([':cid' => $candidateId, ':eid' => $electionId]);
    $candidate = $stmt->fetch();

    if (!$candidate) {
        setFlash('error', 'Invalid candidate for this election.');
        redirect(getBaseUrl() . '/voter/vote.php?election_id=' . $electionId);
        return;
    }

    // 3. Check for duplicate vote (PHP-level check in addition to DB UNIQUE constraint)
    $stmt = $db->prepare('SELECT id FROM votes WHERE election_id = :eid AND voter_id = :vid');
    $stmt->execute([':eid' => $electionId, ':vid' => $voterId]);
    if ($stmt->fetch()) {
        setFlash('error', 'You have already voted in this election. Duplicate voting is not allowed.');
        redirect(getBaseUrl() . '/voter/elections.php');
        return;
    }

    // 4. Cast the vote
    $stmt = $db->prepare(
        'INSERT INTO votes (election_id, candidate_id, voter_id, ip_address)
         VALUES (:election_id, :candidate_id, :voter_id, :ip_address)'
    );
    $stmt->execute([
        ':election_id'  => $electionId,
        ':candidate_id' => $candidateId,
        ':voter_id'     => $voterId,
        ':ip_address'   => getClientIp(),
    ]);

    // 5. Audit log
    auditLog(
        'voter',
        $voterId,
        'VOTE_CAST',
        'Voted in election: ' . $election['title'] . ' for candidate: ' . $candidate['full_name']
    );

    setFlash('success', '✅ Your vote has been cast successfully! Thank you for participating in "' . $election['title'] . '".');
    redirect(getBaseUrl() . '/voter/elections.php');

} catch (PDOException $e) {
    // The UNIQUE constraint will also catch duplicates at DB level
    if ($e->getCode() == 23000) {
        setFlash('error', 'You have already voted in this election.');
    } else {
        error_log('Vote submission error: ' . $e->getMessage());
        setFlash('error', 'A system error occurred while processing your vote. Please try again.');
    }
    redirect(getBaseUrl() . '/voter/elections.php');
}
