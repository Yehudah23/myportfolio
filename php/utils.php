<?php
/**
 * Utility functions for PHP APIs
 */

/**
 * Send JSON response and exit
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Set CORS headers
 */
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Check rate limit
 */
function checkRateLimit($db, $endpoint) {
    if (!RATE_LIMIT_ENABLED) {
        return true;
    }
    
    $ip = getClientIP();
    $now = time();
    $windowStart = date('Y-m-d H:i:s', $now - RATE_LIMIT_PERIOD);
    
    // Clean up old entries
    $stmt = $db->prepare("DELETE FROM rate_limits WHERE window_start < ?");
    $stmt->execute([$windowStart]);
    
    // Check current rate
    $stmt = $db->prepare("
        SELECT request_count 
        FROM rate_limits 
        WHERE ip_address = ? AND endpoint = ? AND window_start >= ?
    ");
    $stmt->execute([$ip, $endpoint, $windowStart]);
    $result = $stmt->fetch();
    
    if ($result && $result['request_count'] >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Increment or insert
    $stmt = $db->prepare("
        INSERT INTO rate_limits (ip_address, endpoint, request_count, window_start) 
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
            request_count = request_count + 1
    ");
    $stmt->execute([$ip, $endpoint]);
    
    return true;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Log to file
 */
function logToFile($filename, $message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
