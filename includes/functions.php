<?php
/**
 * Shared Utility Functions
 * Online Voting System
 */

require_once __DIR__ . '/../config/db.php';

// ─── Input Sanitization ─────────────────────────────────────────────────────

/**
 * Sanitize a string for safe HTML output.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize an entire array (e.g., $_POST).
 */
function sanitizeArray(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $clean[$key] = sanitizeArray($value);
        } else {
            $clean[$key] = sanitize($value);
        }
    }
    return $clean;
}

// ─── Redirect & Flash Messages ──────────────────────────────────────────────

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message in the session.
 * @param string $type  'success', 'error', 'warning', 'info'
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a flash message.
 */
function getFlash(string $type): ?string
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render all flash messages as Bootstrap alerts.
 */
function renderFlashMessages(): string
{
    $html = '';
    $types = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'info',
    ];

    foreach ($types as $key => $bsClass) {
        $msg = getFlash($key);
        if ($msg) {
            $html .= '<div class="alert alert-' . $bsClass . ' alert-dismissible fade show" role="alert">'
                    . sanitize($msg)
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                    . '</div>';
        }
    }

    return $html;
}

// ─── Audit Logging ──────────────────────────────────────────────────────────

/**
 * Log an action to the audit_logs table.
 */
function auditLog(string $userType, int $userId, string $action, string $description = ''): void
{
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_type, user_id, action, description, ip_address)
             VALUES (:user_type, :user_id, :action, :description, :ip_address)'
        );
        $stmt->execute([
            ':user_type'   => $userType,
            ':user_id'     => $userId,
            ':action'      => $action,
            ':description' => $description,
            ':ip_address'  => getClientIp(),
        ]);
    } catch (PDOException $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}

// ─── Date & Formatting ──────────────────────────────────────────────────────

/**
 * Format a datetime string for display.
 */
function formatDate(string $date, string $format = 'M d, Y h:i A'): string
{
    return date($format, strtotime($date));
}

/**
 * Get a human-readable "time ago" string.
 */
function timeAgo(string $datetime): string
{
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ─── File Upload ─────────────────────────────────────────────────────────────

/**
 * Handle a file upload.
 * @return string|false  The new filename on success, false on failure.
 */
function uploadFile(array $file, string $destination, array $allowedTypes = []): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = !empty($allowedTypes) ? $allowedTypes : ALLOWED_EXTENSIONS;
    if (!in_array($ext, $allowed)) {
        return false;
    }

    // Verify it's actually an image
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $validMimes)) {
        return false;
    }

    // Generate a unique filename
    $newName = uniqid('img_', true) . '.' . $ext;

    // Create destination directory if needed
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }

    // Move the file
    if (move_uploaded_file($file['tmp_name'], $destination . $newName)) {
        return $newName;
    }

    return false;
}

/**
 * Delete an uploaded file.
 */
function deleteUploadedFile(string $filepath): bool
{
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// ─── Miscellaneous ──────────────────────────────────────────────────────────

/**
 * Get the client's IP address.
 */
function getClientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Generate the next voter ID (VTR-00001, VTR-00002, ...).
 */
function generateVoterId(): string
{
    $db = Database::getConnection();
    $stmt = $db->query('SELECT MAX(id) as max_id FROM voters');
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'VTR-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
}

/**
 * Get the status badge HTML for an election.
 */
function electionStatusBadge(string $status): string
{
    return match ($status) {
        'upcoming' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Upcoming</span>',
        'active'   => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>',
        'ended'    => '<span class="badge bg-secondary"><i class="fas fa-stop-circle me-1"></i>Ended</span>',
        default    => '<span class="badge bg-dark">Unknown</span>',
    };
}

/**
 * Truncate a string to a max length.
 */
function truncate(string $str, int $maxLen = 100, string $suffix = '...'): string
{
    if (mb_strlen($str) <= $maxLen) {
        return $str;
    }
    return mb_substr($str, 0, $maxLen) . $suffix;
}
