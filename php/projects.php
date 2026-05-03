<?php
require('config.php');
require_once 'utils.php';

// Set CORS headers for this endpoint
setCorsHeaders();
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create mysqli connection with LAMPP socket
$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, null, '/opt/lampp/var/mysql/mysql.sock');

if ($connection->connect_error) {
    die(json_encode(['status' => false, 'message' => 'Database connection failed: ' . $connection->connect_error]));
}

$connection->set_charset('utf8mb4');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if($method == 'GET' && empty($action)){
    $query = "SELECT * FROM `projects` ORDER BY created_at DESC";
    $result = $connection->query($query);
    
    $projects = [];
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $tech = $row['technologies'];
            // Handle both JSON and comma-separated string formats
            if ($tech && $tech[0] == '[') {
                $technologies = json_decode($tech);
            } else {
                $technologies = $tech ? explode(',', $tech) : [];
            }
            
            $projects[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'image' => $row['image'],
                'category' => $row['category'],
                'technologies' => $technologies,
                'featured' => (bool)$row['featured'],
                'githubUrl' => $row['github_url'],
                'liveUrl' => $row['live_url'],
                'created_at' => $row['created_at']
            ];
        }
    }
    echo json_encode(['status' => true, 'data' => $projects]);
    $connection->close();
    exit;
}

if($method == 'GET' && $action == 'single'){
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM `projects` WHERE id=$id";
    $result = $connection->query($query);
    
    if($result->num_rows > 0){
        $project = $result->fetch_assoc();
        $tech = $project['technologies'];
        if ($tech && $tech[0] == '[') {
            $project['technologies'] = json_decode($tech);
        } else {
            $project['technologies'] = $tech ? explode(',', $tech) : [];
        }
        $project['githubUrl'] = $project['github_url'];
        $project['liveUrl'] = $project['live_url'];
        $project['featured'] = (bool)$project['featured'];
        echo json_encode(['status' => true, 'data' => $project]);
    }else{
        echo json_encode(['status' => false, 'message' => 'Project not found']);
    }
    $connection->close();
    exit;
}

if($method == 'POST'){
    session_start();
    if(!(isset($_SESSION['loggedInUser']) && !empty($_SESSION['loggedInUser'])) && !(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)){
        error_log("UNAUTHORIZED - No session user or admin flag (POST)");
        sendJsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        $connection->close();
        exit;
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        $connection->close();
        sendJsonResponse([
            'status' => false,
            'message' => 'Invalid JSON payload',
            'debug' => json_last_error_msg()
        ], 400);
    }

    $title = $connection->real_escape_string($input['title'] ?? '');
    $description = $connection->real_escape_string($input['description'] ?? '');
    $image = $connection->real_escape_string($input['image'] ?? '');
    $technologies = json_encode($input['technologies'] ?? []);
    $category = $connection->real_escape_string($input['category'] ?? '');
    $featured = isset($input['featured']) ? (int)$input['featured'] : 0;
    $githubUrl = $connection->real_escape_string($input['githubUrl'] ?? '');
    $liveUrl = $connection->real_escape_string($input['liveUrl'] ?? '');
    
    if(empty($title) || empty($description)){
        sendJsonResponse(['status' => false, 'message' => 'Title and description are required'], 400);
    }else{
        $query = "INSERT INTO `projects`(`title`, `description`, `image`, `technologies`, `category`, `featured`, `github_url`, `live_url`, `created_at`) VALUES ('$title', '$description', '$image', '$technologies', '$category', $featured, '$githubUrl', '$liveUrl', NOW())";
        $result = $connection->query($query);
        
        if($result){
            sendJsonResponse(['status' => true, 'message' => 'Project created successfully']);
        }else{
            sendJsonResponse(['status' => false, 'message' => 'Error creating project: ' . $connection->error], 500);
        }
    }
    $connection->close();
    exit;
}

if($method == 'PUT'){
    // Start session first
    session_start();
    
    // Read the raw input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Log everything for debugging
    error_log("=== PUT REQUEST RECEIVED ===");
    error_log("Raw Input: " . $rawInput);
    error_log("Decoded Input: " . print_r($input, true));
    error_log("Session User: " . ($_SESSION['loggedInUser'] ?? ($_SESSION['username'] ?? 'NOT SET')));

    if (!is_array($input)) {
        $connection->close();
        sendJsonResponse([
            'status' => false,
            'message' => 'Invalid JSON payload',
            'debug' => json_last_error_msg()
        ], 400);
    }

    if(!(isset($_SESSION['loggedInUser']) && !empty($_SESSION['loggedInUser'])) && !(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)){
        error_log("UNAUTHORIZED - No session user or admin flag (PUT)");
        sendJsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        $connection->close();
        exit;
    }
    
    $id = (int)($input['id'] ?? 0);
    $title = $connection->real_escape_string($input['title'] ?? '');
    $description = $connection->real_escape_string($input['description'] ?? '');
    $image = isset($input['image']) ? $connection->real_escape_string($input['image']) : '';
    $technologies = json_encode($input['technologies'] ?? []);
    $category = $connection->real_escape_string($input['category'] ?? '');
    $featured = isset($input['featured']) ? (int)$input['featured'] : 0;
    $githubUrl = $connection->real_escape_string($input['githubUrl'] ?? '');
    $liveUrl = $connection->real_escape_string($input['liveUrl'] ?? '');
    
    error_log("Processed - ID: $id, Image: $image (length: " . strlen($image) . ")");
    
    // Simplified query without updated_at to avoid any column issues
    $query = "UPDATE `projects` SET 
        `title`='$title', 
        `description`='$description', 
        `image`='$image', 
        `technologies`='$technologies', 
        `category`='$category', 
        `featured`=$featured, 
        `github_url`='$githubUrl', 
        `live_url`='$liveUrl'
        WHERE id=$id";
    
    error_log("Query: " . $query);
    
    $result = $connection->query($query);
    
    if($result){
        // Verify the update
        $checkQuery = "SELECT image, title FROM projects WHERE id=$id";
        $checkResult = $connection->query($checkQuery);
        $row = $checkResult->fetch_assoc();
        
        error_log("After UPDATE - DB Image: " . $row['image']);
        error_log("Affected rows: " . $connection->affected_rows);
        
        echo json_encode([
            'status' => true, 
            'message' => 'Project updated successfully',
            'debug' => [
                'updatedImage' => $row['image'],
                'sentImage' => $input['image'] ?? '',
                'affectedRows' => $connection->affected_rows,
                'id' => $id
            ]
        ]);
    }else{
        error_log("UPDATE FAILED: " . $connection->error);
        echo json_encode(['status' => false, 'message' => 'Error updating project: ' . $connection->error]);
    }
    
    error_log("=== END PUT REQUEST ===");
    $connection->close();
    exit;
}

if($method == 'DELETE'){
    session_start();
    if(!(isset($_SESSION['loggedInUser']) && !empty($_SESSION['loggedInUser'])) && !(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)){
        error_log("UNAUTHORIZED - No session user or admin flag (DELETE)");
        echo json_encode(['status' => false, 'message' => 'Unauthorized']);
        $connection->close();
        exit;
    }
    
    $id = (int)$_GET['id'];
    $query = "DELETE FROM `projects` WHERE id=$id";
    $result = $connection->query($query);
    
    if($result){
        echo json_encode(['status' => true, 'message' => 'Project deleted successfully']);
    }else{
        echo json_encode(['status' => false, 'message' => 'Error deleting project: ' . $connection->error]);
    }
    $connection->close();
    exit;
}

$connection->close();
?>
