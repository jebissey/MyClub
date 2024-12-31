<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'NavManager.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid ID parameter']);
    exit;
}

try {
    $id = (int)$_GET['id'];
    
    // Query the database
    $item = (new Page())->getById($id);
    if (!$item) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Item not found']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode($item);

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error']);
    
    // Log the actual error (but don't show it to users in production)
    error_log($e->getMessage());
    exit;
}

?>