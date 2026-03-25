<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Auth prüfen
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

$user = getCurrentUser();
$db = Database::getInstance();

try {
    // GET - Produkt-Daten laden
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Permission prüfen
        if (!canInspectContainer($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $itemId = (int)($_GET['container_inspection_item_id'] ?? 0);
        
        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'container_inspection_item_id erforderlich']);
            exit;
        }
        
        $details = $db->fetchAll(
            "SELECT 
                cid.*,
                cm.name as compartment_name,
                p.name as product_name,
                p.unit
             FROM container_inspection_details cid
             JOIN compartments cm ON cid.compartment_id = cm.id
             JOIN products p ON cid.product_id = p.id
             WHERE cid.container_inspection_item_id = ?
             ORDER BY cm.sort_order, cid.id",
            [$itemId]
        );
        
        echo json_encode([
            'success' => true,
            'details' => $details
        ]);
        exit;
    }
    
    // POST - Produkt-Daten speichern
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Permission prüfen
        if (!canInspectContainer($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $itemId = (int)($_GET['container_inspection_item_id'] ?? 0);
        
        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'container_inspection_item_id erforderlich']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $products = $input['products'] ?? [];
        
        if (!is_array($products) || empty($products)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Produkt-Array erforderlich']);
            exit;
        }
        
        $savedCount = 0;
        
        foreach ($products as $product) {
            $compartmentId = (int)($product['compartment_id'] ?? 0);
            $productId = (int)($product['product_id'] ?? 0);
            $actualQuantity = (int)($product['actual_quantity'] ?? 0);
            $expiryDateAfter = $product['expiry_date_after'] ?? null;
            $statusAfter = $product['status_after'] ?? 'ok';
            $actionTaken = $product['action_taken'] ?? null;
            $notes = $product['notes'] ?? null;
            
            if (!$compartmentId || !$productId) {
                continue; // Überspringe ungültige Einträge
            }
            
            // Expected Quantity aus Target holen
            $target = $db->fetchOne(
                "SELECT target_quantity FROM compartment_products_target 
                 WHERE compartment_id = ? AND product_id = ?",
                [$compartmentId, $productId]
            );
            
            $expectedQuantity = $target ? (int)$target['target_quantity'] : 0;
            
            // Expiry Date Before + Status Before aus Actual holen
            $actual = $db->fetchOne(
                "SELECT expiry_date, status FROM compartment_products_actual 
                 WHERE compartment_id = ? AND product_id = ?",
                [$compartmentId, $productId]
            );
            
            $expiryDateBefore = $actual['expiry_date'] ?? null;
            $statusBefore = $actual['status'] ?? 'ok';
            
            // Detail speichern
            $db->query(
                "INSERT INTO container_inspection_details 
                 (container_inspection_item_id, compartment_id, product_id, 
                  expected_quantity, actual_quantity, 
                  expiry_date_before, expiry_date_after, 
                  status_before, status_after, 
                  action_taken, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $itemId, $compartmentId, $productId,
                    $expectedQuantity, $actualQuantity,
                    $expiryDateBefore, $expiryDateAfter,
                    $statusBefore, $statusAfter,
                    $actionTaken, $notes
                ]
            );
            
            // compartment_products_actual aktualisieren
            if ($actual) {
                // Update existing
                $db->query(
                    "UPDATE compartment_products_actual 
                     SET actual_quantity = ?, expiry_date = ?, status = ?, last_updated = NOW()
                     WHERE compartment_id = ? AND product_id = ?",
                    [$actualQuantity, $expiryDateAfter, $statusAfter, $compartmentId, $productId]
                );
            } else {
                // Insert new
                $db->query(
                    "INSERT INTO compartment_products_actual 
                     (compartment_id, product_id, actual_quantity, expiry_date, status, last_updated)
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [$compartmentId, $productId, $actualQuantity, $expiryDateAfter, $statusAfter]
                );
            }
            
            $savedCount++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "{$savedCount} Produkte gespeichert"
        ]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler: ' . $e->getMessage()]);
}
