<?php
/**
 * pages/reports.php
 * Berichte und Export-Funktionen
 */
?>

<div class="container-fluid">
    <div class="page-header">
        <h1>Berichte</h1>
        <p>Exportiere Daten als PDF oder Excel</p>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                Bericht erstellen
            </h2>
        </div>
        <div class="card-body">
            <form id="reportForm" class="report-form">
                <div class="form-group">
                    <label for="report-type" class="form-label">Berichtstyp</label>
                    <select id="report-type" class="form-select" required>
                        <option value="expiring">Ablaufende Produkte</option>
                        <option value="inspections">Kontrollen</option>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="date-from" class="form-label">Von</label>
                            <input type="date" id="date-from" class="form-input" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="date-to" class="form-label">Bis</label>
                            <input type="date" id="date-to" class="form-input" required>
                        </div>
                    </div>
                </div>
                
                <div class="export-buttons">
                    <button type="button" id="export-pdf" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                        </svg>
                        Als PDF exportieren
                    </button>
                    <button type="button" id="export-excel" class="btn btn-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="9" y2="15"/>
                            <line x1="15" y1="9" x2="15" y2="15"/>
                        </svg>
                        Als Excel exportieren
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Schnelle Berichte -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                Schnelle Berichte
            </h2>
        </div>
        <div class="card-body">
            <div class="quick-reports">
                <button class="btn btn-outline quick-report-btn" onclick="quickReport('expiring-4weeks')">
                    <div class="quick-report-content">
                        <h4>Ablaufende Produkte</h4>
                        <p>Nächste 4 Wochen (PDF)</p>
                    </div>
                </button>
                
                <button class="btn btn-outline quick-report-btn" onclick="quickReport('expiring-4weeks-excel')">
                    <div class="quick-report-content">
                        <h4>Ablaufende Produkte</h4>
                        <p>Nächste 4 Wochen (Excel)</p>
                    </div>
                </button>
                
                <button class="btn btn-outline quick-report-btn" onclick="quickReport('expired')">
                    <div class="quick-report-content">
                        <h4>Abgelaufene Produkte</h4>
                        <p>Alle abgelaufenen (PDF)</p>
                    </div>
                </button>
                
                <button class="btn btn-outline quick-report-btn" onclick="quickReport('inspections-month')">
                    <div class="quick-report-content">
                        <h4>Kontrollen</h4>
                        <p>Letzter Monat (PDF)</p>
                    </div>
                </button>
                
                <button class="btn btn-outline quick-report-btn" onclick="quickReport('all-products-excel')">
                    <div class="quick-report-content">
                        <h4>Alle Produkte</h4>
                        <p>Komplette Übersicht (Excel)</p>
                    </div>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Aktuelle ablaufende Produkte -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; margin-right: 8px;">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
                Aktuelle ablaufende Produkte
            </h2>
        </div>
        <div class="card-body">
            <?php
            $expiringProducts = getExpiringProducts(4);
            if (empty($expiringProducts)):
            ?>
            <div class="text-center p-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 48px; height: 48px; color: var(--success-green); margin-bottom: 16px;">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
                <p>Keine ablaufenden Produkte in den nächsten 4 Wochen.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Standort</th>
                            <th>Ablaufdatum</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiringProducts as $product): ?>
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        if ($product['days_until_expiry'] < 0) {
                            $statusClass = 'expired';
                            $statusText = 'Abgelaufen';
                        } elseif ($product['days_until_expiry'] <= 7) {
                            $statusClass = 'expiring-soon';
                            $statusText = $product['days_until_expiry'] . ' Tage';
                        } else {
                            $statusClass = 'expiring-later';
                            $statusText = $product['days_until_expiry'] . ' Tage';
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <strong><?= h($product['product_name']) ?></strong>
                                    <small>Menge: <?= $product['quantity'] ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="location-cell">
                                    <?= h($product['vehicle_name']) ?><br>
                                    <small><?= h($product['container_name']) ?> › <?= h($product['compartment_name']) ?></small>
                                </div>
                            </td>
                            <td><?= formatDate($product['expiry_date']) ?></td>
                            <td>
                                <span class="badge badge-<?= $statusClass === 'expired' ? 'danger' : ($statusClass === 'expiring-soon' ? 'warning' : 'info') ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.report-form {
    max-width: 600px;
}

