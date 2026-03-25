<?php
/**
 * Neue API-Datei: api/delete-product.php
 * Erstelle diese Datei für das Löschen
 */
?>
<?php
// api/delete-product.php
require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

requireCSRF();

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Keine Berechtigung']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Nur POST-Requests erlaubt']);
}

$productId = (int)($_POST['id'] ?? 0);

if (!$productId) {
    sendJSON(['success' => false, 'message' => 'Ungültige Produkt-ID']);
}

$db = getDB();

try {
    // Prüfen ob Produkt verwendet wird
    $usageCount = $db->fetchColumn(
        "SELECT COUNT(*) FROM compartment_products_target WHERE product_id = ?",
        [$productId]
    );
    
    if ($usageCount > 0) {
        sendJSON([
            'success' => false, 
            'message' => "Produkt kann nicht gelöscht werden - es wird in $usageCount Fächern verwendet."
        ]);
    }
    
    // Produkt löschen
    $result = $db->query("DELETE FROM products WHERE id = ?", [$productId]);
    
    if ($result->rowCount() > 0) {
        sendJSON(['success' => true, 'message' => 'Produkt erfolgreich gelöscht']);
    } else {
        sendJSON(['success' => false, 'message' => 'Produkt nicht gefunden']);
    }
    
} catch (Exception $e) {
    error_log('Delete product error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Datenbankfehler']);
}
?>