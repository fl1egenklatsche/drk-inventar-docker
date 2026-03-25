<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $compartmentId = (int)($_GET['compartment_id'] ?? 0);
    
    if (!$compartmentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'compartment_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // SOLL-Bestückung für dieses Fach laden
    $products = $db->fetchAll(
        "SELECT 
            p.id,
            p.name,
            p.description,
            cpt.quantity as quantity_target
         FROM compartment_products_target cpt
         JOIN products p ON cpt.product_id = p.id
         WHERE cpt.compartment_id = ?
         ORDER BY p.name",
        [$compartmentId]
    );
    
    echo json_encode([
        'success' => true,
        'products' => $products,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
