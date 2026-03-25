<?php
/**
 * includes/auth.php
 * Authentifizierung und Benutzerverwaltung
 */

// Konstanten
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 8);
}

/**
 * Rate-Limiting prüfen
 */
function checkLoginRateLimit() {
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockoutUntil = $_SESSION['lockout_until'] ?? 0;
    
    if ($lockoutUntil > 0 && time() < $lockoutUntil) {
        $remaining = ceil(($lockoutUntil - time()) / 60);
        return "Zu viele Fehlversuche. Account für {$remaining} Minuten gesperrt.";
    }
    
    // Lockout abgelaufen → Reset
    if ($lockoutUntil > 0 && time() >= $lockoutUntil) {
        unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
    }
    
    return true;
}

/**
 * Fehlgeschlagenen Login-Versuch zählen
 */
function recordFailedLogin() {
    $attempts = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;
    
    if ($attempts >= 5) {
        $_SESSION['lockout_until'] = time() + 900; // 15 Minuten
    }
}

/**
 * Login-Attempts bei Erfolg zurücksetzen
 */
function resetLoginAttempts() {
    unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
}

/**
 * Benutzer anmelden
 */
function login($username, $password) {
    // Rate-Limiting prüfen
    $rateCheck = checkLoginRateLimit();
    if ($rateCheck !== true) {
        return false; // Caller zeigt generische Fehlermeldung
    }
    
    $db = getDB();
    
    $user = $db->fetchOne(
        "SELECT id, username, password_hash, full_name, email, role, active 
         FROM users 
         WHERE username = ? AND active = 1", 
        [$username]
    );
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Rate-Limit zurücksetzen
        resetLoginAttempts();
        // Login-Zeit aktualisieren
        $db->query(
            "UPDATE users SET last_login = NOW() WHERE id = ?", 
            [$user['id']]
        );
        
        // Session-Daten setzen
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    // Fehlgeschlagenen Versuch zählen
    recordFailedLogin();
    return false;
}

/**
 * Prüfen ob Benutzer eingeloggt ist
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Session-Timeout prüfen
    if (isset($_SESSION['login_time']) && 
        (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Prüfen ob Benutzer Administrator ist
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Aktuellen Benutzer zurückgeben
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Passwort-Validierung
 */
function validatePassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return "Passwort muss mindestens " . PASSWORD_MIN_LENGTH . " Zeichen lang sein.";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return "Passwort muss mindestens einen Großbuchstaben enthalten.";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return "Passwort muss mindestens einen Kleinbuchstaben enthalten.";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return "Passwort muss mindestens eine Ziffer enthalten.";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Passwort muss mindestens ein Sonderzeichen enthalten.";
    }
    
    return true;
}

/**
 * Benutzer erstellen
 */
function createUser($username, $password, $fullName, $email, $role = 'user') {
    $db = getDB();
    
    // Validierung
    $passwordValidation = validatePassword($password);
    if ($passwordValidation !== true) {
        return ['success' => false, 'message' => $passwordValidation];
    }
    
    // Prüfen ob Benutzername bereits existiert
    $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        return ['success' => false, 'message' => 'Benutzername bereits vergeben.'];
    }
    
    // Benutzer erstellen
    try {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $db->query(
            "INSERT INTO users (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)",
            [$username, $passwordHash, $fullName, $email, $role]
        );
        
        return ['success' => true, 'message' => 'Benutzer erfolgreich erstellt.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Fehler beim Erstellen des Benutzers.'];
    }
}

/**
 * CSRF-Token generieren
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Prüfen ob Benutzer Container-Prüfung starten darf
 */
function canStartContainerInspection($user) {
    return in_array($user['role'], ['admin', 'fahrzeugwart']);
}

/**
 * Prüfen ob Benutzer Container-Prüfung abschließen darf
 */
function canCompleteContainerInspection($user) {
    return in_array($user['role'], ['admin', 'fahrzeugwart']);
}

/**
 * Prüfen ob Benutzer Container prüfen darf
 */
function canInspectContainer($user) {
    return in_array($user['role'], ['admin', 'fahrzeugwart', 'kontrolle']);
}

/**
 * Prüfen ob Benutzer nur Kontrolle-Rolle hat
 */
function isKontrolleOnly($user) {
    return $user['role'] === 'kontrolle';
}

?>