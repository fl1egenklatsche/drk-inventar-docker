<?php
/**
 * api/vehicle-structure.php
 * Fahrzeugstruktur für Kontrollen laden
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

if (!isset($_GET['vehicle_id']) || !is_numeric($_GET['vehicle_id'])) {
    sendJSON(['success' => false, 'message' => 'Ungültige Fahrzeug-ID']);
}

$vehicleId = (int)$_GET['vehicle_id'];
$vehicle = getVehicleStructure($vehicleId);

if (!$vehicle) {
    sendJSON(['success' => false, 'message' => 'Fahrzeug nicht gefunden']);
}

// Fächer flach sammeln und sortieren
$allCompartments = [];
foreach ($vehicle['containers'] as $container) {
    foreach ($container['compartments'] as $compartment) {
        $compartment['container_name'] = $container['name'];
        $compartment['container_color'] = $container['color_code'];
        $compartment['container_sort'] = $container['sort_order'];
        $allCompartments[] = $compartment;
    }
}

// Sortierung: erst Container, dann Fächer
usort($allCompartments, function($a, $b) {
    if ($a['container_sort'] != $b['container_sort']) {
        return $a['container_sort'] <=> $b['container_sort'];
    }
    return $a['sort_order'] <=> $b['sort_order'];
});

sendJSON([
    'success' => true,
    'data' => [
        'vehicle' => $vehicle,
        'compartments' => $allCompartments
    ]
]);

?>