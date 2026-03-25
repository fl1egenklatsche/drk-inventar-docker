<?php
/**
 * Neue API-Datei: api/get-product.php
 * Erstelle diese Datei für das Edit-Modal
 */
?>
<?php
// api/get-product.php
require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || !isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Keine Berechtigung']);
}

$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    sendJSON(['success' => false, 'message' => 'Ungültige Produkt-ID']);
}

$db = getDB();

try {
    $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    
    if (!$product) {
        sendJSON(['success' => false, 'message' => 'Produkt nicht gefunden']);
    }
    
    // Zusätzliche Infos über Verwendung
    $usageCount = $db->fetchColumn(
        "SELECT COUNT(*) FROM compartment_products_target WHERE product_id = ?",
        [$productId]
    );
    
    $product['usage_count'] = $usageCount;
    
    sendJSON([
        'success' => true,
        'data' => $product
    ]);
    
} catch (Exception $e) {
    error_log('Get product error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Datenbankfehler']);
}
?>