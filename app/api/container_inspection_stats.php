<?php
/**
 * api/container_inspection_stats.php
 * Statistiken für Container-Prüfungen
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Auth-Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nicht eingeloggt']);
    exit;
}

$user = getCurrentUser();

// Nur Admin und Fahrzeugwart dürfen Stats sehen
if (!in_array($user['role'], ['admin', 'fahrzeugwart'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Keine Berechtigung']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Query-Parameter
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    // 1. Container-Prüfungen pro Fahrzeug
    $inspectionsByVehicle = [];
    $result = $db->query("
        SELECT 
            v.id,
            v.name as vehicle_name,
            COUNT(DISTINCT ci.id) as total_inspections,
            COUNT(DISTINCT CASE WHEN ci.status = 'completed' THEN ci.id END) as completed_inspections,
            COUNT(DISTINCT cii.id) as total_containers_checked,
            AVG(TIMESTAMPDIFF(MINUTE, ci.started_at, ci.completed_at)) as avg_duration_minutes
        FROM vehicles v
        LEFT JOIN container_inspections ci ON v.id = ci.vehicle_id
            AND DATE(ci.started_at) BETWEEN ? AND ?
        LEFT JOIN container_inspection_items cii ON ci.id = cii.container_inspection_id
        GROUP BY v.id, v.name
        ORDER BY total_inspections DESC
    ", [$dateFrom, $dateTo]);
    
    foreach ($result->fetchAll() as $row) {
        $inspectionsByVehicle[] = $row;
    }
    
    // 2. Prüfer-Statistik
    $inspectorStats = [];
    $result = $db->query("
        SELECT 
            u.id,
            u.full_name,
            COUNT(DISTINCT ci.id) as inspections_started,
            COUNT(DISTINCT cii.id) as containers_checked,
            AVG(TIMESTAMPDIFF(MINUTE, ci.started_at, ci.completed_at)) as avg_session_duration
        FROM users u
        LEFT JOIN container_inspections ci ON u.id = ci.started_by
            AND DATE(ci.started_at) BETWEEN ? AND ?
        LEFT JOIN container_inspection_items cii ON ci.id = cii.container_inspection_id
            AND cii.inspector_name = u.full_name
        WHERE u.role IN ('admin', 'fahrzeugwart', 'kontrolle')
        GROUP BY u.id, u.full_name
        HAVING inspections_started > 0 OR containers_checked > 0
        ORDER BY containers_checked DESC
    ", [$dateFrom, $dateTo]);
    
    $inspectorStats = [];
    foreach ($result->fetchAll() as $row) {
        $inspectorStats[] = $row;
    }
    
    // 3. Durchschnittliche Prüfdauer pro Container
    $avgContainerDuration = $db->fetchOne("
        SELECT 
            AVG(TIMESTAMPDIFF(MINUTE, cii.started_at, cii.completed_at)) as avg_minutes,
            MIN(TIMESTAMPDIFF(MINUTE, cii.started_at, cii.completed_at)) as min_minutes,
            MAX(TIMESTAMPDIFF(MINUTE, cii.started_at, cii.completed_at)) as max_minutes,
            COUNT(*) as total_completed
        FROM container_inspection_items cii
        WHERE cii.status = 'completed'
            AND cii.started_at IS NOT NULL
            AND cii.completed_at IS NOT NULL
            AND DATE(cii.completed_at) BETWEEN ? AND ?
    ", [$dateFrom, $dateTo]);
    
    // 4. MHD-Warnungen aus Container-Prüfungen
    $mhdWarnings = [];
    $result = $db->query("
        SELECT 
            cpa.id,
            p.name as product_name,
            v.name as vehicle_name,
            ct.name as container_name,
            cp.name as compartment_name,
            cpa.expiry_date,
            DATEDIFF(cpa.expiry_date, CURDATE()) as days_until_expiry,
            cii.completed_at as found_at,
            cii.inspector_name
        FROM container_inspection_items cii
        JOIN container_inspections ci ON cii.container_inspection_id = ci.id
        JOIN containers ct ON cii.container_id = ct.id
        JOIN vehicles v ON ct.vehicle_id = v.id
        JOIN compartments cp ON ct.id = cp.container_id
        JOIN compartment_products_actual cpa ON cp.id = cpa.compartment_id
        JOIN products p ON cpa.product_id = p.id
        WHERE cii.status = 'completed'
            AND DATE(cii.completed_at) BETWEEN ? AND ?
            AND cpa.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 28 DAY)
        ORDER BY cpa.expiry_date ASC
        LIMIT 50
    ", [$dateFrom, $dateTo]);
    
    $mhdWarnings = [];
    foreach ($result->fetchAll() as $row) {
        $mhdWarnings[] = $row;
    }
    
    // 5. Gesamt-Statistiken
    $totalStats = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT ci.id) as total_sessions,
            COUNT(DISTINCT CASE WHEN ci.status = 'completed' THEN ci.id END) as completed_sessions,
            COUNT(DISTINCT CASE WHEN ci.status = 'in_progress' THEN ci.id END) as active_sessions,
            COUNT(DISTINCT cii.id) as total_containers_inspected,
            COUNT(DISTINCT cii.inspector_name) as unique_inspectors
        FROM container_inspections ci
        LEFT JOIN container_inspection_items cii ON ci.id = cii.container_inspection_id
        WHERE DATE(ci.started_at) BETWEEN ? AND ?
    ", [$dateFrom, $dateTo]);
    
    // Response
    echo json_encode([
        'success' => true,
        'period' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ],
        'total' => $totalStats,
        'by_vehicle' => $inspectionsByVehicle,
        'by_inspector' => $inspectorStats,
        'avg_container_duration' => $avgContainerDuration,
        'mhd_warnings' => $mhdWarnings
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Fehler beim Laden der Statistiken',
        'details' => $e->getMessage()
    ]);
}
