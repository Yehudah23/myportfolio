<?php
// Database Configuration - support environment variables for Docker/Render deployment
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'my-portfolio');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

// Email Configuration
define('CONTACT_EMAIL', 'your-email@example.com');
define('FROM_EMAIL', 'noreply@yourdomain.com');

// Security Configuration
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 5);
define('RATE_LIMIT_PERIOD', 3600); // 1 hour in seconds

// CORS Configuration
$allowedOrigins = getenv('ALLOWED_ORIGINS') ?: 'http://localhost:4200,http://localhost:8000';
define('ALLOWED_ORIGINS', array_map('trim', explode(',', $allowedOrigins)));

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Ensure session cookies are set for cross-site requests from frontends (SameSite=None requires Secure)
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
// For PHP >= 7.3 you can set samesite via session.cookie_samesite
if (PHP_VERSION_ID >= 70300) {
    ini_set('session.cookie_samesite', 'None');
}

function startSecureSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (!headers_sent() && PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
    }

    session_start();
}

// Create database connection function
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        // Log a masked, useful debug line for deployed environments without exposing secrets
        $masked = sprintf("DB connect failed: host=%s port=%s db=%s user=%s pass=%s", DB_HOST, DB_PORT, DB_NAME, DB_USER, '***');
        error_log($masked . "; error=" . $e->getMessage());
        return null;
    }
}
