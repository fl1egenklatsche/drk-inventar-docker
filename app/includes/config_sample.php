<?php
/**
 * includes/config_sample.php
 * Konfigurationsvorlage für DRK Inventar
 * 
 * ANLEITUNG:
 * 1. Diese Datei nach config.php kopieren
 * 2. Platzhalter durch echte Werte ersetzen
 */

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');          // z.B. localhost oder 127.0.0.1
define('DB_PORT', '3306');               // Standard MySQL Port
define('DB_NAME', 'deine_datenbank');    // Name der Datenbank
define('DB_USER', 'dein_benutzer');      // Datenbankbenutzer
define('DB_PASS', 'dein_passwort');      // Datenbankpasswort
define('DB_CHARSET', 'utf8mb4');

// Session-Konfiguration
define('SESSION_LIFETIME', 3600); // 1 Stunde

// Anwendungskonfiguration
define('APP_NAME', 'DRK Inventar');
define('APP_VERSION', '1.0.0');

// Upload-Konfiguration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

// Debug-Modus (auf false in Produktion setzen!)
define('DEBUG_MODE', false);

// Fehlerbehandlung
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Berlin');
