<?php
/**
 * includes/functions.php
 * Allgemeine Hilfsfunktionen
 */

/**
 * Systemeinstellungen laden
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $db = getDB();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings[$key] ?? $default;
}

/**
 * Systemeinstellung speichern
 */
function setSetting($key, $value) {
    $db = getDB();
    $db->query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

/**
 * Ablaufende Produkte für Dashboard
 */
function getExpiringProducts($weeksAhead = 4) {
    $db = getDB();
    return $db->fetchAll("SELECT * FROM v_expiring_products WHERE days_until_expiry <= ?", [$weeksAhead * 7]);
}

/**
 * Letzte Kontrollen pro Fahrzeug
 */
function getLastInspections() {
    $db = getDB();
    // Query without view - works even if inspections table doesn't exist yet
    $sql = "SELECT 
                v.id AS vehicle_id,
                v.name AS vehicle_name,
                i.completed_at AS completed_at,
                u.full_name AS inspector_name
            FROM vehicles v
            LEFT JOIN inspections i ON (
                v.id = i.vehicle_id 
                AND i.status = 'completed'
                AND i.completed_at = (
                    SELECT MAX(i2.completed_at) 
                    FROM inspections i2 
                    WHERE i2.vehicle_id = v.id 
                    AND i2.status = 'completed'
                )
            )
            LEFT JOIN users u ON i.user_id = u.id
            ORDER BY v.name";
    
    // Handle case where inspections table doesn't exist yet (fresh install)
    try {
        return $db->fetchAll($sql);
    } catch (PDOException $e) {
        // If inspections table doesn't exist, return just vehicles
        if (strpos($e->getMessage(), 'inspections') !== false) {
            return $db->fetchAll("SELECT id AS vehicle_id, name AS vehicle_name, NULL AS completed_at, NULL AS inspector_name FROM vehicles ORDER BY name");
        }
        throw $e;
    }
}

/**
 * Fahrzeug-Liste
 */
function getVehicles($activeOnly = true) {
    $db = getDB();
    $sql = "SELECT * FROM vehicles" . ($activeOnly ? " WHERE active = 1" : "") . " ORDER BY name";
    return $db->fetchAll($sql);
}

/**
 * Fahrzeug-Details mit Behältern und Fächern
 */
function getVehicleStructure($vehicleId) {
    $db = getDB();
    
    $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = ?", [$vehicleId]);
    if (!$vehicle) return null;
    
    // Behälter laden
    $containers = $db->fetchAll(
        "SELECT * FROM containers WHERE vehicle_id = ? AND active = 1 ORDER BY sort_order, name",
        [$vehicleId]
    );
    
    foreach ($containers as &$container) {
        // Fächer laden
        $container['compartments'] = $db->fetchAll(
            "SELECT * FROM compartments WHERE container_id = ? AND active = 1 ORDER BY sort_order, name",
            [$container['id']]
        );
        
        foreach ($container['compartments'] as &$compartment) {
            // SOLL-Bestückung (Produkttypen mit Zielmengen)
            $compartment['target_products'] = $db->fetchAll(
                "SELECT cpt.*, p.name as product_name, p.description as product_description
                 FROM compartment_products_target cpt
                 JOIN products p ON cpt.product_id = p.id
                 WHERE cpt.compartment_id = ?",
                [$compartment['id']]
            );
            
            // IST-Bestückung (Individuelle Produktinstanzen)
            $compartment['actual_products'] = $db->fetchAll(
                "SELECT cpa.*, p.name as product_name, p.description as product_description,
                        DATEDIFF(cpa.expiry_date, CURDATE()) as days_until_expiry
                 FROM compartment_products_actual cpa
                 JOIN products p ON cpa.product_id = p.id
                 WHERE cpa.compartment_id = ?
                 ORDER BY p.name, cpa.expiry_date",
                [$compartment['id']]
            );
            
            // Gruppiere nach Produkttyp für bessere Übersicht
            $compartment['product_groups'] = groupProductInstances($compartment['actual_products']);
        }
    }
    
    $vehicle['containers'] = $containers;
    return $vehicle;
}

/**
 * Neue Hilfsfunktion: Gruppiert Produktinstanzen nach Typ
 */
function groupProductInstances($actualProducts) {
    $groups = [];
    
    foreach ($actualProducts as $product) {
        $productId = $product['product_id'];
        
        if (!isset($groups[$productId])) {
            $groups[$productId] = [
                'product_id' => $productId,
                'product_name' => $product['product_name'],
                'product_description' => $product['product_description'],
                'instances' => [],
                'total_count' => 0,
                'ok_count' => 0,
                'expired_count' => 0,
                'missing_count' => 0
            ];
        }
        
        $groups[$productId]['instances'][] = $product;
        $groups[$productId]['total_count']++;
        
        // Zähle Status
        switch ($product['status']) {
            case 'ok':
                $groups[$productId]['ok_count']++;
                break;
            case 'expired':
                $groups[$productId]['expired_count']++;
                break;
            case 'missing':
                $groups[$productId]['missing_count']++;
                break;
        }
    }
    
    return $groups;
}

/**
 * Datei-Upload verarbeiten (korrigierte Version)
 */
function handleFileUpload($file, $subdir = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Fehler beim Upload.'];
    }
    
    // Dateigröße prüfen
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'Datei ist zu groß (max. ' . round(UPLOAD_MAX_SIZE/1024/1024, 1) . ' MB).'];
    }
    
    // Dateityp prüfen
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Dateityp nicht erlaubt. Erlaubt: ' . implode(', ', UPLOAD_ALLOWED_TYPES)];
    }
    
    // Upload-Verzeichnis erstellen (absoluter Pfad)
    $uploadDir = UPLOAD_DIR . $subdir;
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Upload-Verzeichnis konnte nicht erstellt werden.'];
        }
    }
    
    // Eindeutigen Dateinamen generieren
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $fullPath = $uploadDir . '/' . $filename;
    $relativePath = $uploadDir . '/' . $filename; // Für Datenbank
    
    // Datei verschieben
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        // Bild komprimieren falls nötig
        compressImage($fullPath);
        
        return [
            'success' => true, 
            'filepath' => $relativePath, 
            'filename' => $filename,
            'full_path' => $fullPath
        ];
    }
    
    return ['success' => false, 'message' => 'Fehler beim Speichern der Datei.'];
}

