<?php
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

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendJsonResponse(['error' => 'Username and password are required'], 400);
    }
    
    // Simple authentication - username: admin, password: yehudah23
    if ($username === 'admin' && $password === 'yehudah23') {
        // Create session
        startSecureSession();
        $_SESSION['admin_logged_in'] = true;
        // Also set the legacy session key used by other endpoints
        $_SESSION['loggedInUser'] = $username;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'username' => $username,
                'role' => 'admin'
            ]
        ]);
    } else {
        sendJsonResponse(['error' => 'Invalid username or password'], 401);
    }
}

if ($method === 'POST' && $action === 'logout') {
    startSecureSession();
    session_destroy();
    sendJsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

if ($method === 'GET' && $action === 'check') {
    startSecureSession();
    
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        sendJsonResponse([
            'authenticated' => true,
            'user' => [
                'username' => $_SESSION['username'] ?? 'admin',
                'role' => 'admin'
            ]
        ]);
    } else {
        sendJsonResponse(['authenticated' => false], 401);
    }
}

// Invalid action
sendJsonResponse(['error' => 'Invalid action'], 400);
?>
