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

function isMultipartRequest() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    return stripos($contentType, 'multipart/form-data') !== false;
}

function normalizeTechnologiesValue($value) {
    if (is_array($value)) {
        return json_encode($value);
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return json_encode([]);
        }

        if ($trimmed[0] === '[') {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode(array_values($decoded));
            }
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $trimmed))));
        return json_encode($parts);
    }

    return json_encode([]);
}

function ensureProjectUploadDir() {
    $dir = __DIR__ . '/../uploads/projects';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function saveProjectImageFromFileArray(array $file) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $originalName = $file['name'] ?? 'project-image';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $extension = 'png';
    }

    $fileName = uniqid('project_', true) . '.' . $extension;
    $targetDir = ensureProjectUploadDir();
    $targetPath = $targetDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/projects/' . $fileName;
}

function parseMultipartBody($rawBody, $contentType) {
    $result = ['fields' => [], 'files' => []];

    if (!preg_match('/boundary=(.*)$/', $contentType, $matches)) {
        return $result;
    }

    $boundary = trim($matches[1], '"');
    $parts = preg_split('/-+' . preg_quote($boundary, '/') . '/', $rawBody);

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '' || $part === '--') {
            continue;
        }

        $segments = preg_split("/\r\n\r\n/", $part, 2);
        if (count($segments) !== 2) {
            continue;
        }

        [$rawHeaders, $body] = $segments;
        $body = preg_replace("/\r\n$/", '', $body);

        $headers = explode("\r\n", $rawHeaders);
        $fieldName = '';
        $fileName = '';
        $mimeType = 'application/octet-stream';

        foreach ($headers as $header) {
            if (stripos($header, 'Content-Disposition:') === 0) {
                if (preg_match('/name="([^"]+)"/', $header, $nameMatch)) {
                    $fieldName = $nameMatch[1];
                }
                if (preg_match('/filename="([^"]*)"/', $header, $fileMatch)) {
                    $fileName = $fileMatch[1];
                }
            }

            if (stripos($header, 'Content-Type:') === 0) {
                $mimeType = trim(substr($header, strpos($header, ':') + 1));
            }
        }

        if ($fieldName === '') {
            continue;
        }

        if ($fileName !== '') {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (!in_array($extension, $allowedExtensions, true)) {
                $extension = 'png';
            }

            $storedName = uniqid('project_', true) . '.' . $extension;
            $targetDir = ensureProjectUploadDir();
            $targetPath = $targetDir . '/' . $storedName;

            if (file_put_contents($targetPath, $body) === false) {
                return $result;
            }

            $result['files'][$fieldName] = [
                'name' => $fileName,
                'type' => $mimeType,
                'path' => $targetPath,
                'storedName' => $storedName,
                'publicPath' => 'uploads/projects/' . $storedName
            ];
        } else {
            $result['fields'][$fieldName] = $body;
        }
    }

    return $result;
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

    $requestData = [];
    if (isMultipartRequest()) {
        $requestData = $_POST;
        if (isset($_FILES['image'])) {
            $uploadedImage = saveProjectImageFromFileArray($_FILES['image']);
            if ($uploadedImage) {
                $requestData['image'] = $uploadedImage;
            }
        }
    } else {
        $rawInput = file_get_contents('php://input');
        $requestData = json_decode($rawInput, true);

        if (!is_array($requestData)) {
            $connection->close();
            sendJsonResponse([
                'status' => false,
                'message' => 'Invalid JSON payload',
                'debug' => json_last_error_msg()
            ], 400);
        }
    }

    $title = $connection->real_escape_string($requestData['title'] ?? '');
    $description = $connection->real_escape_string($requestData['description'] ?? '');
    $image = $connection->real_escape_string($requestData['image'] ?? '');
    $technologies = normalizeTechnologiesValue($requestData['technologies'] ?? []);
    $category = $connection->real_escape_string($requestData['category'] ?? '');
    $featured = isset($requestData['featured']) ? (int)$requestData['featured'] : 0;
    $githubUrl = $connection->real_escape_string($requestData['githubUrl'] ?? '');
    $liveUrl = $connection->real_escape_string($requestData['liveUrl'] ?? '');
    
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
    
    $requestData = [];
    if (isMultipartRequest()) {
        $requestData = parseMultipartBody($rawInput, $_SERVER['CONTENT_TYPE'] ?? '');
        $requestData = array_merge($requestData['fields'], $requestData['files']);

        if (isset($requestData['image']['publicPath'])) {
            $requestData['image'] = $requestData['image']['publicPath'];
        }
    } else {
        $requestData = $input;
    }

    $id = (int)($requestData['id'] ?? 0);
    $title = $connection->real_escape_string($requestData['title'] ?? '');
    $description = $connection->real_escape_string($requestData['description'] ?? '');
    $image = $connection->real_escape_string($requestData['image'] ?? '');
    $technologies = normalizeTechnologiesValue($requestData['technologies'] ?? []);
    $category = $connection->real_escape_string($requestData['category'] ?? '');
    $featured = isset($requestData['featured']) ? (int)$requestData['featured'] : 0;
    $githubUrl = $connection->real_escape_string($requestData['githubUrl'] ?? '');
    $liveUrl = $connection->real_escape_string($requestData['liveUrl'] ?? '');
    
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
                'sentImage' => $requestData['image'] ?? '',
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
        sendJsonResponse(['status' => false, 'message' => 'Unauthorized'], 401);
        $connection->close();
        exit;
    }

    // Log request details for debugging
    $rawInput = file_get_contents('php://input');
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    error_log("=== DELETE REQUEST ===");
    error_log("Method: " . ($_SERVER['REQUEST_METHOD'] ?? ''));
    error_log("Content-Type: $contentType");
    error_log("Query string: " . ($_SERVER['QUERY_STRING'] ?? ''));
    error_log("Raw input: " . $rawInput);

    // Only attempt to decode JSON if there's a non-empty body
    $input = [];
    if (is_string($rawInput) && trim($rawInput) !== '') {
        $decoded = json_decode($rawInput, true);
        if (!is_array($decoded)) {
            error_log("DELETE FAILED - Invalid JSON payload: " . json_last_error_msg());
            $connection->close();
            sendJsonResponse([
                'status' => false,
                'message' => 'Invalid JSON payload',
                'debug' => json_last_error_msg()
            ], 400);
        }
        $input = $decoded;
    }

    // Prefer id in query string, fallback to JSON body
    $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));

    if ($id <= 0) {
        error_log("DELETE FAILED - Missing or invalid project id. Raw input: " . $rawInput);
        $connection->close();
        sendJsonResponse(['status' => false, 'message' => 'Project id is required'], 400);
    }

    $query = "DELETE FROM `projects` WHERE id=$id";
    $result = $connection->query($query);
    
    if($result){
        sendJsonResponse(['status' => true, 'message' => 'Project deleted successfully']);
    }else{
        sendJsonResponse(['status' => false, 'message' => 'Error deleting project: ' . $connection->error], 500);
    }
    $connection->close();
    exit;
}

$connection->close();
?>
