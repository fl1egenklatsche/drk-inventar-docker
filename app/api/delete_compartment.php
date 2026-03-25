<?php
/**
 * api/delete-compartment.php
 * Fach löschen
 */

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

$compartmentId = (int)($_POST['id'] ?? 0);

if (!$compartmentId) {
    sendJSON(['success' => false, 'message' => 'Ungültige Fach-ID']);
}

$db = getDB();

try {
    // Prüfen ob Fach verwendet wird (SOLL-Bestückung oder IST-Bestückung)
    $targetUsage = $db->fetchColumn(
        "SELECT COUNT(*) FROM compartment_products_target WHERE compartment_id = ?",
        [$compartmentId]
    );
    
    $actualUsage = $db->fetchColumn(
        "SELECT COUNT(*) FROM compartment_products_actual WHERE compartment_id = ?",
        [$compartmentId]
    );
    
    $inspectionUsage = $db->fetchColumn(
        "SELECT COUNT(*) FROM inspection_items WHERE compartment_id = ?",
        [$compartmentId]
    );
    
    $totalUsage = $targetUsage + $actualUsage + $inspectionUsage;
    
    if ($totalUsage > 0) {
        // Fach nur deaktivieren, nicht löschen
        $result = $db->query("UPDATE compartments SET active = 0 WHERE id = ?", [$compartmentId]);
        $message = 'Fach wurde deaktiviert (Daten vorhanden)';
    } else {
        // Fach komplett löschen
        $result = $db->query("DELETE FROM compartments WHERE id = ?", [$compartmentId]);
        $message = 'Fach wurde gelöscht';
    }
    
    if ($result->rowCount() > 0) {
        sendJSON(['success' => true, 'message' => $message]);
    } else {
        sendJSON(['success' => false, 'message' => 'Fach nicht gefunden']);
    }
    
} catch (Exception $e) {
    error_log('Delete compartment error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Datenbankfehler']);
}
?>