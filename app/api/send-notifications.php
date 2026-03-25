<?php
/**
 * api/send-notifications.php
 * E-Mail-Benachrichtigungen für ablaufende Produkte
 * (Wird als Cron-Job ausgeführt)
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Prüfen ob als Cron-Job ausgeführt wird
if (php_sapi_name() !== 'cli') {
    $providedKey = $_GET['cron_key'] ?? '';
    $validKey = defined('CRON_SECRET_KEY') ? CRON_SECRET_KEY : '';
    
    if (empty($validKey) || empty($providedKey) || !hash_equals($validKey, $providedKey)) {
        http_response_code(403);
        die('Forbidden');
    }
}

$db = getDB();

// Produkte die in den nächsten 3 Monaten ablaufen
$expiringProducts = $db->fetchAll("
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
    WHERE cpa.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    AND cpa.status != 'missing'
    ORDER BY cpa.expiry_date ASC
");

if (empty($expiringProducts)) {
    echo "Keine ablaufenden Produkte gefunden.\n";
    exit;
}

// E-Mail erstellen
$emailTo = getSetting('email_notifications');
if (!$emailTo) {
    echo "Keine E-Mail-Adresse konfiguriert.\n";
    exit;
}

$subject = 'Ablaufende Medizinprodukte - DRK Haltern';

$message = '<html><body>';
$message .= '<h2>Ablaufende Medizinprodukte</h2>';
$message .= '<p>Die folgenden Produkte laufen in den nächsten 3 Monaten ab:</p>';

$message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
$message .= '<tr style="background-color: #dc143c; color: white;">';
$message .= '<th>Fahrzeug</th><th>Behälter</th><th>Fach</th><th>Produkt</th><th>Ablaufdatum</th><th>Tage verbleibend</th>';
$message .= '</tr>';

foreach ($expiringProducts as $product) {
    $rowColor = '';
    if ($product['days_until_expiry'] < 0) {
        $rowColor = ' style="background-color: #ffcccc;"'; // Rot für abgelaufen
    } elseif ($product['days_until_expiry'] <= 7) {
        $rowColor = ' style="background-color: #ffe6cc;"'; // Orange für bald abgelaufen
    }
    
    $message .= '<tr' . $rowColor . '>';
    $message .= '<td>' . h($product['vehicle_name']) . '</td>';
    $message .= '<td>' . h($product['container_name']) . '</td>';
    $message .= '<td>' . h($product['compartment_name']) . '</td>';
    $message .= '<td>' . h($product['product_name']) . '</td>';
    $message .= '<td>' . formatDate($product['expiry_date']) . '</td>';
    $message .= '<td>' . ($product['days_until_expiry'] < 0 ? 'Abgelaufen' : $product['days_until_expiry'] . ' Tage') . '</td>';
    $message .= '</tr>';
}

$message .= '</table>';
$message .= '<br><p>Diese E-Mail wurde automatisch vom Medizinprodukt-Verwaltungssystem generiert.</p>';
$message .= '<p>DRK Stadtverband Haltern am See e.V.</p>';
$message .= '</body></html>';

// E-Mail senden
if (sendEmail($emailTo, $subject, $message, true)) {
    echo "E-Mail erfolgreich gesendet an: $emailTo\n";
    echo "Anzahl ablaufende Produkte: " . count($expiringProducts) . "\n";
} else {
    echo "Fehler beim Senden der E-Mail\n";
}

?>