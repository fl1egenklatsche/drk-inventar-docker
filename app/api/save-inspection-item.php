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
    $compartmentId = (int)($input['compartment_id'] ?? 0);
    $productId = (int)($input['product_id'] ?? 0);
    $quantityActual = (int)($input['quantity_actual'] ?? 0);
    $expiryDate = $input['expiry_date'] ?? null;
    $notes = $input['notes'] ?? null;
    $photoUrl = $input['photo_url'] ?? null;
    
    if (!$inspectionId || !$compartmentId || !$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'inspection_id, compartment_id und product_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // SOLL-Menge laden
    $target = $db->fetchOne(
        "SELECT quantity FROM compartment_products_target 
         WHERE compartment_id = ? AND product_id = ?",
        [$compartmentId, $productId]
    );
    
    $quantityTarget = $target ? $target['quantity'] : 0;
    
    // Prüfen ob bereits ein Eintrag existiert
    $existing = $db->fetchOne(
        "SELECT id FROM inspection_items 
         WHERE inspection_id = ? AND compartment_id = ? AND product_id = ?",
        [$inspectionId, $compartmentId, $productId]
    );
    
    if ($existing) {
        // Update
        $db->query(
            "UPDATE inspection_items 
             SET quantity_target = ?, quantity_actual = ?, expiry_date = ?, notes = ?, photo_url = ?, updated_at = NOW() 
             WHERE id = ?",
            [$quantityTarget, $quantityActual, $expiryDate, $notes, $photoUrl, $existing['id']]
        );
    } else {
        // Insert
        $db->query(
            "INSERT INTO inspection_items 
             (inspection_id, compartment_id, product_id, quantity_target, quantity_actual, expiry_date, notes, photo_url) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$inspectionId, $compartmentId, $productId, $quantityTarget, $quantityActual, $expiryDate, $notes, $photoUrl]
        );
    }
    
    echo json_encode([
        'success' => true,
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler: ' . $e->getMessage()]);
}
