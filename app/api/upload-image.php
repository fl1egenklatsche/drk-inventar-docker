<?php
/**
 * api/upload-image.php
 * Bild-Upload für Fächer und Behälter - Korrigierte Version
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

if (!isset($_FILES['image']) || !isset($_POST['type']) || !isset($_POST['id'])) {
    sendJSON(['success' => false, 'message' => 'Unvollständige Daten']);
}

$type = $_POST['type']; // 'container' oder 'compartment'
$id = (int)$_POST['id'];

// Upload-Verzeichnis korrekt setzen (relativ zum Hauptverzeichnis, nicht zum api-Ordner)
$uploadBaseDir = '../' . UPLOAD_DIR;
$uploadDir = $uploadBaseDir . $type . 's'; // containers/ oder compartments/

// Upload verarbeiten
$uploadResult = processFileUpload($_FILES['image'], $type . 's', $uploadDir);

if (!$uploadResult['success']) {
    sendJSON($uploadResult);
}

$db = getDB();

try {
    // Altes Foto löschen
    if ($type === 'container') {
        $oldPhoto = $db->fetchColumn("SELECT photo_path FROM containers WHERE id = ?", [$id]);
        $db->query("UPDATE containers SET photo_path = ? WHERE id = ?", [$uploadResult['filepath'], $id]);
    } else {
        $oldPhoto = $db->fetchColumn("SELECT photo_path FROM compartments WHERE id = ?", [$id]);
        $db->query("UPDATE compartments SET photo_path = ? WHERE id = ?", [$uploadResult['filepath'], $id]);
    }
    
    // Altes Foto von Festplatte löschen
    if ($oldPhoto && file_exists('../' . $oldPhoto)) {
        unlink('../' . $oldPhoto);
    }
    
    sendJSON([
        'success' => true,
        'message' => 'Foto erfolgreich hochgeladen',
        'filepath' => $uploadResult['filepath']
    ]);
    
} catch (Exception $e) {
    error_log('Image upload error: ' . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Fehler beim Speichern in der Datenbank']);
}

/**
 * Datei-Upload verarbeiten (lokale Funktion mit korrekten Pfaden)
 */
function processFileUpload($file, $subdir, $uploadDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Fehler beim Upload.'];
    }
    
    // Dateigröße prüfen
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'Datei ist zu groß (max. ' . round(UPLOAD_MAX_SIZE/1024/1024, 1) . ' MB).'];
    }
    
    // Dateityp prüfen (Extension)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'Dateityp nicht erlaubt. Erlaubt: ' . implode(', ', UPLOAD_ALLOWED_TYPES)];
    }
    
    // MIME-Type prüfen (Magic Bytes)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'message' => 'Ungültiger Dateityp (MIME-Check fehlgeschlagen)'];
    }
    
    // Zusätzlich: Prüfen ob es wirklich ein Bild ist
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'Datei ist kein gültiges Bild'];
    }
    
    // Upload-Verzeichnis erstellen falls nicht vorhanden
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Upload-Verzeichnis konnte nicht erstellt werden.'];
        }
    }
    
    // Eindeutigen Dateinamen generieren
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $fullPath = $uploadDir . '/' . $filename;
    
    // Relativen Pfad für Datenbank (ohne '../' Präfix)
    $relativePath = UPLOAD_DIR . $subdir . '/' . $filename;
    
    // Datei verschieben
    if (move_uploaded_file($file['tmp_name'], $fullPath)) {
        // Bild komprimieren falls nötig
        compressImageFile($fullPath, $extension);
        
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
 * Bild komprimieren (lokale Funktion)
 */
function compressImageFile($filepath, $extension, $quality = 80) {
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
    
    if (!$image) return false;
    
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

?>