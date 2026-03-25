<?php
/**
 * api/save-inspection.php
 * Kontrolle speichern - Korrigierte Version
 */

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

$input = json_decode(file_get_contents('php://input'), true);

// Debug-Logging
if (APP_ENV !== 'production') error_log('=== INSPECTION SAVE START ===');
if (APP_ENV !== 'production') error_log('Inspection save data (keys): ' . implode(',', array_keys($input ?? [])));

if (!$input || !isset($input['vehicle_id']) || !isset($input['inspection_data'])) {
    error_log('ERROR: Missing required data');
    sendJSON(['success' => false, 'message' => 'Ungültige Daten - vehicle_id oder inspection_data fehlt']);
}

$vehicleId = (int)$input['vehicle_id'];
$inspectionData = $input['inspection_data'];
$userId = getCurrentUser()['id'];

error_log('Vehicle ID: ' . $vehicleId);
error_log('User ID: ' . $userId);

// Datum konvertieren falls ISO-Format vorhanden
$startedAt = date('Y-m-d H:i:s');
if (isset($input['started_at']) && !empty($input['started_at'])) {
    try {
        $dateTime = new DateTime($input['started_at']);
        $startedAt = $dateTime->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        error_log('Date conversion error: ' . $e->getMessage());
        // Fallback auf aktuelles Datum
        $startedAt = date('Y-m-d H:i:s');
    }
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Debug-Logging für Inspection-Insert
    $status = 'completed';
    $completedAt = date('Y-m-d H:i:s');
    error_log("Inserting inspection with status: '$status'");
    error_log("Vehicle ID: $vehicleId, User ID: $userId, Started: $startedAt, Completed: $completedAt");
    
    // Kontrolle erstellen - verwende explizite Werte
    $insertQuery = "INSERT INTO inspections (vehicle_id, user_id, started_at, completed_at, status) VALUES (?, ?, ?, ?, ?)";
    $insertParams = [$vehicleId, $userId, $startedAt, $completedAt, $status];
    
    error_log("SQL Query: $insertQuery");
    error_log("SQL Params: " . print_r($insertParams, true));
    
    $db->query($insertQuery, $insertParams);
    
    $inspectionId = $db->lastInsertId();
    error_log("Created inspection with ID: $inspectionId");
    
    // Für jedes Fach die Produktinstanzen verarbeiten
    foreach ($inspectionData as $compartmentId => $compartmentData) {
        $compartmentId = (int)$compartmentId;
        
        if (!isset($compartmentData['products']) || !is_array($compartmentData['products'])) {
            continue;
        }
        
        foreach ($compartmentData['products'] as $productId => $productData) {
            $productId = (int)$productId;
            
            if (!isset($productData['instances']) || !is_array($productData['instances'])) {
                continue;
            }
            
            // Alte IST-Bestückung für dieses Produkt in diesem Fach löschen
            $db->query(
                "DELETE FROM compartment_products_actual WHERE compartment_id = ? AND product_id = ?",
                [$compartmentId, $productId]
            );
            
            // Neue Instanzen einfügen
            $instanceCount = 0;
            foreach ($productData['instances'] as $instanceData) {
                if (!isset($instanceData['expiry_date']) || empty($instanceData['expiry_date'])) {
                    continue; // Leere Instanzen überspringen
                }
                
                $expiryDate = $instanceData['expiry_date'];
                $status = $instanceData['status'] ?? 'ok';
                
                // Status basierend auf Ablaufdatum bestimmen
                $daysUntilExpiry = floor((strtotime($expiryDate) - time()) / (60 * 60 * 24));
                if ($daysUntilExpiry < 0) {
                    $status = 'expired';
                } else if ($daysUntilExpiry <= 28) {
                    $status = 'expiring_soon';
                } else {
                    $status = 'ok';
                }
                
                // Neue Produktinstanz einfügen
                $db->query(
                    "INSERT INTO compartment_products_actual (
                        compartment_id, product_id, expiry_date, quantity, status, 
                        last_checked, last_checked_by
                    ) VALUES (?, ?, ?, 1, ?, NOW(), ?)",
                    [$compartmentId, $productId, $expiryDate, $status, $userId]
                );
                
                $actualId = $db->lastInsertId();
                $instanceCount++;
                
                // Kontrolldetail speichern
                $db->query(
                    "INSERT INTO inspection_items (
                        inspection_id, compartment_id, product_id, 
                        status_before, status_after, 
                        expiry_date_before, expiry_date_after,
                        action_taken, checked_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $inspectionId, $compartmentId, $productId,
                        'unknown', $status,
                        null, $expiryDate,
                        'updated'
                    ]
                );
            }
            
            // Log für Debugging
            error_log("Processed product $productId in compartment $compartmentId: $instanceCount instances saved");
        }
    }
    
    $db->commit();
    
    sendJSON([
        'success' => true, 
        'message' => 'Kontrolle erfolgreich gespeichert',
        'inspection_id' => $inspectionId
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    error_log('Inspection save error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    sendJSON(['success' => false, 'message' => 'Fehler beim Speichern der Kontrolle: ' . $e->getMessage()]);
}

?>