.export-buttons {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.export-buttons .btn {
    flex: 1;
}

.quick-reports {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.quick-report-btn {
    padding: 20px;
    text-align: left;
    height: auto;
    border: 2px solid var(--border-color);
    transition: var(--transition-fast);
}

.quick-report-btn:hover {
    border-color: var(--primary-red);
    background: rgba(220, 20, 60, 0.05);
}

.quick-report-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 4px;
}

.quick-report-content p {
    font-size: 14px;
    color: var(--dark-gray);
    margin: 0;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}

.data-table th,
.data-table td {
    padding: 12px 8px;
    border-bottom: 1px solid var(--border-color);
    text-align: left;
}

.data-table th {
    background: var(--light-gray);
    font-weight: 600;
    color: var(--primary-black);
    position: sticky;
    top: 0;
    z-index: 10;
}

.product-cell strong {
    display: block;
    margin-bottom: 2px;
}

.product-cell small,
.location-cell small {
    color: var(--dark-gray);
    font-size: 12px;
}

@media (max-width: 768px) {
    .export-buttons {
        flex-direction: column;
    }
    
    .quick-reports {
        grid-template-columns: 1fr;
    }
    
    .data-table {
        font-size: 14px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px 4px;
    }
}
</style>

<script>
// Reports JavaScript
$(document).ready(function() {
    // Export-Button Event-Listener
    $('#export-pdf').click(function() {
        exportReport('pdf');
    });
    
    $('#export-excel').click(function() {
        exportReport('excel');
    });
    
    // Default-Daten setzen
    const today = new Date().toISOString().split('T')[0];
    const lastMonth = new Date();
    lastMonth.setMonth(lastMonth.getMonth() - 1);
    
    $('#date-from').val(lastMonth.toISOString().split('T')[0]);
    $('#date-to').val(today);
});

function exportReport(format) {
    const reportType = $('#report-type').val();
    const dateFrom = $('#date-from').val();
    const dateTo = $('#date-to').val();
    
    if (!dateFrom || !dateTo) {
        alert('Bitte wählen Sie einen gültigen Zeitraum aus.');
        return;
    }
    
    const params = new URLSearchParams();
    params.append('format', format);
    params.append('report_type', reportType);
    params.append('date_from', dateFrom);
    params.append('date_to', dateTo);
    
    // Neue URL öffnen für Download
    const url = `api/export-report.php?${params.toString()}`;
    
    if (format === 'pdf') {
        // PDF in neuem Tab öffnen
        window.open(url, '_blank');
    } else {
        // Excel als Download
        window.location.href = url;
    }
}

function quickReport(type) {
    let params = new URLSearchParams();
    let format = 'pdf'; // Standard auf PDF
    
    // Bei Excel-Export (könnte später erweitert werden)
    if (type.includes('excel')) {
        format = 'excel';
        type = type.replace('-excel', '');
    }
    
    params.append('format', format);
    
    switch(type) {
        case 'expiring-4weeks':
            params.append('report_type', 'expiring');
            params.append('date_from', new Date().toISOString().split('T')[0]);
            params.append('date_to', new Date(Date.now() + 28*24*60*60*1000).toISOString().split('T')[0]);
            break;
        case 'expired':
            params.append('report_type', 'expiring');
            params.append('date_from', '2020-01-01');
            params.append('date_to', new Date().toISOString().split('T')[0]);
            break;
        case 'inspections-month':
            let lastMonth = new Date();
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            params.append('report_type', 'inspections');
            params.append('date_from', lastMonth.toISOString().split('T')[0]);
            params.append('date_to', new Date().toISOString().split('T')[0]);
            break;
        case 'all-products':
            params.append('report_type', 'expiring');
            params.append('date_from', new Date().toISOString().split('T')[0]);
            params.append('date_to', '2030-12-31');
            break;
    }
    
    const url = `api/export-report.php?${params.toString()}`;
    
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
}
</script>