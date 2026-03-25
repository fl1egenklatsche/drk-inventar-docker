<?php
/**
 * Neue API-Datei: api/manage-product-instance.php
 * Für das Verwalten individueller Produktinstanzen
 */
?>
<?php
// api/manage-product-instance.php
require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

requireCSRF();

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Nicht angemeldet']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Nur POST-Requests erlaubt']);
}

$action = $_POST['action'] ?? '';
$db = getDB();
$userId = getCurrentUser()['id'];

try {
    switch ($action) {
        case 'add_instance':
            $compartmentId = (int)($_POST['compartment_id'] ?? 0);
            $productId = (int)($_POST['product_id'] ?? 0);
            $expiryDate = $_POST['expiry_date'] ?? '';
            
            if (!$compartmentId || !$productId || !$expiryDate) {
                sendJSON(['success' => false, 'message' => 'Unvollständige Daten']);
            }
            
            // Neue Produktinstanz hinzufügen
            $db->query(
                "INSERT INTO compartment_products_actual 
                 (compartment_id, product_id, expiry_date, quantity, status, last_checked, last_checked_by) 
                 VALUES (?, ?, ?, 1, 'ok', NOW(), ?)",
                [$compartmentId, $productId, $expiryDate, $userId]
            );
            
            sendJSON(['success' => true, 'message' => 'Produktinstanz hinzugefügt']);
            break;
            
        case 'update_instance':
            $instanceId = (int)($_POST['instance_id'] ?? 0);
            $expiryDate = $_POST['expiry_date'] ?? '';
            
            if (!$instanceId || !$expiryDate) {
                sendJSON(['success' => false, 'message' => 'Unvollständige Daten']);
            }
            
            // Produktinstanz aktualisieren
            $result = $db->query(
                "UPDATE compartment_products_actual 
                 SET expiry_date = ?, last_checked = NOW(), last_checked_by = ? 
                 WHERE id = ?",
                [$expiryDate, $userId, $instanceId]
            );
            
            if ($result->rowCount() > 0) {
                sendJSON(['success' => true, 'message' => 'Produktinstanz aktualisiert']);
            } else {
                sendJSON(['success' => false, 'message' => 'Instanz nicht gefunden']);
            }
            break;
            
        case 'remove_instance':
            $instanceId = (int)($_POST['instance_id'] ?? 0);
            
            if (!$instanceId) {
                sendJSON(['success' => false, 'message' => 'Ungültige Instanz-ID']);
            }
            
            // Produktinstanz entfernen
            $result = $db->query(
                "DELETE FROM compartment_products_actual WHERE id = ?",
                [$instanceId]
            );
            
            if ($result->rowCount() > 0) {
                sendJSON(['success' => true, 'message' => 'Produktinstanz entfernt']);
            } else {
                sendJSON(['success' => false, 'message' => 'Instanz nicht gefunden']);
            }
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Ungültige Aktion']);
    }
    
} catch (Exception $e) {
    error_log('Product instance error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Datenbankfehler']);
}
?>