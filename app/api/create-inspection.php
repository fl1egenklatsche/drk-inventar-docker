<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $vehicleId = (int)($input['vehicle_id'] ?? 0);
    $userId = (int)($input['user_id'] ?? 0);
    $inspectionDate = $input['inspection_date'] ?? date('Y-m-d');
    
    if (!$vehicleId || !$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'vehicle_id und user_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Neue Inspektion erstellen
    $db->query(
        "INSERT INTO inspections (vehicle_id, user_id, status) 
         VALUES (?, ?, 'in_progress')",
        [$vehicleId, $userId]
    );
    
    $inspectionId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'inspection_id' => $inspectionId,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
