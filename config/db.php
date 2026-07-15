<?php
/**
 * Database Configuration & Connection
 * Online Voting System
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// ─── Database Credentials ───────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');           // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

// ─── Application Constants ──────────────────────────────────────────────────
define('APP_NAME', 'VoteSecure');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/voting System');

// Upload paths (relative to project root)
define('UPLOAD_PATH', BASE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('CANDIDATE_PHOTOS', UPLOAD_PATH . 'candidates' . DIRECTORY_SEPARATOR);
define('VOTER_PHOTOS', UPLOAD_PATH . 'voters' . DIRECTORY_SEPARATOR);

// Max upload size (2MB)
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ─── PDO Connection ─────────────────────────────────────────────────────────
class Database
{
    private static ?PDO $instance = null;

    /**
     * Get the singleton PDO connection.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In production, log the error instead of displaying it
                error_log('Database Connection Error: ' . $e->getMessage());
                die(
                    '<div style="font-family:sans-serif;padding:40px;text-align:center;">'
                    . '<h1 style="color:#e74c3c;">Database Connection Failed</h1>'
                    . '<p>Please make sure XAMPP MySQL is running and the database <code>' . DB_NAME . '</code> exists.</p>'
                    . '<p style="color:#999;font-size:13px;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>'
                    . '</div>'
                );
            }
        }

        return self::$instance;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}
}
