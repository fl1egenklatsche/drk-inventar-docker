<?php
/**
 * includes/csrf.php
 * CSRF-Protection Middleware
 * 
 * Einbinden in API-Endpoints die POST/DELETE verarbeiten:
 *   require_once '../includes/csrf.php';
 *   requireCSRF(); // prüft Token bei POST/PUT/DELETE
 */

/**
 * CSRF-Token aus Request lesen (POST-Body, JSON-Body oder Header)
 */
function getCSRFTokenFromRequest() {
    // 1. POST-Parameter
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    
    // 2. X-CSRF-Token Header (für AJAX/JSON-Requests)
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-csrf-token') {
            return $value;
        }
    }
    
    // 3. JSON-Body
    $input = file_get_contents('php://input');
    if ($input) {
        $json = json_decode($input, true);
        if (isset($json['csrf_token'])) {
            return $json['csrf_token'];
        }
    }
    
    return null;
}

/**
 * CSRF-Validierung erzwingen für schreibende Requests
 */
function requireCSRF() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Nur schreibende Methoden prüfen
    if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        return true;
    }
    
    $token = getCSRFTokenFromRequest();
    
    if (!$token || !validateCSRFToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'CSRF-Token ungültig oder fehlend'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    return true;
}

?>
