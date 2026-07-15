<?php
/**
 * Authentication & Security Helpers
 * Handles sessions, CSRF tokens, rate limiting, and access control.
 */

// ─── Security Headers ──────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// ─── Session Configuration ─────────────────────────────────────────────────
// Session inactivity timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,      // Set true in production with HTTPS
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);
    session_start();
}

// ─── Session Timeout Check ──────────────────────────────────────────────────
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        // Session has expired
        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your session has expired due to inactivity. Please log in again.';
    }
}
$_SESSION['last_activity'] = time();

// ─── CSRF Protection ────────────────────────────────────────────────────────

/**
 * Generate a CSRF token and store it in the session.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field with the CSRF token.
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate the submitted CSRF token against the session token.
 * Regenerates the token after successful validation to prevent token reuse.
 */
function validateCsrfToken(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    if ($valid) {
        // Regenerate token after use to prevent replay attacks
        unset($_SESSION['csrf_token']);
    }
    return $valid;
}

// ─── Login Rate Limiting ────────────────────────────────────────────────────

/**
 * Check if login attempts are within the allowed limit.
 * Returns true if the user can attempt login, false if rate-limited.
 * 
 * @param string $identifier  Username or IP to track
 * @param int    $maxAttempts  Max attempts allowed (default 5)
 * @param int    $windowSec    Time window in seconds (default 900 = 15 min)
 */
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSec = 900): bool
{
    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }

    $data = &$_SESSION[$key];

    // Reset window if it has expired
    if (time() - $data['first_attempt'] > $windowSec) {
        $data = ['attempts' => 0, 'first_attempt' => time()];
    }

    return $data['attempts'] < $maxAttempts;
}

/**
 * Record a failed login attempt.
 */
function recordFailedLogin(string $identifier): void
{
    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }

    $_SESSION[$key]['attempts']++;
}

/**
 * Clear rate limit tracking for a user (call after successful login).
 */
function clearRateLimit(string $identifier): void
{
    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

/**
 * Get remaining seconds until rate limit resets.
 */
function getRateLimitReset(string $identifier, int $windowSec = 900): int
{
    $key = 'rate_limit_' . md5($identifier);
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    return max(0, $windowSec - $elapsed);
}

// ─── Session Helpers ────────────────────────────────────────────────────────

/**
 * Check if the current user is logged in as admin.
 */
function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if the current user is logged in as voter.
 */
function isVoterLoggedIn(): bool
{
    return isset($_SESSION['voter_id']) && $_SESSION['user_type'] === 'voter';
}

/**
 * Require admin authentication — redirect to login if not authenticated.
 */
function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in as an administrator to access that page.';
        header('Location: ' . getBaseUrl() . '/admin/login.php');
        exit;
    }
}

/**
 * Require voter authentication — redirect to login if not authenticated.
 */
function requireVoter(): void
{
    if (!isVoterLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access that page.';
        header('Location: ' . getBaseUrl() . '/voter/login.php');
        exit;
    }
}

/**
 * Get the base URL of the application (auto-detect).
 */
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Determine the base path from the script
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Navigate up to the project root
    $parts = explode('/', trim($scriptDir, '/'));
    // Find 'voting System' in the path
    $baseParts = [];
    foreach ($parts as $part) {
        $baseParts[] = $part;
        if ($part === 'voting System') {
            break;
        }
    }
    $basePath = '/' . implode('/', $baseParts);
    return $protocol . '://' . $host . $basePath;
}

/**
 * Regenerate session ID (call after login to prevent session fixation).
 */
function regenerateSession(): void
{
    session_regenerate_id(true);
}

/**
 * Destroy the current session and redirect to a URL.
 */
function destroySession(string $redirectUrl): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    header('Location: ' . $redirectUrl);
    exit;
}
