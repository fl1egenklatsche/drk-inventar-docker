<?php
/**
 * pages/settings.php
 * Systemeinstellungen (nur für Admins)
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
        case 'update_email_settings':
            $emailNotifications = trim($_POST['email_notifications'] ?? '');
            $notificationWeeksAhead = (int)($_POST['notification_weeks_ahead'] ?? 12);
            $dashboardWeeksAhead = (int)($_POST['dashboard_weeks_ahead'] ?? 4);
            
            if (!empty($emailNotifications) && !filter_var($emailNotifications, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ungültige E-Mail-Adresse.';
            } else {
                setSetting('email_notifications', $emailNotifications);
                setSetting('notification_weeks_ahead', $notificationWeeksAhead);
                setSetting('dashboard_weeks_ahead', $dashboardWeeksAhead);
                
                header('Location: ?page=settings&success=email_updated');
                exit;
            }
            break;
            
        case 'update_smtp_settings':
            $smtpHost = trim($_POST['smtp_host'] ?? '');
            $smtpPort = (int)($_POST['smtp_port'] ?? 587);
            $smtpUsername = trim($_POST['smtp_username'] ?? '');
            $smtpPassword = $_POST['smtp_password'] ?? '';
            
            setSetting('smtp_host', $smtpHost);
            setSetting('smtp_port', $smtpPort);
            setSetting('smtp_username', $smtpUsername);
            
            if (!empty($smtpPassword)) {
                setSetting('smtp_password', base64_encode($smtpPassword)); // Einfache Verschleierung
            }
            
            header('Location: ?page=settings&success=smtp_updated');
            exit;
            break;
            
        case 'test_email':
            $testEmail = trim($_POST['test_email'] ?? '');
            
            if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $error = 'Bitte eine gültige E-Mail-Adresse eingeben.';
            } else {
                $subject = 'Test E-Mail - DRK Medizinprodukt-Verwaltung';
                $message = '
                <html>
                <body>
                    <h2>Test E-Mail</h2>
                    <p>Dies ist eine Test-E-Mail vom Medizinprodukt-Verwaltungssystem.</p>
                    <p><strong>Gesendet am:</strong> ' . date('d.m.Y H:i:s') . '</p>
                    <p><strong>Von:</strong> ' . getCurrentUser()['full_name'] . '</p>
                    <hr>
                    <p><em>DRK Stadtverband Haltern am See e.V.</em></p>
                </body>
                </html>';
                
                if (sendEmail($testEmail, $subject, $message, true)) {
                    $successMessage = 'Test-E-Mail erfolgreich an ' . $testEmail . ' gesendet.';
                } else {
                    $error = 'Fehler beim Senden der Test-E-Mail. Bitte SMTP-Einstellungen prüfen.';
                }
            }
            break;
            
        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $currentUser = getCurrentUser();
            $userRecord = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$currentUser['id']]);
            
            if (!password_verify($currentPassword, $userRecord['password_hash'])) {
                $error = 'Aktuelles Passwort ist falsch.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Neue Passwörter stimmen nicht überein.';
            } else {
                $passwordValidation = validatePassword($newPassword);
                if ($passwordValidation !== true) {
                    $error = $passwordValidation;
                } else {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newPasswordHash, $currentUser['id']]);
                    
                    header('Location: ?page=settings&success=password_changed');
                    exit;
                }
            }
            break;
            
        case 'clear_old_data':
            $monthsOld = (int)($_POST['months_old'] ?? 12);
            
            if ($monthsOld < 6) {
                $error = 'Daten können nur ab 6 Monaten gelöscht werden.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    $cutoffDate = date('Y-m-d', strtotime("-$monthsOld months"));
                    
                    // Alte Kontroll-Details löschen
                    $deletedItems = $db->query("
                        DELETE ii FROM inspection_items ii
                        JOIN inspections i ON ii.inspection_id = i.id
                        WHERE i.completed_at < ?
                    ", [$cutoffDate])->rowCount();
                    
                    // Alte Kontrollen löschen
                    $deletedInspections = $db->query("
                        DELETE FROM inspections WHERE completed_at < ?
                    ", [$cutoffDate])->rowCount();
                    
                    $db->commit();
                    
                    $successMessage = "Alte Daten gelöscht: $deletedInspections Kontrollen, $deletedItems Einzelprüfungen.";
                    
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Fehler beim Löschen der alten Daten.';
                }
            }
            break;
    }
}

// Aktuelle Einstellungen laden
$settings = [
    'email_notifications' => getSetting('email_notifications', ''),
    'notification_weeks_ahead' => getSetting('notification_weeks_ahead', 12),
    'dashboard_weeks_ahead' => getSetting('dashboard_weeks_ahead', 4),
    'smtp_host' => getSetting('smtp_host', ''),
    'smtp_port' => getSetting('smtp_port', 587),
    'smtp_username' => getSetting('smtp_username', ''),
    'smtp_password' => getSetting('smtp_password', '') ? '••••••••' : ''
];

// Statistiken
$stats = [
    'total_users' => $db->fetchColumn("SELECT COUNT(*) FROM users WHERE active = 1"),
    'total_vehicles' => $db->fetchColumn("SELECT COUNT(*) FROM vehicles WHERE active = 1"),
    'total_products' => $db->fetchColumn("SELECT COUNT(*) FROM compartment_products_actual"),
    'total_inspections' => $db->fetchColumn("SELECT COUNT(*) FROM inspections WHERE status = 'completed'"),
    'database_size' => $db->fetchColumn("
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB'
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ")
];
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Einstellungen</h1>
        <p>Systemkonfiguration und Verwaltung</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        <?php
        $messages = [
            'email_updated' => 'E-Mail-Einstellungen erfolgreich aktualisiert',
            'smtp_updated' => 'SMTP-Einstellungen erfolgreich aktualisiert',
            'password_changed' => 'Passwort erfolgreich geändert'
        ];
        echo $messages[$_GET['success']] ?? 'Einstellungen erfolgreich gespeichert';
        ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="error-message">
        <?= h($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($successMessage)): ?>
    <div class="success-message">
        <?= h($successMessage) ?>
    </div>
    <?php endif; ?>
    
    <!-- Settings Tabs -->
    <div class="settings-tabs">
        <button class="tab-btn active" onclick="showTab('general')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="m12 1l3.09 3.09L22 4l-.91 6.91L22 17l-6.91-.09L12 23l-3.09-3.09L2 20l.91-6.91L2 7l6.91.09L12 1z"/>
            </svg>
            Allgemein
        </button>
        <button class="tab-btn" onclick="showTab('email')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            E-Mail
        </button>
        <button class="tab-btn" onclick="showTab('security')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <circle cx="12" cy="16" r="1"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            Sicherheit
        </button>
        <button class="tab-btn" onclick="showTab('maintenance')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
            Wartung
        </button>
    </div>
    
    <!-- General Tab -->
    <div class="settings-tab-content" id="general-tab">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Systemübersicht</h2>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_users']) ?></h3>
                            <p>Aktive Benutzer</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                                <path d="M5 17h-2v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_vehicles']) ?></h3>
                            <p>Fahrzeuge</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_products']) ?></h3>
                            <p>Produkte im System</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <h3><?= number_format($stats['total_inspections']) ?></h3>
                            <p>Durchgeführte Kontrollen</p>
                        </div>
                    </div>
                </div>
                
                <div class="system-info">
                    <h3>Systeminformationen</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Version:</span>
                            <span class="info-value"><?= APP_VERSION ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?= phpversion() ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Datenbankgröße:</span>
                            <span class="info-value"><?= $stats['database_size'] ?> MB</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Installiert:</span>
                            <span class="info-value"><?= date('d.m.Y H:i') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- E-Mail Tab -->
    <div class="settings-tab-content" id="email-tab" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">E-Mail-Benachrichtigungen</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_email_settings">
                    
                    <div class="form-group">
                        <label for="email_notifications" class="form-label">Benachrichtigungs-E-Mail</label>
                        <input type="email" id="email_notifications" name="email_notifications" 
                               class="form-input" value="<?= h($settings['email_notifications']) ?>">
                        <div class="form-help">E-Mail-Adresse für automatische Benachrichtigungen</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="notification_weeks_ahead" class="form-label">Vorlaufzeit E-Mail (Wochen)</label>
                                <input type="number" id="notification_weeks_ahead" name="notification_weeks_ahead" 
                                       class="form-input" value="<?= $settings['notification_weeks_ahead'] ?>" min="1" max="52">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="dashboard_weeks_ahead" class="form-label">Dashboard-Anzeige (Wochen)</label>
                                <input type="number" id="dashboard_weeks_ahead" name="dashboard_weeks_ahead" 
                                       class="form-input" value="<?= $settings['dashboard_weeks_ahead'] ?>" min="1" max="12">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">E-Mail-Einstellungen speichern</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">SMTP-Konfiguration</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_smtp_settings">
                    
                    <div class="form-group">
                        <label for="smtp_host" class="form-label">SMTP Server</label>
                        <input type="text" id="smtp_host" name="smtp_host" 
                               class="form-input" value="<?= h($settings['smtp_host']) ?>" 
                               placeholder="mail.example.com">
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="smtp_port" class="form-label">Port</label>
                                <input type="number" id="smtp_port" name="smtp_port" 
                                       class="form-input" value="<?= $settings['smtp_port'] ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="smtp_username" class="form-label">Benutzername</label>
                                <input type="text" id="smtp_username" name="smtp_username" 
                                       class="form-input" value="<?= h($settings['smtp_username']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="smtp_password" class="form-label">Passwort</label>
                        <input type="password" id="smtp_password" name="smtp_password" 
                               class="form-input" placeholder="Leer lassen um nicht zu ändern">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">SMTP-Einstellungen speichern</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">E-Mail testen</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="test_email">
                    
                    <div class="form-group">
                        <label for="test_email" class="form-label">Test-E-Mail senden an</label>
                        <input type="email" id="test_email" name="test_email" 
                               class="form-input" value="<?= h($settings['email_notifications']) ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-outline">Test-E-Mail senden</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Security Tab -->
    <div class="settings-tab-content" id="security-tab" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Passwort ändern</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password" class="form-label">Aktuelles Passwort</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Neues Passwort</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="form-input" required>
                        <div class="form-help">Mindestens 10 Zeichen mit Groß-/Kleinbuchstaben, Zahlen und Sonderzeichen</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-input" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Passwort ändern</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Sicherheitsinformationen</h2>
            </div>
            <div class="card-body">
                <div class="security-info">
                    <div class="security-item">
                        <div class="security-icon success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                        </div>
                        <div class="security-content">
                            <h4>HTTPS verschlüsselt</h4>
                            <p>Alle Daten werden verschlüsselt übertragen</p>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <circle cx="12" cy="16" r="1"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <div class="security-content">
                            <h4>Sichere Passwort-Hashes</h4>
                            <p>Passwörter werden mit bcrypt verschlüsselt</p>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"/>
                                <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                            </svg>
                        </div>
                        <div class="security-content">
                            <h4>SQL-Injection Schutz</h4>
                            <p>Prepared Statements verhindern Datenbankeingriffe</p>
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div class="security-content">
                            <h4>Session-Sicherheit</h4>
                            <p>Automatische Abmeldung nach 8 Stunden Inaktivität</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Tab -->
    <div class="settings-tab-content" id="maintenance-tab" style="display: none;">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Datenbereinigung</h2>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirmDataDeletion()">
                    <input type="hidden" name="action" value="clear_old_data">
                    
                    <div class="form-group">
                        <label for="months_old" class="form-label">Daten löschen älter als (Monate)</label>
                        <select id="months_old" name="months_old" class="form-select">
                            <option value="6">6 Monate</option>
                            <option value="12" selected>12 Monate</option>
                            <option value="24">24 Monate</option>
                            <option value="36">36 Monate</option>
                        </select>
                        <div class="form-help">Alte Kontrolldaten werden unwiderruflich gelöscht</div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">Alte Daten löschen</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Backup & Export</h2>
            </div>
            <div class="card-body">
                <div class="backup-actions">
                    <button class="btn btn-outline" onclick="exportDatabase()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7,10 12,15 17,10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Datenbank exportieren
                    </button>
                    
                    <button class="btn btn-outline" onclick="exportSettings()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="m12 1l3.09 3.09L22 4l-.91 6.91L22 17l-6.91-.09L12 23l-3.09-3.09L2 20l.91-6.91L2 7l6.91.09L12 1z"/>
                        </svg>
                        Einstellungen exportieren
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">System-Logs</h2>
            </div>
            <div class="card-body">
                <div class="log-info">
                    <p>System-Logs werden automatisch rotiert und nach 30 Tagen gelöscht.</p>
                    <button class="btn btn-outline btn-small" onclick="viewLogs()">
                        Logs anzeigen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.settings-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--border-color);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--dark-gray);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
    white-space: nowrap;
    min-height: var(--touch-target);
}

.tab-btn.active,
.tab-btn:hover {
    color: var(--primary-red);
    border-bottom-color: var(--primary-red);
}

.tab-btn svg {
    width: 18px;
    height: 18px;
}

.settings-tab-content {
    display: none;
}

.settings-tab-content.active {
    display: block;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: linear-gradient(135deg, var(--light-gray), #e9ecef);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: var(--transition-fast);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-red);
    color: var(--primary-white);
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon svg {
    width: 24px;
    height: 24px;
}

.stat-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.stat-content p {
    font-size: 14px;
    color: var(--dark-gray);
    margin: 0;
}

.system-info {
    margin-top: 32px;
}

.system-info h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 16px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: var(--light-gray);
    border-radius: 8px;
}

.info-label {
    font-weight: 500;
    color: var(--primary-black);
}

.info-value {
    color: var(--dark-gray);
    font-family: monospace;
}

.security-info {
    display: grid;
    gap: 16px;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--light-gray);
    border-radius: 8px;
}

.security-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.security-icon.success {
    background: rgba(40, 167, 69, 0.1);
    color: var(--success-green);
}

.security-icon svg {
    width: 20px;
    height: 20px;
}

.security-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.security-content p {
    font-size: 14px;
    color: var(--dark-gray);
    margin: 0;
}

.backup-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.log-info {
    text-align: center;
    padding: 20px;
}

.log-info p {
    color: var(--dark-gray);
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .settings-tabs {
        gap: 0;
    }
    
    .tab-btn {
        flex: 1;
        justify-content: center;
        padding: 12px 8px;
        font-size: 14px;
    }
    
    .tab-btn span {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .backup-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showTab(tabName) {
    // Hide all tabs
    $('.settings-tab-content').hide();
    $('.tab-btn').removeClass('active');
    
    // Show selected tab
    $(`#${tabName}-tab`).show();
    $(`button[onclick="showTab('${tabName}')"]`).addClass('active');
}

function confirmDataDeletion() {
    const months = $('#months_old').val();
    return confirm(`Möchtest du wirklich alle Kontrolldaten löschen, die älter als ${months} Monate sind? Diese Aktion kann nicht rückgängig gemacht werden.`);
}

function exportDatabase() {
    App.showToast('Database-Export wird vorbereitet...', 'info');
    window.open('api/export-database.php');
}

function exportSettings() {
    App.showToast('Einstellungs-Export wird vorbereitet...', 'info');
    window.open('api/export-settings.php');
}

function viewLogs() {
    App.showToast('Log-Viewer wird geladen...', 'info');
    window.open('api/view-logs.php');
}

$(document).ready(function() {
    // Show first tab by default
    showTab('general');
    
    // Password validation
    $('#new_password').on('input', function() {
        App.validatePassword($(this));
    });
    
    // Password confirmation
    $('#confirm_password').on('input', function() {
        const newPassword = $('#new_password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword && newPassword !== confirmPassword) {
            App.showFieldValidation($(this), false, 'Passwörter stimmen nicht überein');
        } else if (confirmPassword) {
            App.showFieldValidation($(this), true, '');
        }
    });
});
</script>