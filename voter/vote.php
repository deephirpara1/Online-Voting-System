<?php
/**
 * Voting Page — Ballot UI
 * Allows voters to select a candidate and cast their vote.
 */

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once BASE_PATH . 'includes/auth.php';
require_once BASE_PATH . 'includes/functions.php';

requireVoter();

$db = Database::getConnection();
$voterId = $_SESSION['voter_id'];
$electionId = intval($_GET['election_id'] ?? 0);

if ($electionId <= 0) {
    setFlash('error', 'Invalid election.');
    redirect(getBaseUrl() . '/voter/elections.php');
}

// Fetch election
$stmt = $db->prepare("SELECT * FROM elections WHERE id = :id AND status = 'active'");
$stmt->execute([':id' => $electionId]);
$election = $stmt->fetch();

if (!$election) {
    setFlash('error', 'Election not found or not active.');
    redirect(getBaseUrl() . '/voter/elections.php');
}

// Check if already voted
$stmt = $db->prepare('SELECT id FROM votes WHERE election_id = :eid AND voter_id = :vid');
$stmt->execute([':eid' => $electionId, ':vid' => $voterId]);
if ($stmt->fetch()) {
    setFlash('info', 'You have already voted in this election.');
    redirect(getBaseUrl() . '/voter/elections.php');
}

// Fetch candidates
$stmt = $db->prepare('SELECT * FROM candidates WHERE election_id = :eid ORDER BY full_name ASC');
$stmt->execute([':eid' => $electionId]);
$candidates = $stmt->fetchAll();

$pageTitle = 'Vote — ' . $election['title'];
require_once BASE_PATH . 'includes/header.php';
require_once BASE_PATH . 'includes/voter_sidebar.php';
?>

<div class="main-content">
    <nav class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
            <h1 class="navbar-title mb-0"><?= sanitize($election['title']) ?></h1>
        </div>
        <div class="dropdown user-dropdown">
            <button class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['voter_name'] ?? 'V', 0, 1)) ?></div>
                <span class="d-none d-md-inline"><?= sanitize($_SESSION['voter_name'] ?? 'Voter') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= getBaseUrl() ?>/voter/profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= getBaseUrl() ?>/voter/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <?= renderFlashMessages() ?>

        <!-- Election Info Banner -->
        <div class="card-custom mb-4 animate-fade-in-up">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><?= sanitize($election['title']) ?></h5>
                        <p class="text-muted mb-0"><?= sanitize($election['description'] ?? '') ?></p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Voting ends</small>
                        <strong class="text-danger"><?= formatDate($election['end_date'], 'M d, Y h:i A') ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instruction -->
        <div class="alert alert-info mb-4 animate-fade-in-up">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Select your candidate</strong> below and click "Cast Vote". Your vote is final and cannot be changed.
        </div>

        <!-- Candidates Grid -->
        <form method="POST" action="<?= getBaseUrl() ?>/voter/process/vote_process.php" id="voteForm">
            <?= csrfField() ?>
            <input type="hidden" name="election_id" value="<?= $electionId ?>">

            <?php if (empty($candidates)): ?>
                <div class="card-custom">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-user-slash d-block"></i>
                            <h5>No Candidates</h5>
                            <p>No candidates have been registered for this election yet.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4 stagger mb-4">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="col-lg-4 col-md-6 animate-fade-in-up">
                            <div class="candidate-card position-relative" onclick="selectCandidate(<?= $candidate['id'] ?>, this)">
                                <!-- Photo -->
                                <?php if ($candidate['photo']): ?>
                                    <img src="<?= getBaseUrl() ?>/uploads/candidates/<?= sanitize($candidate['photo']) ?>" 
                                         alt="<?= sanitize($candidate['full_name']) ?>" class="candidate-photo">
                                <?php else: ?>
                                    <div class="candidate-photo d-flex align-items-center justify-content-center" 
                                         style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="fas fa-user" style="font-size: 4rem; color: rgba(255,255,255,0.5);"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Selection Indicator -->
                                <div class="select-indicator">
                                    <i class="fas fa-check" style="display:none;"></i>
                                </div>

                                <!-- Candidate Info -->
                                <div class="candidate-info">
                                    <div class="candidate-name"><?= sanitize($candidate['full_name']) ?></div>
                                    <div class="candidate-party">
                                        <?php if ($candidate['symbol']): ?>
                                            <span><?= $candidate['symbol'] ?></span>
                                        <?php endif; ?>
                                        <?= sanitize($candidate['party'] ?? 'Independent') ?>
                                    </div>
                                    <?php if ($candidate['manifesto']): ?>
                                        <hr class="my-2">
                                        <p class="text-muted small mb-0"><?= sanitize(truncate($candidate['manifesto'], 150)) ?></p>
                                    <?php endif; ?>
                                </div>

                                <input type="radio" name="candidate_id" value="<?= $candidate['id'] ?>" 
                                       class="d-none candidate-radio" id="candidate_<?= $candidate['id'] ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <div class="text-center animate-fade-in-up">
                    <button type="submit" class="btn btn-primary-gradient btn-lg px-5" id="voteBtn" disabled>
                        <i class="fas fa-vote-yea me-2"></i> Cast My Vote
                    </button>
                    <p class="text-muted mt-2 small">
                        <i class="fas fa-lock me-1"></i> Your vote is anonymous and secure. This action cannot be undone.
                    </p>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
let selectedCard = null;

function selectCandidate(candidateId, cardElement) {
    // Remove selection from all cards
    document.querySelectorAll('.candidate-card').forEach(card => {
        card.classList.remove('selected');
        card.querySelector('.select-indicator i').style.display = 'none';
    });

    // Select this card
    cardElement.classList.add('selected');
    cardElement.querySelector('.select-indicator i').style.display = 'block';

    // Check the hidden radio
    document.getElementById('candidate_' + candidateId).checked = true;

    // Enable vote button
    document.getElementById('voteBtn').disabled = false;
    selectedCard = cardElement;
}

document.getElementById('voteForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const selected = document.querySelector('input[name="candidate_id"]:checked');
    if (!selected) {
        showToast('Please select a candidate first', 'warning');
        return;
    }

    const candidateName = selectedCard.querySelector('.candidate-name').textContent;

    Swal.fire({
        title: 'Confirm Your Vote',
        html: `You are voting for <strong>${candidateName}</strong>.<br><br>
               <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>This action cannot be undone.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check me-1"></i> Confirm Vote',
        cancelButtonText: 'Go Back',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            setButtonLoading(document.getElementById('voteBtn'), true);
            e.target.submit();
        }
    });
});
</script>

<?php require_once BASE_PATH . 'includes/footer.php'; ?>
