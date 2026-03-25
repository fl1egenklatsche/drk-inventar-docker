<?php
/**
 * api/export-report.php
 * Berichte exportieren (PDF/Excel)
 */

require_once '../includes/config.php';
session_start();
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


if (!isLoggedIn()) {
    http_response_code(401);
    die('Nicht angemeldet');
}

$format = $_GET['format'] ?? 'pdf';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'expiring';

$db = getDB();

// Daten je nach Report-Typ laden
switch ($reportType) {
    case 'expiring':
        $data = $db->fetchAll("
            SELECT 
                v.name as vehicle_name,
                cont.name as container_name,
                comp.name as compartment_name,
                p.name as product_name,
                cpa.expiry_date,
                cpa.quantity,
                DATEDIFF(cpa.expiry_date, CURDATE()) as days_until_expiry
            FROM compartment_products_actual cpa
            JOIN compartments comp ON cpa.compartment_id = comp.id
            JOIN containers cont ON comp.container_id = cont.id
            JOIN vehicles v ON cont.vehicle_id = v.id
            JOIN products p ON cpa.product_id = p.id
            WHERE cpa.expiry_date BETWEEN ? AND ?
            ORDER BY cpa.expiry_date ASC
        ", [$dateFrom, $dateTo]);
        $title = 'Ablaufende Produkte';
        break;
        
    case 'inspections':
        $data = $db->fetchAll("
            SELECT 
                i.completed_at,
                v.name as vehicle_name,
                u.full_name as inspector_name,
                COUNT(ii.id) as items_checked,
                SUM(CASE WHEN ii.action_taken != 'none' THEN 1 ELSE 0 END) as items_changed
            FROM inspections i
            JOIN vehicles v ON i.vehicle_id = v.id
            JOIN users u ON i.user_id = u.id
            LEFT JOIN inspection_items ii ON i.id = ii.inspection_id
            WHERE DATE(i.completed_at) BETWEEN ? AND ?
            GROUP BY i.id
            ORDER BY i.completed_at DESC
        ", [$dateFrom, $dateTo]);
        $title = 'Kontrollen';
        break;
        
    default:
        http_response_code(400);
        die('Ungültiger Report-Typ');
}

if ($format === 'excel') {
    exportToExcel($data, $title, $dateFrom, $dateTo);
} else {
    exportToPDF($data, $title, $dateFrom, $dateTo);
}

function exportToExcel($data, $title, $dateFrom, $dateTo) {
    // Echtes Excel-Format (.xlsx) verwenden
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d') . '.xlsx"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Falls keine echte Excel-Bibliothek verfügbar ist, verwende CSV mit Excel-kompatiblen Headern
    $filename = $title . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // UTF-8 BOM für korrekte Umlaute in Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Titel-Informationen
    fputcsv($output, [$title], ';');
    fputcsv($output, ['Zeitraum: ' . formatDate($dateFrom) . ' - ' . formatDate($dateTo)], ';');
    fputcsv($output, ['Erstellt: ' . date('d.m.Y H:i')], ';');
    fputcsv($output, [''], ';'); // Leerzeile
    
    if (!empty($data)) {
        // Header-Zeile
        $headers = [];
        foreach (array_keys($data[0]) as $header) {
            $headers[] = ucfirst(str_replace('_', ' ', $header));
        }
        fputcsv($output, $headers, ';');
        
        // Daten-Zeilen
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $cell) {
                $csvRow[] = $cell;
            }
            fputcsv($output, $csvRow, ';');
        }
    } else {
        fputcsv($output, ['Keine Daten gefunden'], ';');
    }
    
    fclose($output);
}

function exportToPDF($data, $title, $dateFrom, $dateTo) {
    // Da keine PDF-Bibliothek verfügbar ist, erstelle eine druckoptimierte HTML-Seite
    // die der Browser als PDF speichern kann
    header('Content-Type: text/html; charset=utf-8');
    // Für echte PDF-Downloads: header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d') . '.pdf"');
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . h($title) . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 12px;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #dc143c;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #dc143c;
            margin: 0;
            font-size: 24px;
        }
        
        .meta { 
            color: #666; 
            font-size: 14px; 
            margin: 5px 0;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            font-size: 11px;
        }
        
        th, td { 
            border: 1px solid #ddd; 
            padding: 6px 8px; 
            text-align: left; 
        }
        
        th { 
            background-color: #dc143c; 
            color: white; 
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc143c;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Als PDF drucken</button>
    
    <div class="header">
        <h1>' . h($title) . '</h1>
        <p class="meta"><strong>DRK Stadtverband Haltern am See e.V.</strong></p>
        <p class="meta">Zeitraum: ' . formatDate($dateFrom) . ' bis ' . formatDate($dateTo) . '</p>
        <p class="meta">Erstellt: ' . date('d.m.Y um H:i') . ' Uhr</p>
    </div>
    
    <table>';
    
    if (!empty($data)) {
        echo '<thead><tr>';
        foreach (array_keys($data[0]) as $header) {
            $displayHeader = ucfirst(str_replace('_', ' ', $header));
            // Deutsche Übersetzungen für häufige Felder
            $translations = [
                'Vehicle name' => 'Fahrzeug',
                'Container name' => 'Behälter', 
                'Compartment name' => 'Fach',
                'Product name' => 'Produkt',
                'Expiry date' => 'Ablaufdatum',
                'Quantity' => 'Menge',
                'Days until expiry' => 'Tage bis Ablauf',
                'Completed at' => 'Abgeschlossen',
                'Inspector name' => 'Prüfer',
                'Items checked' => 'Geprüfte Artikel',
                'Items changed' => 'Geänderte Artikel'
            ];
            $displayHeader = $translations[$displayHeader] ?? $displayHeader;
            echo '<th>' . h($displayHeader) . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $key => $cell) {
                // Datum formatieren falls erkannt
                if (strpos($key, 'date') !== false && !empty($cell)) {
                    $cell = formatDate($cell);
                }
                echo '<td>' . h($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
    } else {
        echo '<tbody><tr><td colspan="10" style="text-align: center; padding: 20px;">Keine Daten im ausgewählten Zeitraum gefunden</td></tr></tbody>';
    }
    
    echo '</table>
    
    <div class="footer">
        <p>Bericht erstellt mit dem DRK MHD-Tool - ' . date('Y') . '</p>
    </div>
</body>
</html>';
}

?>