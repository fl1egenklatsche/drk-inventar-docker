<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
    // POST - Session starten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Permission prüfen
        if (!canStartContainerInspection($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $vehicleId = (int)($input['vehicle_id'] ?? 0);
        $userId = (int)($input['user_id'] ?? $user['id']);
        
        if (!$vehicleId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'vehicle_id erforderlich']);
            exit;
        }
        
        // Prüfe ob bereits in_progress Session existiert
        $existingSession = $db->fetchOne(
            "SELECT id FROM container_inspections 
             WHERE vehicle_id = ? AND status = 'in_progress'",
            [$vehicleId]
        );
        
        if ($existingSession) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Es läuft bereits eine Prüfung für dieses Fahrzeug'
            ]);
            exit;
        }
        
        // Session erstellen
        $db->query(
            "INSERT INTO container_inspections (vehicle_id, user_id, status) 
             VALUES (?, ?, 'in_progress')",
            [$vehicleId, $userId]
        );
        
        $sessionId = $db->lastInsertId();
        
        // Alle Container des Fahrzeugs holen
        $containers = $db->fetchAll(
            "SELECT id FROM containers WHERE vehicle_id = ? AND active = 1",
            [$vehicleId]
        );
        
        // Inspection Items erstellen
        foreach ($containers as $container) {
            $db->query(
                "INSERT INTO container_inspection_items 
                 (container_inspection_id, container_id, status) 
                 VALUES (?, ?, 'pending')",
                [$sessionId, $container['id']]
            );
        }
        
        echo json_encode([
            'success' => true,
            'session_id' => $sessionId,
            'container_count' => count($containers)
        ]);
        exit;
    }
    
    // GET - Sessions abrufen
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Permission prüfen
        if (!canInspectContainer($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        // GET ?id=X - Session Details
        if (isset($_GET['id'])) {
            $sessionId = (int)$_GET['id'];
            
            $session = $db->fetchOne(
                "SELECT ci.*, v.name as vehicle_name, u.full_name as user_name
                 FROM container_inspections ci
                 JOIN vehicles v ON ci.vehicle_id = v.id
                 JOIN users u ON ci.started_by = u.id
                 WHERE ci.id = ?",
                [$sessionId]
            );
            
            if (!$session) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Session nicht gefunden']);
                exit;
            }
            
            // Items holen
            $items = $db->fetchAll(
                "SELECT cii.*, c.name as container_name, 
                        u.full_name as inspected_by_name
                 FROM container_inspection_items cii
                 JOIN containers c ON cii.container_id = c.id
                 LEFT JOIN users u ON cii.inspected_by = u.id
                 WHERE cii.container_inspection_id = ?
                 ORDER BY c.sort_order, c.name",
                [$sessionId]
            );
            
            echo json_encode([
                'success' => true,
                'session' => $session,
                'items' => $items
            ]);
            exit;
        }
        
        // GET ?vehicle_id=X - Offene Sessions für Fahrzeug
        if (isset($_GET['vehicle_id'])) {
            $vehicleId = (int)$_GET['vehicle_id'];
            
            $sessions = $db->fetchAll(
                "SELECT ci.*, v.name as vehicle_name, u.full_name as user_name
                 FROM container_inspections ci
                 JOIN vehicles v ON ci.vehicle_id = v.id
                 JOIN users u ON ci.started_by = u.id
                 WHERE ci.vehicle_id = ? AND ci.status = 'in_progress'
                 ORDER BY ci.created_at DESC",
                [$vehicleId]
            );
            
            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            exit;
        }
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter fehlen']);
        exit;
    }
    
    // PUT - Session abschließen
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Permission prüfen
        if (!canCompleteContainerInspection($user)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
            exit;
        }
        
        $sessionId = (int)($_GET['id'] ?? 0);
        $action = $_GET['action'] ?? '';
        
        if (!$sessionId || $action !== 'complete') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ungültige Parameter']);
            exit;
        }
        
        // Prüfe ob alle Items completed sind
        $pendingItems = $db->fetchOne(
            "SELECT COUNT(*) as count FROM container_inspection_items 
             WHERE container_inspection_id = ? AND status != 'completed'",
            [$sessionId]
        );
        
        if ($pendingItems['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => 'Nicht alle Container wurden geprüft'
            ]);
            exit;
        }
        
        // Session abschließen
        $db->query(
            "UPDATE container_inspections 
             SET status = 'completed', completed_at = NOW() 
             WHERE id = ?",
            [$sessionId]
        );
        
        echo json_encode(['success' => true, 'message' => 'Prüfung abgeschlossen']);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
