<?php
/**
 * Container-Prüfung Start-Seite
 * Zeigt alle Fahrzeuge mit Container-Prüfungs-Status
 */

$db = Database::getInstance();
$user = getCurrentUser();
$userRole = $user['role'];
$canStartInspection = in_array($userRole, ['admin', 'fahrzeugwart']);

// Alle Fahrzeuge laden
$vehicles = $db->query("SELECT * FROM vehicles WHERE active = 1 ORDER BY name ASC")->fetchAll();

// Laufende Container-Inspections laden
$activeSessions = [];
$sessionsResult = $db->query("
    SELECT 
        ci.id,
        ci.vehicle_id,
        ci.started_at,
        u.full_name as starter_name,
        COUNT(cii.id) as total_items,
        SUM(CASE WHEN cii.status = 'completed' THEN 1 ELSE 0 END) as completed_items
    FROM container_inspections ci
    LEFT JOIN users u ON ci.started_by = u.id
    LEFT JOIN container_inspection_items cii ON ci.id = cii.container_inspection_id
    WHERE ci.status = 'in_progress'
    GROUP BY ci.id
");

foreach ($sessionsResult->fetchAll() as $session) {
    $activeSessions[$session['vehicle_id']] = $session;
}

// POST: Neue Container-Prüfung starten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_id']) && $canStartInspection) {
    $vehicleId = (int)$_POST['vehicle_id'];
    
    try {
        // Direkt in PHP erstellen (ohne API Call)
        
        // Prüfe ob bereits in_progress Session existiert
        $existingSession = $db->fetchOne(
            "SELECT id FROM container_inspections WHERE vehicle_id = ? AND status = 'in_progress'",
            [$vehicleId]
        );
        
        if ($existingSession) {
            // Redirect zu bestehender Session
            header("Location: ?page=container_inspection_overview&session_id=" . $existingSession['id']);
            exit;
        }
        
        // Neue Session erstellen
        $db->query(
            "INSERT INTO container_inspections (vehicle_id, started_by, started_at, status) 
             VALUES (?, ?, NOW(), 'in_progress')",
            [$vehicleId, $user['id']]
        );
        $sessionId = $db->lastInsertId();
        
        // Alle Container des Fahrzeugs als Items hinzufügen
        $containers = $db->query(
            "SELECT id FROM containers WHERE vehicle_id = ? AND active = 1 ORDER BY sort_order, name",
            [$vehicleId]
        )->fetchAll();
        
        foreach ($containers as $container) {
            $db->query(
                "INSERT INTO container_inspection_items (container_inspection_id, container_id, status) 
                 VALUES (?, ?, 'pending')",
                [$sessionId, $container['id']]
            );
        }
        
        // Redirect zu Overview
        header("Location: ?page=container_inspection_overview&session_id=" . $sessionId);
        exit;
        
    } catch (Exception $e) {
        $error = "Fehler beim Starten der Prüfung: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>🗂️ Container-Prüfung starten</h1>
        <p>Wählen Sie ein Fahrzeug aus, um eine neue Container-Prüfung zu starten oder eine laufende Prüfung fortzusetzen.</p>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <div class="vehicles-grid">
        <?php foreach ($vehicles as $vehicle): ?>
        <?php
        $hasActiveSession = isset($activeSessions[$vehicle['id']]);
        $session = $hasActiveSession ? $activeSessions[$vehicle['id']] : null;
        ?>
        <div class="card vehicle-card">
            <div class="card-header">
                <h3><?= htmlspecialchars($vehicle['name']) ?></h3>
                <span class="badge badge-secondary"><?= htmlspecialchars($vehicle['type']) ?></span>
            </div>
            <div class="card-body">
                <?php if ($hasActiveSession): ?>
                    <div class="inspection-status running">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        <div class="status-details">
                            <strong>Prüfung läuft</strong>
                            <p class="text-muted mb-0">
                                Gestartet von <?= htmlspecialchars($session['starter_name']) ?><br>
                                am <?= date('d.m.Y H:i', strtotime($session['started_at'])) ?> Uhr
                            </p>
                            <div class="progress-info mt-2">
                                <span class="badge badge-info">
                                    <?= $session['completed_items'] ?> / <?= $session['total_items'] ?> Container geprüft
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="inspection-status idle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; color: var(--gray-400);">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <div class="status-details">
                            <strong>Keine laufende Prüfung</strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <?php if ($hasActiveSession): ?>
                    <a href="?page=container_inspection_overview&session_id=<?= $session['id'] ?>" class="btn btn-primary btn-block">
                        Zur Prüfung
                    </a>
                <?php else: ?>
                    <?php if ($canStartInspection): ?>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                            <button type="submit" class="btn btn-success btn-block">
                                Prüfung starten
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-block" disabled>
                            Keine Berechtigung
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.vehicles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.vehicle-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--border-color);
}

.vehicle-card .card-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.inspection-status {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
}

.inspection-status.running {
    background: #e3f2fd;
    border: 1px solid #2196f3;
}

.inspection-status.idle {
    background: var(--gray-50);
    border: 1px solid var(--border-color);
}

.status-details {
    flex: 1;
}

.status-details strong {
    display: block;
    margin-bottom: 0.5rem;
}

.progress-info {
    margin-top: 0.5rem;
}

.inline-form {
    width: 100%;
}

.btn-block {
    display: block;
    width: 100%;
}

@media (max-width: 768px) {
    .vehicles-grid {
        grid-template-columns: 1fr;
    }
}
</style>
