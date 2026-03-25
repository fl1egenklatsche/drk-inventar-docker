<?php
/**
 * get-inspection-flow.php
 * Lädt den kompletten Inspektions-Flow: Alle Container → Compartments → Products
 * für ein Fahrzeug. Wird einmalig beim Start der Inspektion geladen.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $inspectionId = (int)($_GET['inspection_id'] ?? 0);
    
    if (!$inspectionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'inspection_id erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Inspektion + Fahrzeug laden
    $inspection = $db->fetchOne(
        "SELECT i.*, v.name as vehicle_name, v.type as vehicle_type
         FROM inspections i
         JOIN vehicles v ON i.vehicle_id = v.id
         WHERE i.id = ?",
        [$inspectionId]
    );
    
    if (!$inspection) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Inspektion nicht gefunden']);
        exit;
    }
    
    $vehicleId = $inspection['vehicle_id'];
    
    // Alle Container für dieses Fahrzeug
    $containers = $db->fetchAll(
        "SELECT id, name, type FROM containers WHERE vehicle_id = ? AND active = 1 ORDER BY sort_order, name",
        [$vehicleId]
    );
    
    // Für jeden Container: Compartments + Products laden
    $flow = [];
    $totalCompartments = 0;
    
    foreach ($containers as $container) {
        $compartments = $db->fetchAll(
            "SELECT id, name FROM compartments WHERE container_id = ? AND active = 1 ORDER BY sort_order, name",
            [$container['id']]
        );
        
        foreach ($compartments as $compartment) {
            // SOLL-Produkte für dieses Fach
            $products = $db->fetchAll(
                "SELECT p.id, p.name, p.has_expiry, cpt.quantity as quantity_target
                 FROM compartment_products_target cpt
                 JOIN products p ON cpt.product_id = p.id
                 WHERE cpt.compartment_id = ?
                 ORDER BY p.name",
                [$compartment['id']]
            );
            
            // Existierende inspection_items + instances laden (für Wiederaufnahme)
            foreach ($products as &$product) {
                $item = $db->fetchOne(
                    "SELECT id FROM inspection_items 
                     WHERE inspection_id = ? AND compartment_id = ? AND product_id = ?",
                    [$inspectionId, $compartment['id'], $product['id']]
                );
                
                $product['inspection_item_id'] = $item ? $item['id'] : null;
                $product['instances'] = [];
                
                if ($item) {
                    $product['instances'] = $db->fetchAll(
                        "SELECT instance_number, expiry_date, missing, checked_at
                         FROM product_instances
                         WHERE inspection_item_id = ?
                         ORDER BY instance_number",
                        [$item['id']]
                    );
                }
            }
            unset($product);
            
            if (count($products) > 0) {
                $totalCompartments++;
                $flow[] = [
                    'container_id' => $container['id'],
                    'container_name' => $container['name'],
                    'compartment_id' => $compartment['id'],
                    'compartment_name' => $compartment['name'],
                    'products' => $products,
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'inspection' => [
            'id' => $inspection['id'],
            'vehicle_name' => $inspection['vehicle_name'],
            'vehicle_type' => $inspection['vehicle_type'],
            'status' => $inspection['status'],
            'started_at' => $inspection['started_at'],
        ],
        'total_compartments' => $totalCompartments,
        'flow' => $flow,
    ]);
    
} catch (Exception $e) {
    error_log("get-inspection-flow error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
