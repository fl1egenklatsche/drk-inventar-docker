<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $containerId = (int)($_GET['container_id'] ?? 0);
    
    if (!$containerId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'container_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    $compartments = $db->fetchAll(
        "SELECT id, container_id, name 
         FROM compartments 
         WHERE container_id = ? 
         ORDER BY sort_order, name",
        [$containerId]
    );
    
    echo json_encode([
        'success' => true,
        'compartments' => $compartments,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
