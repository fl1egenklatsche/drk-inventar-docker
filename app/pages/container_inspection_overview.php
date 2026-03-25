<?php
/**
 * Container-Prüfung Übersicht
 * Zeigt alle Container einer laufenden Prüfungs-Session
 */

if (!isset($_GET['session_id']) || !is_numeric($_GET['session_id'])) {
    header('Location: ?page=container_inspection_start');
    exit;
}

$sessionId = (int)$_GET['session_id'];
$db = Database::getInstance();
$currentUser = getCurrentUser();
$currentUserId = $currentUser['id'];

// POST: Container-Prüfung starten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_item'])) {
    $itemId = (int)$_POST['item_id'];
    
    try {
        // Item auf in_progress setzen
        $db->query(
            "UPDATE container_inspection_items 
             SET inspected_by = ?, started_at = NOW(), status = 'in_progress'
             WHERE id = ? AND status = 'pending'",
            [$currentUserId, $itemId]
        );
        
        // Redirect zu Check-Seite
        header("Location: ?page=container_inspection_check&item_id=" . $itemId);
        exit;
        
    } catch (Exception $e) {
        $error = "Fehler beim Starten: " . $e->getMessage();
    }
}

// Session-Daten laden
$session = $db->fetchOne("
    SELECT 
        ci.*,
        v.name as vehicle_name,
        v.type as vehicle_type,
        u.full_name as starter_name
    FROM container_inspections ci
    LEFT JOIN vehicles v ON ci.vehicle_id = v.id
    LEFT JOIN users u ON ci.started_by = u.id
    WHERE ci.id = ?
", [$sessionId]);

if (!$session) {
    die("Session nicht gefunden.");
}

if ($session['status'] === 'completed') {
    header('Location: ?page=container_inspection_start');
    exit;
}

// Container-Items laden
$result = $db->query("
    SELECT 
        cii.*,
        c.name as container_name,
        c.type as container_type
    FROM container_inspection_items cii
    LEFT JOIN containers c ON cii.container_id = c.id
    WHERE cii.container_inspection_id = ?
    ORDER BY c.sort_order, c.name ASC
", [$sessionId]);

$items = $result->fetchAll();

// Fortschritt berechnen
$totalItems = count($items);
$completedItems = array_reduce($items, function($carry, $item) {
    return $carry + ($item['status'] === 'completed' ? 1 : 0);
}, 0);

$canComplete = ($currentUser['role'] === 'admin' || $currentUser['role'] === 'fahrzeugwart');
$allCompleted = ($completedItems === $totalItems);
?>

<div class="container-fluid">
    <div class="page-header">
        <a href="?page=container_inspection_start" class="btn btn-outline btn-sm mb-2">
            ← Zurück zur Übersicht
        </a>
        <h1>🗂️ Container-Prüfung: <?= htmlspecialchars($session['vehicle_name']) ?></h1>
        <p class="text-muted">
            <?= htmlspecialchars($session['vehicle_type']) ?> · 
            Gestartet von <?= htmlspecialchars($session['starter_name']) ?> am 
            <?= date('d.m.Y H:i', strtotime($session['started_at'])) ?> Uhr
        </p>
    </div>
    
    <div class="progress-banner">
        <div class="progress-stats">
            <h3>Fortschritt</h3>
            <div class="progress-bar-wrapper">
                <div class="progress-bar" style="width: <?= $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0 ?>%"></div>
            </div>
            <p class="progress-text">
                <strong><?= $completedItems ?> von <?= $totalItems ?> Containern geprüft</strong>
                <?php if ($allCompleted): ?>
                    <span class="badge badge-success ml-2">✓ Vollständig</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <div class="containers-grid" id="containers-grid">
        <?php foreach ($items as $item): ?>
        <div class="card container-card status-<?= $item['status'] ?>" data-item-id="<?= $item['id'] ?>">
            <div class="card-header">
                <h3>🗂️ <?= htmlspecialchars($item['container_name']) ?></h3>
                <span class="status-badge badge-<?= $item['status'] ?>">
                    <?php if ($item['status'] === 'pending'): ?>
                        ⬜ Offen
                    <?php elseif ($item['status'] === 'in_progress'): ?>
                        🔄 In Prüfung
                    <?php else: ?>
                        ✅ Geprüft
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($item['status'] === 'pending'): ?>
                    <p class="text-muted">Noch nicht begonnen</p>
                <?php elseif ($item['status'] === 'in_progress'): ?>
                    <p>
                        <strong>In Prüfung von:</strong><br>
                        User #<?= $item['inspected_by'] ?>
                        <?php if ($item['inspected_by'] == $currentUserId): ?>
                            <span class="badge badge-info">Sie</span>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p>
                        <strong>Geprüft von:</strong><br>
                        <?= htmlspecialchars($item['inspector_name']) ?><br>
                        <span class="text-muted">
                            am <?= date('d.m.Y H:i', strtotime($item['completed_at'])) ?> Uhr
                        </span>
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <?php if ($item['status'] === 'pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="start_item" value="1">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-block">
                            Container prüfen
                        </button>
                    </form>
                <?php elseif ($item['status'] === 'in_progress' && $item['inspected_by'] == $currentUserId): ?>
                    <a href="?page=container_inspection_check&item_id=<?= $item['id'] ?>" class="btn btn-warning btn-block">
                        Weiter prüfen
                    </a>
                <?php elseif ($item['status'] === 'in_progress'): ?>
                    <button type="button" class="btn btn-secondary btn-block" disabled>
                        Wird von anderem User geprüft
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-success btn-block" disabled>
                        ✓ Abgeschlossen
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($canComplete && $allCompleted): ?>
    <div class="complete-section">
        <div class="card">
            <div class="card-body text-center">
                <h3>🎉 Alle Container geprüft!</h3>
                <p>Sie können die Gesamt-Prüfung jetzt abschließen.</p>
                <button type="button" id="completeSessionBtn" class="btn btn-success btn-lg">
                    Gesamt-Prüfung abschließen
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.progress-banner {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.progress-stats h3 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.progress-bar-wrapper {
    height: 30px;
    background: var(--gray-200);
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #4caf50, #66bb6a);
    transition: width 0.3s ease;
}

.progress-text {
    margin: 0;
    font-size: 1.1rem;
}

.containers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.container-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.container-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.container-card.status-pending {
    border-left: 4px solid var(--gray-400);
}

.container-card.status-in_progress {
    border-left: 4px solid #ff9800;
}

.container-card.status-completed {
    border-left: 4px solid #4caf50;
}

.container-card .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--border-color);
}

.container-card .card-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-pending {
    background: var(--gray-200);
    color: var(--gray-700);
}

.badge-in_progress {
    background: #fff3e0;
    color: #f57c00;
}

.badge-completed {
    background: #e8f5e9;
    color: #2e7d32;
}

.complete-section {
    margin-top: 2rem;
}

.complete-section .card {
    background: #e8f5e9;
    border: 2px solid #4caf50;
}

@media (max-width: 768px) {
    .containers-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Auto-Reload deaktiviert während Tests
// let reloadInterval = setInterval(() => {
//     location.reload();
// }, 10000);

// Complete Session
const completeBtn = document.getElementById('completeSessionBtn');
if (completeBtn) {
    completeBtn.addEventListener('click', async () => {
        if (!confirm('Gesamt-Prüfung wirklich abschließen?\n\nDie Prüfung kann danach nicht mehr fortgesetzt werden.')) {
            return;
        }
        
        try {
            const res = await fetch('/api/container_inspections.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CSRF_TOKEN
                },
                body: JSON.stringify({
                    action: 'complete',
                    session_id: <?= $sessionId ?>
                })
            });
            
            const data = await res.json();
            
            if (data.success) {
                alert('✓ Prüfung erfolgreich abgeschlossen!');
                window.location.href = '?page=container_inspection_start';
            } else {
                alert('Fehler: ' + (data.error || 'Unbekannt'));
            }
        } catch (err) {
            alert('Netzwerkfehler: ' + err);
        }
    });
}
</script>
