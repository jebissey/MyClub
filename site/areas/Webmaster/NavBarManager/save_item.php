<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'NavManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = [
    'id' => isset($_POST['id']) ? trim($_POST['id']) : '',
    'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
    'file' => isset($_POST['file']) ? trim($_POST['file']) : '',
    'content' => isset($_POST['content']) ? trim($_POST['content']) : ''
];

// Validation basique
if (empty($data['name']) || empty($data['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Name and File are required']);
    exit;
}

try {
    $navManager = new NavManager();
    if (!empty($data['id'])) {
        $success = $navManager->updateNavItem($data['id'], $data['name'], $data['file'], $data['content']);
    } else {
        $success = $navManager->addNavItem($data['name'], $data['file']);
    }

    header('Content-Type: application/json');
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => empty($data['id']) ? 'Item created' : 'Item updated'
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to save item']);
    }

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
    error_log($e->getMessage());
}