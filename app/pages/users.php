<?php
/**
 * pages/users.php
 * Benutzerverwaltung (nur für Admins)
 */

if (!isAdmin()) {
    header('Location: ?page=403');
    exit;
}

$db = getDB();

// POST-Requests verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            
            if (empty($username) || empty($password) || empty($fullName)) {
                $error = 'Benutzername, Passwort und vollständiger Name sind erforderlich.';
            } else {
                $result = createUser($username, $password, $fullName, $email, $role);
                if ($result['success']) {
                    header('Location: ?page=users&success=user_added');
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
            break;
            
        case 'edit_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $active = isset($_POST['active']) ? 1 : 0;
            $newPassword = $_POST['new_password'] ?? '';
            
            if (!$userId || empty($username) || empty($fullName)) {
                $error = 'Ungültige Daten.';
            } else {
                try {
                    // Benutzername-Konflikt prüfen
                    $existing = $db->fetchOne(
                        "SELECT id FROM users WHERE username = ? AND id != ?", 
                        [$username, $userId]
                    );
                    
                    if ($existing) {
                        $error = 'Benutzername bereits vergeben.';
                    } else {
                        if (!empty($newPassword)) {
                            $passwordValidation = validatePassword($newPassword);
                            if ($passwordValidation !== true) {
                                $error = $passwordValidation;
                            } else {
                                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                                $db->query(
                                    "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, active = ?, password_hash = ? WHERE id = ?",
                                    [$username, $fullName, $email, $role, $active, $passwordHash, $userId]
                                );
                            }
                        } else {
                            $db->query(
                                "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, active = ? WHERE id = ?",
                                [$username, $fullName, $email, $role, $active, $userId]
                            );
                        }
                        
                        if (!isset($error)) {
                            header('Location: ?page=users&success=user_updated');
                            exit;
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Fehler beim Aktualisieren des Benutzers.';
                }
            }
            break;
            
        case 'delete_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            
            if (!$userId || $userId === getCurrentUser()['id']) {
                $error = 'Du kannst deinen eigenen Account nicht löschen.';
            } else {
                try {
                    // Prüfen ob Benutzer Kontrollen durchgeführt hat
                    $hasInspections = $db->fetchColumn(
                        "SELECT COUNT(*) FROM inspections WHERE user_id = ?",
                        [$userId]
                    );
                    
                    if ($hasInspections > 0) {
                        // Benutzer nur deaktivieren
                        $db->query("UPDATE users SET active = 0 WHERE id = ?", [$userId]);
                        $successMessage = 'Benutzer wurde deaktiviert (Kontrollen vorhanden).';
                    } else {
                        // Benutzer löschen
                        $db->query("DELETE FROM users WHERE id = ?", [$userId]);
                        $successMessage = 'Benutzer wurde gelöscht.';
                    }
                    
                    header('Location: ?page=users&success=user_deleted&message=' . urlencode($successMessage));
                    exit;
                } catch (Exception $e) {
                    $error = 'Fehler beim Löschen des Benutzers.';
                }
            }
            break;
    }
}

// Benutzer laden
$users = $db->fetchAll("
    SELECT u.*, 
           COUNT(i.id) as inspection_count,
           MAX(i.completed_at) as last_inspection
    FROM users u
    LEFT JOIN inspections i ON u.id = i.user_id AND i.status = 'completed'
    GROUP BY u.id
    ORDER BY u.full_name
");

$editUserId = $_GET['edit'] ?? null;
$editUser = null;
if ($editUserId) {
    $editUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$editUserId]);
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Benutzerverwaltung</h1>
        <p>Benutzerkonten und Berechtigungen verwalten</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        <?php
        $messages = [
            'user_added' => 'Benutzer erfolgreich hinzugefügt',
            'user_updated' => 'Benutzer erfolgreich aktualisiert',
            'user_deleted' => $_GET['message'] ?? 'Benutzer erfolgreich gelöscht'
        ];
        echo $messages[$_GET['success']] ?? 'Aktion erfolgreich';
        ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="error-message">
        <?= h($error) ?>
    </div>
    <?php endif; ?>
    
    <div class="users-section">
        <div class="section-header">
            <h2>Benutzer</h2>
            <button class="btn btn-primary" onclick="showAddUserModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
                Benutzer hinzufügen
            </button>
        </div>
        
        <div class="users-grid">
            <?php foreach ($users as $user): ?>
            <div class="user-card <?= !$user['active'] ? 'inactive' : '' ?>">
                <div class="user-header">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                    </div>
                    <div class="user-info">
                        <h3><?= h($user['full_name']) ?></h3>
                        <p class="username">@<?= h($user['username']) ?></p>
                        <span class="role-badge role-<?= $user['role'] ?>">
                            <?php
                            $roleNames = [
                                'admin' => 'Administrator',
                                'fahrzeugwart' => 'Fahrzeugwart',
                                'kontrolle' => 'Kontrolle',
                                'user' => 'Benutzer'
                            ];
                            echo $roleNames[$user['role']] ?? 'Benutzer';
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="user-details">
                    <?php if ($user['email']): ?>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <?= h($user['email']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                        <?= $user['inspection_count'] ?> Kontrollen
                    </div>
                    
                    <?php if ($user['last_inspection']): ?>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        Letzte Kontrolle: <?= timeAgo($user['last_inspection']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($user['last_login']): ?>
                    <div class="detail-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-5-4l5-5-5-5m5 5H3"/>
                        </svg>
                        Letzter Login: <?= timeAgo($user['last_login']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-actions">
                    <a href="?page=users&edit=<?= $user['id'] ?>" class="btn btn-outline btn-small">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Bearbeiten
                    </a>
                    
                    <?php if ($user['id'] !== getCurrentUser()['id']): ?>
                    <button class="btn btn-danger btn-small" onclick="deleteUser(<?= $user['id'] ?>, '<?= h($user['full_name']) ?>')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"/>
                            <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
                        </svg>
                        <?= $user['active'] ? 'Löschen' : 'Gelöscht' ?>
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (!$user['active']): ?>
                <div class="inactive-overlay">
                    <span>Inaktiv</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <h3>Benutzer hinzufügen</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label for="username" class="form-label">Benutzername *</label>
                <input type="text" id="username" name="username" class="form-input" required>
                <div class="form-help">Nur Buchstaben, Zahlen und Unterstriche erlaubt</div>
            </div>
            
            <div class="form-group">
                <label for="full_name" class="form-label">Vollständiger Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">E-Mail-Adresse</label>
                <input type="email" id="email" name="email" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Passwort *</label>
                <input type="password" id="password" name="password" class="form-input" required>
                <div class="form-help">Mindestens 10 Zeichen, mit Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen</div>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Rolle *</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="user">Benutzer</option>
                    <option value="fahrzeugwart">Fahrzeugwart</option>
                    <option value="kontrolle">Kontrolle</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<?php if ($editUser): ?>
<div class="modal show" id="editUserModal">
    <div class="modal-content">
        <h3>Benutzer bearbeiten</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            
            <div class="form-group">
                <label for="edit_username" class="form-label">Benutzername *</label>
                <input type="text" id="edit_username" name="username" class="form-input" 
                       value="<?= h($editUser['username']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="edit_full_name" class="form-label">Vollständiger Name *</label>
                <input type="text" id="edit_full_name" name="full_name" class="form-input" 
                       value="<?= h($editUser['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="edit_email" class="form-label">E-Mail-Adresse</label>
                <input type="email" id="edit_email" name="email" class="form-input" 
                       value="<?= h($editUser['email']) ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_new_password" class="form-label">Neues Passwort</label>
                <input type="password" id="edit_new_password" name="new_password" class="form-input">
                <div class="form-help">Leer lassen um Passwort nicht zu ändern</div>
            </div>
            
            <div class="form-group">
                <label for="edit_role" class="form-label">Rolle *</label>
                <select id="edit_role" name="role" class="form-select" required>
                    <option value="user" <?= $editUser['role'] === 'user' ? 'selected' : '' ?>>Benutzer</option>
                    <option value="fahrzeugwart" <?= $editUser['role'] === 'fahrzeugwart' ? 'selected' : '' ?>>Fahrzeugwart</option>
                    <option value="kontrolle" <?= $editUser['role'] === 'kontrolle' ? 'selected' : '' ?>>Kontrolle</option>
                    <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="active" <?= $editUser['active'] ? 'checked' : '' ?>>
                    Benutzer ist aktiv
                </label>
            </div>
            
            <div class="modal-actions">
                <a href="?page=users" class="btn btn-secondary">Abbrechen</a>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Delete User Form -->
<form id="deleteUserForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<style>
.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.user-card {
    background: var(--primary-white);
    border-radius: 12px;
    box-shadow: var(--shadow-light);
    overflow: hidden;
    transition: var(--transition-fast);
    position: relative;
}

.user-card:hover {
    box-shadow: var(--shadow-medium);
}

.user-card.inactive {
    opacity: 0.6;
}

.user-header {
    padding: 20px;
    background: linear-gradient(135deg, var(--light-gray), #e9ecef);
    display: flex;
    align-items: center;
    gap: 16px;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary-red);
    color: var(--primary-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
}

.user-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.username {
    font-size: 14px;
    color: var(--dark-gray);
    margin-bottom: 8px;
}

.role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.role-admin {
    background: rgba(220, 20, 60, 0.1);
    color: var(--primary-red);
}

.role-user {
    background: rgba(108, 117, 125, 0.1);
    color: var(--dark-gray);
}

.user-details {
    padding: 20px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 14px;
    color: var(--dark-gray);
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.user-actions {
    padding: 16px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 8px;
}

.inactive-overlay {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--danger-red);
    color: var(--primary-white);
    padding: 4px 12px;
    border-bottom-left-radius: 8px;
    font-size: 12px;
    font-weight: 500;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

@media (max-width: 768px) {
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .user-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showAddUserModal() {
    $('#addUserModal').addClass('show');
}

function deleteUser(userId, userName) {
    App.showConfirmModal(
        'Benutzer löschen',
        `Möchtest du den Benutzer "${userName}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.`,
        function() {
            $('#deleteUserId').val(userId);
            $('#deleteUserForm').submit();
        }
    );
}

$(document).ready(function() {
    // Passwort-Validierung für neuen Benutzer
    $('#password').on('input', function() {
        App.validatePassword($(this));
    });
    
    // Passwort-Validierung für Bearbeitung
    $('#edit_new_password').on('input', function() {
        if ($(this).val()) {
            App.validatePassword($(this));
        }
    });
});
</script>