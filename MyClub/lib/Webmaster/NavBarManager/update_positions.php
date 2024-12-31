<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'NavManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['positions']) || !is_array($_POST['positions'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid positions data']);
    exit;
}

try {
    $navManager = new NavManager();
    $success = $navManager->updatePositions($_POST['positions']);
    
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to update positions']);
    }

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
    
    error_log($e->getMessage());
}