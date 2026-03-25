<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $vehicleId = (int)($_GET['vehicle_id'] ?? 0);
    
    if (!$vehicleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'vehicle_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    $containers = $db->fetchAll(
        "SELECT id, vehicle_id, name, type 
         FROM containers 
         WHERE vehicle_id = ? AND active = 1 
         ORDER BY sort_order, name",
        [$vehicleId]
    );
    
    echo json_encode([
        'success' => true,
        'containers' => $containers,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
