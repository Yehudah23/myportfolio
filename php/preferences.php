<?php
/**
 * User Preferences API
 * Handles storing and retrieving user preferences (dark mode, etc.)
 */

require_once 'config.php';
require_once 'utils.php';

// Set headers
setCorsHeaders();
header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$db = getDBConnection();
if (!$db) {
    sendJsonResponse(['error' => 'Database connection failed'], 500);
}

// Get user ID from cookie or create new one
$userId = $_COOKIE['user_id'] ?? null;
if (!$userId) {
    $userId = generateUserId();
    setcookie('user_id', $userId, time() + (365 * 24 * 60 * 60), '/', '', false, true); // 1 year, httpOnly
}

// Route the request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $userId);
            break;
        case 'POST':
        case 'PUT':
            handleUpdate($db, $userId);
            break;
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Preferences API Error: " . $e->getMessage());
    sendJsonResponse(['error' => 'An error occurred'], 500);
}

/**
 * Get user preferences
 */
function handleGet($db, $userId) {
    $stmt = $db->prepare("SELECT dark_mode FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    if ($result) {
        sendJsonResponse([
            'success' => true,
            'data' => [
                'darkMode' => (bool)$result['dark_mode']
            ]
        ]);
    } else {
        // Return default preferences
        sendJsonResponse([
            'success' => true,
            'data' => [
                'darkMode' => false
            ]
        ]);
    }
}

/**
 * Update user preferences
 */
function handleUpdate($db, $userId) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['darkMode'])) {
        sendJsonResponse(['error' => 'Invalid input'], 400);
    }
    
    $darkMode = (bool)$input['darkMode'];
    
    // Insert or update preferences
    $stmt = $db->prepare("
        INSERT INTO user_preferences (user_id, dark_mode) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE 
            dark_mode = VALUES(dark_mode),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$userId, $darkMode]);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Preferences updated successfully',
        'data' => [
            'darkMode' => $darkMode
        ]
    ]);
}

/**
 * Generate a unique user ID
 */
function generateUserId() {
    return bin2hex(random_bytes(16)) . '_' . time();
}
