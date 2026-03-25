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
    // GET - Items oder Compartments laden
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Permission prüfen
        if (!canInspectContainer($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $itemId = (int)($_GET['id'] ?? 0);
        
        // GET ?container_inspection_id=X - Liste aller Container
        if (isset($_GET['container_inspection_id'])) {
            $inspectionId = (int)$_GET['container_inspection_id'];
            
            $items = $db->fetchAll(
                "SELECT cii.*, c.name as container_name, c.type as container_type,
                        u.full_name as inspected_by_name
                 FROM container_inspection_items cii
                 JOIN containers c ON cii.container_id = c.id
                 LEFT JOIN users u ON cii.inspected_by = u.id
                 WHERE cii.container_inspection_id = ?
                 ORDER BY c.sort_order, c.name",
                [$inspectionId]
            );
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
            exit;
        }
        
        // GET ?id=X&compartments=1 - Compartments für Container laden
        if ($itemId && isset($_GET['compartments'])) {
            $item = $db->fetchOne(
                "SELECT container_id FROM container_inspection_items WHERE id = ?",
                [$itemId]
            );
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item nicht gefunden']);
                exit;
            }
            
            // Compartments mit Target-Produkten holen
            $compartments = $db->fetchAll(
                "SELECT 
                    cm.id, cm.name, cm.description,
                    cpt.id as target_id, cpt.product_id, 
                    cpt.target_quantity, cpt.position,
                    p.name as product_name, p.unit, p.critical_minimum
                 FROM compartments cm
                 LEFT JOIN compartment_products_target cpt ON cm.id = cpt.compartment_id
                 LEFT JOIN products p ON cpt.product_id = p.id
                 WHERE cm.container_id = ? AND cm.active = 1
                 ORDER BY cm.sort_order, cm.name, cpt.position",
                [$item['container_id']]
            );
            
            // Gruppiere Produkte nach Compartment
            $result = [];
            foreach ($compartments as $comp) {
                $compartmentId = $comp['id'];
                
                if (!isset($result[$compartmentId])) {
                    $result[$compartmentId] = [
                        'id' => $comp['id'],
                        'name' => $comp['name'],
                        'description' => $comp['description'],
                        'products' => []
                    ];
                }
                
                if ($comp['product_id']) {
                    $result[$compartmentId]['products'][] = [
                        'target_id' => $comp['target_id'],
                        'product_id' => $comp['product_id'],
                        'product_name' => $comp['product_name'],
                        'target_quantity' => $comp['target_quantity'],
                        'unit' => $comp['unit'],
                        'critical_minimum' => $comp['critical_minimum'],
                        'position' => $comp['position']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'compartments' => array_values($result)
            ]);
            exit;
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter fehlen']);
        exit;
    }
    
    // POST - Item starten oder abschließen
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Permission prüfen
        if (!canInspectContainer($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $itemId = (int)($_GET['id'] ?? 0);
        $action = $_GET['action'] ?? '';
        
        if (!$itemId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'id erforderlich']);
            exit;
        }
        
        // POST ?id=X&action=start - Container-Prüfung starten
        if ($action === 'start') {
            $item = $db->fetchOne(
                "SELECT status FROM container_inspection_items WHERE id = ?",
                [$itemId]
            );
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item nicht gefunden']);
                exit;
            }
            
            if ($item['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Item ist nicht pending']);
                exit;
            }
            
            $db->query(
                "UPDATE container_inspection_items 
                 SET inspected_by = ?, started_at = NOW(), status = 'in_progress'
                 WHERE id = ?",
                [$user['id'], $itemId]
            );
            
            echo json_encode(['success' => true, 'message' => 'Prüfung gestartet']);
            exit;
        }
        
        // POST ?id=X&action=complete - Container-Prüfung abschließen
        if ($action === 'complete') {
            $input = json_decode(file_get_contents('php://input'), true);
            $inspectorName = trim($input['inspector_name'] ?? '');
            $inspectionData = $input['inspection_data'] ?? [];
            
            if (!$inspectorName) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'inspector_name erforderlich']);
                exit;
            }
            
            $item = $db->fetchOne(
                "SELECT status, container_inspection_id FROM container_inspection_items WHERE id = ?",
                [$itemId]
            );
            
            if (!$item) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item nicht gefunden']);
                exit;
            }
            
            if ($item['status'] !== 'in_progress') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Item ist nicht in_progress']);
                exit;
            }
            
            // Inspection Data in container_inspection_details speichern
            foreach ($inspectionData as $productKey => $data) {
                // Parse productKey: "compartmentId_productId"
                $parts = explode('_', $productKey);
                if (count($parts) !== 2) continue;
                
                $compartmentId = (int)$parts[0];
                $productId = (int)$parts[1];
                
                // Vorhandenen Eintrag löschen falls vorhanden
                $db->query(
                    "DELETE FROM container_inspection_details 
                     WHERE container_inspection_item_id = ? AND compartment_id = ? AND product_id = ?",
                    [$itemId, $compartmentId, $productId]
                );
                
                // Neuen Eintrag anlegen
                $db->query(
                    "INSERT INTO container_inspection_details 
                     (container_inspection_item_id, compartment_id, product_id, actual_quantity, expiry_date_after, notes, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $itemId,
                        $compartmentId,
                        $productId,
                        isset($data['missing']) && $data['missing'] ? 0 : ($data['quantity'] ?? 0),
                        $data['expiry'] ?? null,
                        $data['notes'] ?? null
                    ]
                );
            }
            
            // Container-Item auf completed setzen
            $db->query(
                "UPDATE container_inspection_items 
                 SET inspector_name = ?, completed_at = NOW(), status = 'completed'
                 WHERE id = ?",
                [$inspectorName, $itemId]
            );
            
            echo json_encode(['success' => true, 'message' => 'Prüfung abgeschlossen']);
            exit;
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige action']);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    
} catch (Exception $e) {
    error_log("API Error (container_inspection_items): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
