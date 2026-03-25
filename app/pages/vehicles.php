<?php
/**
 * pages/vehicles.php
 * Fahrzeugübersicht
 */

$vehicles = getVehicles();
$lastInspections = getLastInspections();

// Array für schnelleren Zugriff erstellen
$inspectionData = [];
foreach ($lastInspections as $inspection) {
    $inspectionData[$inspection['vehicle_id']] = $inspection;
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Fahrzeuge</h1>
        <p>Wähle ein Fahrzeug für die Kontrolle aus</p>
    </div>
    
    <div class="vehicles-grid">
        <?php foreach ($vehicles as $vehicle): ?>
        <?php 
        $inspection = $inspectionData[$vehicle['id']] ?? null;
        $lastCheck = $inspection ? $inspection['completed_at'] : null;
        $inspector = $inspection ? $inspection['inspector_name'] : null;
        
        // Warnung bei länger als 7 Tage
        $daysSinceCheck = null;
        $needsCheck = false;
        if ($lastCheck) {
            $daysSinceCheck = floor((time() - strtotime($lastCheck)) / (60 * 60 * 24));
            $needsCheck = $daysSinceCheck > 7;
        } else {
            $needsCheck = true;
        }
        ?>
        
        <div class="vehicle-card <?= $needsCheck ? 'needs-check' : '' ?>" 
             onclick="location.href='?page=inspection&vehicle_id=<?= $vehicle['id'] ?>'">
            <div class="vehicle-header">
                <h3 class="vehicle-title"><?= h($vehicle['name']) ?></h3>
                <span class="vehicle-type"><?= h($vehicle['type']) ?></span>
            </div>
            
            <div class="vehicle-info">
                <?php if ($lastCheck): ?>
                <div class="last-inspection">
                    <div class="inspection-meta">
                        <span class="inspection-date">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            <?= timeAgo($lastCheck) ?>
                        </span>
                        <span class="inspector-name">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <?= h($inspector) ?>
                        </span>
                    </div>
                    
                    <?php if ($needsCheck): ?>
                    <div class="check-warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px; margin-right: 4px;">
                            <path d="m21.73 18l-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Kontrolle überfällig
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="no-inspection">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    Noch nie kontrolliert
                </div>
                <?php endif; ?>
                
                <div class="vehicle-actions">
                    <button class="btn btn-primary btn-large btn-block start-inspection" 
                            onclick="event.stopPropagation(); startInspection(<?= $vehicle['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                        Kontrolle starten
                    </button>
                    
                    <?php if (isAdmin()): ?>
                    <button class="btn btn-outline btn-small" 
                            onclick="event.stopPropagation(); editVehicle(<?= $vehicle['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="m18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Bearbeiten
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="floating-action">
        <button class="btn btn-primary btn-large fab" onclick="addVehicle()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>
</div>


<script>
function startInspection(vehicleId) {
    window.location.href = `?page=inspection&vehicle_id=${vehicleId}`;
}

function editVehicle(vehicleId) {
    window.location.href = `?page=management&action=edit_vehicle&id=${vehicleId}`;
}

function addVehicle() {
    window.location.href = `?page=management&action=add_vehicle`;
}
</script>