<?php
/**
 * save-product-instances.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/drk-api-errors.log');
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
    $instances = $input['instances'] ?? [];
    
    if (!$inspectionId || !$compartmentId || !$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'inspection_id, compartment_id und product_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // SOLL-Menge laden
    $target = $db->fetchOne(
        "SELECT quantity FROM compartment_products_target WHERE compartment_id = ? AND product_id = ?",
        [$compartmentId, $productId]
    );
    $quantityTarget = $target ? $target['quantity'] : 0;
    
    // inspection_item erstellen/laden
    $item = $db->fetchOne(
        "SELECT id FROM inspection_items WHERE inspection_id = ? AND compartment_id = ? AND product_id = ?",
        [$inspectionId, $compartmentId, $productId]
    );
    
    if (!$item) {
        // IST-Menge berechnen (nicht-fehlende Instanzen zählen)
        $quantityActual = 0;
        foreach ($instances as $inst) {
            if (empty($inst['missing'])) {
                $quantityActual++;
            }
        }
        
        $db->query(
            "INSERT INTO inspection_items (inspection_id, compartment_id, product_id, expected_quantity, actual_quantity)
             VALUES (?, ?, ?, ?, ?)",
            [$inspectionId, $compartmentId, $productId, $quantityTarget, $quantityActual]
        );
        $itemId = $db->lastInsertId();
    } else {
        $itemId = $item['id'];
        
        // IST-Menge aktualisieren
        $quantityActual = 0;
        foreach ($instances as $inst) {
            if (empty($inst['missing'])) {
                $quantityActual++;
            }
        }
        $db->query(
            "UPDATE inspection_items SET actual_quantity = ? WHERE id = ?",
            [$quantityActual, $itemId]
        );
    }
    
    // Instanzen speichern (Upsert)
    foreach ($instances as $inst) {
        $instanceNumber = (int)($inst['instance_number'] ?? 0);
        $expiryDate = !empty($inst['expiry_date']) ? $inst['expiry_date'] : null;
        $missing = !empty($inst['missing']) ? 1 : 0;
        
        if ($instanceNumber < 1) continue;
        
        $db->query(
            "INSERT INTO product_instances (inspection_item_id, instance_number, expiry_date, missing, checked_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE 
             expiry_date = VALUES(expiry_date),
             missing = VALUES(missing),
             checked_at = NOW()",
            [$itemId, $instanceNumber, $expiryDate, $missing]
        );
    }
    
    echo json_encode([
        'success' => true,
        'inspection_item_id' => $itemId,
        'quantity_actual' => $quantityActual,
    ]);
    
} catch (Exception $e) {
    error_log("save-product-instances error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler: ' . $e->getMessage()]);
}
