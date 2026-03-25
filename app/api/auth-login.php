<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Benutzername und Passwort erforderlich']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // User laden
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE username = ? AND active = 1",
        [$username]
    );
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Ungültige Anmeldedaten']);
        exit;
    }
    
    // Passwort prüfen
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Ungültige Anmeldedaten']);
        exit;
    }
    
    // Token generieren (einfach - in Production besser JWT verwenden)
    $token = bin2hex(random_bytes(32));
    
    // Token in Session speichern (optional: eigene Tabelle für Tokens)
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['token'] = $token;
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['full_name'],
            'role' => $user['role'],
        ],
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server-Fehler']);
}
