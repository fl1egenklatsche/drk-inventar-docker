<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getInstance();
    
    $vehicles = $db->fetchAll(
        "SELECT id, name, type, active 
         FROM vehicles 
         WHERE active = 1 
         ORDER BY name"
    );
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
