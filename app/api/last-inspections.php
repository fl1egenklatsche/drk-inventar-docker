<?php
/**
 * api/last-inspections.php
 * Letzte Kontrollen laden
 */

require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Nicht angemeldet']);
}

$inspections = getLastInspections();

sendJSON([
    'success' => true,
    'data' => $inspections
]);

?>