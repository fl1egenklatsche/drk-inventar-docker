<?php
/**
 * api/ping.php
 * Verbindungstest
 */

require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    sendJSON(['success' => false, 'message' => 'Nicht angemeldet']);
}

try {
    $db = getDB();
    $db->fetchColumn("SELECT 1");
    sendJSON(['success' => true, 'timestamp' => time()]);
} catch (Exception $e) {
    http_response_code(500);
    sendJSON(['success' => false, 'message' => 'Datenbankfehler']);
}

?>