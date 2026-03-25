<?php
/**
 * pages/dashboard.php
 * Dashboard-Seite
 */

$expiringProducts = getExpiringProducts(4); // 4 Wochen
$lastInspections = getLastInspections();

// Laufende Container-Prüfungen laden
$db = Database::getInstance();
$containerInspections = [];
$userRole = getCurrentUser()['role'];
$showContainerInspections = in_array($userRole, ['admin', 'fahrzeugwart', 'kontrolle']);

if ($showContainerInspections) {
    $result = $db->query("
        SELECT 
            ci.id,
            ci.vehicle_id,
            ci.started_at,
            v.name as vehicle_name,
            u.full_name as starter_name,
            COUNT(cii.id) as total_count,
            SUM(CASE WHEN cii.status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM container_inspections ci
        LEFT JOIN vehicles v ON ci.vehicle_id = v.id
        LEFT JOIN users u ON ci.started_by = u.id
        LEFT JOIN container_inspection_items cii ON ci.id = cii.container_inspection_id
        WHERE ci.status = 'in_progress'
        GROUP BY ci.id
        ORDER BY ci.started_at DESC
        LIMIT 5
    ");
    
    $containerInspections = $result;
}

$isKontrolleOnly = ($userRole === 'kontrolle');
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Willkommen, <?= h(getCurrentUser()['full_name']) ?>!</p>
    </div>
    
    <div class="dashboard-grid">
        <!-- Laufende Container-Prüfungen (wenn Berechtigung) -->
        <?php if ($showContainerInspections): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                    </svg>
                    🗂️ Laufende Container-Prüfungen
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($containerInspections)): ?>
                    <p class="text-muted">Keine laufenden Prüfungen</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($containerInspections as $ci): ?>
                            <a href="?page=container_inspection_overview&session_id=<?= $ci['id'] ?>" class="list-group-item">
                                <div class="list-item-header">
                                    <strong><?= htmlspecialchars($ci['vehicle_name']) ?></strong>
                                    <span class="badge badge-info">
                                        <?= $ci['completed_count'] ?> / <?= $ci['total_count'] ?> Container
                                    </span>
                                </div>
                                <small class="text-muted">
                                    Gestartet von <?= htmlspecialchars($ci['starter_name']) ?> am 
                                    <?= date('d.m.Y H:i', strtotime($ci['started_at'])) ?> Uhr
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="?page=container_inspection_start" class="btn btn-primary btn-small">
                    Zur Container-Prüfung
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$isKontrolleOnly): ?>
        <!-- Ablaufende Produkte -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                    Ablaufende Produkte (nächste 4 Wochen)
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($expiringProducts)): ?>
                <div class="text-center p-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: var(--success-green); margin-bottom: 16px;">
                        <polyline points="20,6 9,17 4,12"/>
                    </svg>
                    <p>Keine ablaufenden Produkte in den nächsten 4 Wochen.</p>
                </div>
                <?php else: ?>
                <div class="expiring-products-list" id="expiring-products-list">
                    <?php foreach ($expiringProducts as $product): ?>
                    <?php
                    $expiryClass = '';
                    if ($product['days_until_expiry'] < 0) $expiryClass = 'expired';
                    elseif ($product['days_until_expiry'] <= 7) $expiryClass = 'expiring-soon';
                    else $expiryClass = 'expiring-later';
                    ?>
                    <div class="expiring-product">
                        <div class="product-info">
                            <div class="product-name"><?= h($product['product_name']) ?></div>
                            <div class="product-location">
                                <?= h($product['vehicle_name']) ?> › 
                                <?= h($product['container_name']) ?> › 
                                <?= h($product['compartment_name']) ?>
                            </div>
                        </div>
                        <div class="expiry-info">
                            <div class="expiry-date <?= $expiryClass ?>">
                                <?= formatDate($product['expiry_date']) ?>
                            </div>
                            <div class="days-remaining <?= $expiryClass ?>">
                                <?php if ($product['days_until_expiry'] < 0): ?>
                                    Abgelaufen
                                <?php else: ?>
                                    <?= $product['days_until_expiry'] ?> Tage
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($expiringProducts)): ?>
            <div class="card-footer">
                <a href="?page=reports" class="btn btn-outline btn-small">
                    Vollständiger Bericht
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$isKontrolleOnly): ?>
        <!-- Letzte Kontrollen -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                        <path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4m6-6h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-4m-6 0v4m6-4v4"/>
                        <rect x="8" y="3" width="8" height="6"/>
                    </svg>
                    Fahrzeuge & letzte Kontrollen
                </h2>
            </div>
            <div class="card-body">
                <div id="last-inspections-list">
                    <?php foreach ($lastInspections as $inspection): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <h3 class="vehicle-title"><?= h($inspection['vehicle_name']) ?></h3>
                        </div>
                        <div class="vehicle-info">
                            <div class="last-inspection">
                                <span class="inspection-date">
                                    Letzte Kontrolle: 
                                    <?= $inspection['completed_at'] ? timeAgo($inspection['completed_at']) : 'Noch nie' ?>
                                </span>
                                <?php if ($inspection['inspector_name']): ?>
                                <span class="inspector-name">
                                    von <?= h($inspection['inspector_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <a href="?page=inspection&vehicle_id=<?= $inspection['vehicle_id'] ?>" 
                               class="btn btn-primary btn-block">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 18px; height: 18px;">
                                    <polyline points="20,6 9,17 4,12"/>
                                </svg>
                                Kontrolle starten
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$isKontrolleOnly): ?>
    <!-- Statistiken -->
    <?php if (isAdmin()): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                Schnellstatistiken
            </h2>
        </div>
        <div class="card-body">
            <?php
            $db = getDB();
            $stats = [
                'total_products' => $db->fetchColumn("SELECT COUNT(*) FROM compartment_products_actual"),
                'expired_products' => $db->fetchColumn("SELECT COUNT(*) FROM compartment_products_actual WHERE expiry_date < CURDATE()"),
                'total_inspections' => $db->fetchColumn("SELECT COUNT(*) FROM inspections WHERE status = 'completed'"),
                'inspections_this_month' => $db->fetchColumn("SELECT COUNT(*) FROM inspections WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())"),
                'container_inspections_total' => $db->fetchColumn("SELECT COUNT(*) FROM container_inspections WHERE status = 'completed'"),
                'container_inspections_month' => $db->fetchColumn("SELECT COUNT(*) FROM container_inspections WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())"),
                'containers_checked_month' => $db->fetchColumn("SELECT COUNT(*) FROM container_inspection_items WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURDATE()) AND YEAR(completed_at) = YEAR(CURDATE())")
            ];
            ?>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_products']) ?></div>
                    <div class="stat-label">Gesamte Produkte</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number <?= $stats['expired_products'] > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= number_format($stats['expired_products']) ?>
                    </div>
                    <div class="stat-label">Abgelaufene Produkte</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['total_inspections']) ?></div>
                    <div class="stat-label">Item-Kontrollen gesamt</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['inspections_this_month']) ?></div>
                    <div class="stat-label">Item-Kontrollen diesen Monat</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['container_inspections_total']) ?></div>
                    <div class="stat-label">Container-Prüfungen gesamt</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['container_inspections_month']) ?></div>
                    <div class="stat-label">Container-Prüfungen diesen Monat</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($stats['containers_checked_month']) ?></div>
                    <div class="stat-label">Container geprüft (Monat)</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.page-header {
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.page-header p {
    color: var(--dark-gray);
    font-size: 16px;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: rgba(220, 20, 60, 0.05);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-red);
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    color: var(--dark-gray);
    font-weight: 500;
}

.text-danger {
    color: var(--danger-red) !important;
}

.text-success {
    color: var(--success-green) !important;
}

.text-success {
    color: var(--success-green) !important;
}

.list-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.list-group-item {
    display: block;
    padding: 1rem;
    background: var(--gray-50);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.list-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.list-item-header strong {
    font-size: 1.05rem;
    color: var(--primary-black);
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 12px;
}

.badge-info {
    background: #e3f2fd;
    color: #1976d2;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-number {
        font-size: 24px;
    }
}
</style>

<script>
$(document).ready(function() {
    Dashboard.init();
});
</script>