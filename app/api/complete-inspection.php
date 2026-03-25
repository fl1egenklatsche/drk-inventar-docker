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
    
    $inspectionId = (int)($input['inspection_id'] ?? 0);
    
    if (!$inspectionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'inspection_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Inspektion als abgeschlossen markieren
    $db->query(
        "UPDATE inspections 
         SET status = 'completed', completed_at = NOW() 
         WHERE id = ?",
        [$inspectionId]
    );
    
    echo json_encode([
        'success' => true,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