/**
 * Bild komprimieren
 */
function compressImage($filepath, $quality = 80) {
    $info = getimagesize($filepath);
    if (!$info) return false;
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($filepath);
            break;
        default:
            return false;
    }
    
    // Maximale Auflösung: 1920x1080
    $maxWidth = 1920;
    $maxHeight = 1080;
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Transparenz für PNG beibehalten
        if ($mime === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Bild speichern
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($resized, $filepath, $quality);
                break;
            case 'image/png':
                imagepng($resized, $filepath, 9);
                break;
        }
        
        imagedestroy($resized);
    } else if ($mime === 'image/jpeg') {
        // Nur Qualität reduzieren
        imagejpeg($image, $filepath, $quality);
    }
    
    imagedestroy($image);
    return true;
}

/**
 * JSON-Response senden
 */
function sendJSON($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sanitize HTML
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Datum formatieren
 */
function formatDate($date, $format = 'd.m.Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Relative Zeit anzeigen
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'gerade eben';
    if ($time < 3600) return floor($time/60) . ' Min';
    if ($time < 86400) return floor($time/3600) . ' Std';
    if ($time < 2592000) return floor($time/86400) . ' Tage';
    if ($time < 31536000) return floor($time/2592000) . ' Monate';
    
    return floor($time/31536000) . ' Jahre';
}

/**
 * E-Mail versenden
 */
function sendEmail($to, $subject, $message, $isHTML = true) {
    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . getSetting('email_notifications') . '>',
        'Reply-To: ' . getSetting('email_notifications'),
        'X-Mailer: PHP/' . phpversion()
    ];
    
    if ($isHTML) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
    }
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

?>