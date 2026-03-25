<?php
/**
 * api/manage-vehicle.php
 * Fahrzeug-Verwaltung (CRUD)
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Nur POST-Requests erlaubt']);
        }
        
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name) || empty($type)) {
            sendJSON(['success' => false, 'message' => 'Name und Typ sind erforderlich']);
        }
        
        try {
            $db->query(
                "INSERT INTO vehicles (name, type, description) VALUES (?, ?, ?)",
                [$name, $type, $description]
            );
            
            sendJSON([
                'success' => true,
                'message' => 'Fahrzeug erfolgreich erstellt',
                'id' => $db->lastInsertId()
            ]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Fehler beim Erstellen des Fahrzeugs']);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJSON(['success' => false, 'message' => 'Nur POST-Requests erlaubt']);
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if (!$id || empty($name) || empty($type)) {
            sendJSON(['success' => false, 'message' => 'Ungültige Daten']);
        }
        
        try {
            $db->query(
                "UPDATE vehicles SET name = ?, type = ?, description = ? WHERE id = ?",
                [$name, $type, $description, $id]
            );
            
            sendJSON(['success' => true, 'message' => 'Fahrzeug erfolgreich aktualisiert']);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Fehler beim Aktualisieren des Fahrzeugs']);
        }
        break;
        
    case 'delete':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if (!$id) {
            sendJSON(['success' => false, 'message' => 'Ungültige ID']);
        }
        
        try {
            // Prüfen ob Kontrollen existieren
            $hasInspections = $db->fetchColumn(
                "SELECT COUNT(*) FROM inspections WHERE vehicle_id = ?",
                [$id]
            );
            
            if ($hasInspections > 0) {
                // Fahrzeug nur deaktivieren, nicht löschen
                $db->query("UPDATE vehicles SET active = 0 WHERE id = ?", [$id]);
                $message = 'Fahrzeug wurde deaktiviert (Kontrollen vorhanden)';
            } else {
                // Fahrzeug komplett löschen
                $db->query("DELETE FROM vehicles WHERE id = ?", [$id]);
                $message = 'Fahrzeug wurde gelöscht';
            }
            
            sendJSON(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            sendJSON(['success' => false, 'message' => 'Fehler beim Löschen des Fahrzeugs']);
        }
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            sendJSON(['success' => false, 'message' => 'Ungültige ID']);
        }
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = ?", [$id]);
        
        if (!$vehicle) {
            sendJSON(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
        }
        
        sendJSON(['success' => true, 'data' => $vehicle]);
        break;
        
    default:
        sendJSON(['success' => false, 'message' => 'Ungültige Aktion']);
}

?>