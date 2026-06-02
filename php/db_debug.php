<?php
require_once 'config.php';
require_once 'utils.php';

// Simple, safe debug endpoint to inspect relevant env values (masks secrets).
setCorsHeaders();
header('Content-Type: application/json');

$masked = [
    'DB_HOST' => DB_HOST,
    'DB_PORT' => DB_PORT,
    'DB_NAME' => DB_NAME,
    'DB_USER' => DB_USER,
    'DB_PASS' => '***',
    'ALLOWED_ORIGINS' => ALLOWED_ORIGINS,
];

echo json_encode(['status' => true, 'env' => $masked]);
exit;

?>