<?php
/**
 * API: Rename Container
 * POST /api/rename-container.php
 * Body: {"id": 123, "name": "New Name"}
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check auth
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id or name']);
    exit;
}

$id = (int)$input['id'];
$name = trim($input['name']);

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name cannot be empty']);
    exit;
}

$db = Database::getInstance();

// Check if container exists
$container = $db->fetchOne("SELECT * FROM containers WHERE id = ?", [$id]);
if (!$container) {
    http_response_code(404);
    echo json_encode(['error' => 'Container not found']);
    exit;
}

// Update name
$db->query("UPDATE containers SET name = ? WHERE id = ?", [$name, $id]);

echo json_encode([
    'success' => true,
    'message' => 'Container renamed',
    'id' => $id,
    'name' => $name
]);
