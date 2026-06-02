<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'status' => true,
    'service' => 'myportfolio-api',
    'message' => 'API service is running',
    'endpoints' => [
        '/api/auth.php',
        '/api/preferences.php',
        '/api/projects.php',
        '/api/contact.php',
        '/api/newsletter.php',
        '/api/db_debug.php'
    ]
]);
