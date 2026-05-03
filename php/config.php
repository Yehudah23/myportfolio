<?php
// Database Configuration - support environment variables for Docker/Render deployment
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'my-portfolio');

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

// Create database connection function
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}
