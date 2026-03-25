<?php
/**
 * Container-Prüfung Check Page
 * Einzelnen Container durchgehen und prüfen
 */

if (!isset($_GET['item_id']) || !is_numeric($_GET['item_id'])) {
    header('Location: ?page=container_inspection_start');
    exit;
}

$itemId = (int)$_GET['item_id'];
$db = Database::getInstance();
$currentUser = getCurrentUser();

// Item + Session laden
$item = $db->fetchOne("
    SELECT 
        cii.*,
        c.name as container_name,
        ci.id as session_id,
        ci.vehicle_id,
        v.name as vehicle_name
    FROM container_inspection_items cii
    LEFT JOIN containers c ON cii.container_id = c.id
    LEFT JOIN container_inspections ci ON cii.container_inspection_id = ci.id
    LEFT JOIN vehicles v ON ci.vehicle_id = v.id
    WHERE cii.id = ?
", [$itemId]);

if (!$item) {
    die("Container-Item nicht gefunden.");
}

if ($item['status'] === 'completed') {
    header('Location: ?page=container_inspection_overview&session_id=' . $item['session_id']);
    exit;
}

// Alle Compartments des Containers laden
$allCompartments = [];
$result = $db->query("
    SELECT 
        c.id,
        c.name,
        c.container_id,
        c.sort_order
    FROM compartments c
    WHERE c.container_id = ?
    ORDER BY c.sort_order ASC
", [$item['container_id']]);

foreach ($result->fetchAll() as $comp) {
    // Target-Produkte für dieses Compartment laden
    $products = [];
    $prodResult = $db->query("
        SELECT 
            cpt.product_id,
            cpt.quantity,
            p.name as product_name,
            p.has_expiry
        FROM compartment_products_target cpt
        LEFT JOIN products p ON cpt.product_id = p.id
        WHERE cpt.compartment_id = ?
        ORDER BY p.name ASC
    ", [$comp['id']]);
    
    foreach ($prodResult->fetchAll() as $prod) {
        $products[] = $prod;
    }
    
    $comp['products'] = $products;
    $allCompartments[] = $comp;
}

if (empty($allCompartments)) {
    die("Keine Fächer gefunden für diesen Container.");
}
?>

<div class="inspection-container">
    <div class="inspection-header">
        <a href="?page=container_inspection_overview&session_id=<?= $item['session_id'] ?>" class="btn btn-outline btn-sm mb-2">
            ← Zurück zur Übersicht
        </a>
        <h1>🗂️ <?= htmlspecialchars($item['container_name']) ?></h1>
        <p class="text-muted">
            <?= htmlspecialchars($item['vehicle_name']) ?> · 
            Fach <span id="current-index">1</span> von <?= count($allCompartments) ?>
        </p>
    </div>
    
    <div id="inspection-content" class="inspection-content">
        <!-- Dynamisch gefüllt via JavaScript -->
    </div>
    
    <div class="inspection-footer">
        <button type="button" id="prevBtn" class="btn btn-outline" disabled>← Zurück</button>
        <button type="button" id="nextBtn" class="btn btn-primary">Weiter →</button>
        <button type="button" id="finishBtn" class="btn btn-success" style="display:none;">Abschließen</button>
    </div>
</div>

<!-- Modal: Prüfung abschließen -->
<div class="modal" id="finishModal" style="display:none;">
    <div class="modal-content">
        <h3>Prüfung abschließen</h3>
        <p>Bitte geben Sie Ihren Namen ein, um die Container-Prüfung abzuschließen.</p>
        <div class="form-group">
            <label for="inspectorName">Ihr Name *</label>
            <input type="text" id="inspectorName" class="form-control" placeholder="Vorname Nachname" required>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeFinishModal()">Abbrechen</button>
            <button type="button" class="btn btn-success" onclick="submitInspection()">Abschließen</button>
        </div>
    </div>
</div>

<style>
.inspection-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.inspection-header {
    margin-bottom: 2rem;
    text-align: center;
}

.inspection-content {
    min-height: 400px;
    margin-bottom: 2rem;
}

.compartment-view {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.compartment-view h2 {
    margin-top: 0;
    color: var(--primary-color);
}

.products-list {
    margin-top: 1.5rem;
}

.product-item {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: var(--gray-50);
}

.product-item h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.product-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    color: var(--gray-600);
    font-size: 0.9rem;
}

.instances-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.instance-input {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.instance-input label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--gray-700);
}

.instance-input input[type="date"] {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    width: 100%;
}

.no-expiry-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--primary-color);
    color: white;
    border-radius: 4px;
    font-weight: 500;
}

.no-expiry-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.inspection-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-top: 1px solid var(--border-color);
    position: sticky;
    bottom: 0;
}

#finishModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-content h3 {
    margin-top: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 1rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (max-width: 768px) {
    .instances-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const ITEM_ID = <?= $itemId ?>;
const SESSION_ID = <?= $item['session_id'] ?>;
const ALL_COMPARTMENTS = <?= json_encode($allCompartments, JSON_UNESCAPED_UNICODE) ?>;
let currentIndex = 0;
let inspectionData = {};

function renderCompartment(index) {
    const comp = ALL_COMPARTMENTS[index];
    if (!comp) return;
    
    let html = `
        <div class="compartment-view">
            <h2>${comp.name}</h2>
            
            <div class="products-list">
    `;
    
    if (comp.products && comp.products.length > 0) {
        comp.products.forEach((product) => {
            const hasExpiry = product.has_expiry !== '0' && product.has_expiry !== 0;
            const productKey = `${comp.id}_${product.product_id}`;
            
            html += `
                <div class="product-item" data-product-key="${productKey}">
                    <h3>${product.product_name}</h3>
                    <div class="product-meta">
                        <span>SOLL: ${product.quantity}</span>
                        ${hasExpiry ? '<span>📅 MHD-pflichtig</span>' : '<span>✓ Kein MHD</span>'}
                    </div>
            `;
            
            // "Fehlt komplett" Checkbox
            html += `
                <div style="margin-bottom: 0.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" 
                               id="missing_${productKey}"
                               onchange="toggleInstances('${productKey}', this.checked)"
                               style="width: 18px; height: 18px;">
                        <span style="font-weight: 500; color: var(--error-red);">Fehlt komplett</span>
                    </label>
                </div>
            `;
            
            if (!hasExpiry) {
                // Kein MHD: Vorhanden-Checkboxen (default: checked)
                html += `<div class="instances-grid" id="instances_${productKey}">`;
                for (let i = 0; i < product.quantity; i++) {
                    html += `
                        <div class="no-expiry-check">
                            <input type="checkbox" 
                                   id="present_${productKey}_${i}"
                                   checked
                                   data-product-key="${productKey}"
                                   data-instance="${i}">
                            <label for="present_${productKey}_${i}">Stück ${i + 1} vorhanden</label>
                        </div>
                    `;
                }
                html += '</div>';
            } else {
                // MHD-pflichtig: Checkbox + MHD-Feld (default: checked)
                html += `<div class="instances-grid" id="instances_${productKey}">`;
                for (let i = 0; i < product.quantity; i++) {
                    html += `
                        <div class="instance-input">
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <input type="checkbox" 
                                       id="present_${productKey}_${i}"
                                       checked
                                       onchange="toggleMhdField('${productKey}', ${i}, this.checked)"
                                       style="width: 18px; height: 18px;">
                                <span style="font-weight: 500;">Stück ${i + 1} vorhanden</span>
                            </label>
                            <div id="mhd_container_${productKey}_${i}">
                                <label for="mhd_${productKey}_${i}" style="font-size: 0.85rem; color: var(--gray-700);">MHD:</label>
                                <input type="date" 
                                       id="mhd_${productKey}_${i}"
                                       data-product-key="${productKey}"
                                       data-instance="${i}">
                            </div>
                        </div>
                    `;
                }
                html += '</div>';
            }
            
            html += '</div>';
        });
    } else {
        html += '<p class="text-muted">Keine Produkte in diesem Fach</p>';
    }
    
    html += '</div></div>';
    
    document.getElementById('inspection-content').innerHTML = html;
    document.getElementById('current-index').textContent = index + 1;
    
    loadSavedData(index);
    updateNavigation();
}

function loadSavedData(index) {
    const comp = ALL_COMPARTMENTS[index];
    if (!comp || !comp.products) return;
    
    comp.products.forEach(product => {
        const productKey = `${comp.id}_${product.product_id}`;
        const savedData = inspectionData[productKey];
        
        if (savedData) {
            const missingCheckbox = document.getElementById(`missing_${productKey}`);
            if (missingCheckbox && savedData.missing) {
                missingCheckbox.checked = true;
                toggleInstances(productKey, true);
                return;
            }
            
            const hasExpiry = product.has_expiry !== '0' && product.has_expiry !== 0;
            
            for (let i = 0; i < product.quantity; i++) {
                if (hasExpiry) {
                    const presentCheckbox = document.getElementById(`present_${productKey}_${i}`);
                    const mhdInput = document.getElementById(`mhd_${productKey}_${i}`);
                    
                    if (savedData[i]) {
                        if (presentCheckbox) {
                            presentCheckbox.checked = savedData[i].present !== false;
                            toggleMhdField(productKey, i, savedData[i].present !== false);
                        }
                        if (mhdInput && savedData[i].mhd) {
                            mhdInput.value = savedData[i].mhd;
                        }
                    }
                } else {
                    const checkbox = document.getElementById(`present_${productKey}_${i}`);
                    if (checkbox && savedData[i]) {
                        checkbox.checked = savedData[i] === 'present';
                    }
                }
            }
        }
    });
}

function saveCurrentData() {
    const comp = ALL_COMPARTMENTS[currentIndex];
    if (!comp || !comp.products) return;
    
    comp.products.forEach(product => {
        const productKey = `${comp.id}_${product.product_id}`;
        const hasExpiry = product.has_expiry !== '0' && product.has_expiry !== 0;
        
        const missingCheckbox = document.getElementById(`missing_${productKey}`);
        if (missingCheckbox && missingCheckbox.checked) {
            inspectionData[productKey] = { missing: true };
            return;
        }
        
        inspectionData[productKey] = {};
        
        for (let i = 0; i < product.quantity; i++) {
            if (hasExpiry) {
                const presentCheckbox = document.getElementById(`present_${productKey}_${i}`);
                const mhdInput = document.getElementById(`mhd_${productKey}_${i}`);
                
                inspectionData[productKey][i] = {
                    present: presentCheckbox ? presentCheckbox.checked : false,
                    mhd: mhdInput && presentCheckbox.checked ? mhdInput.value : null
                };
            } else {
                const checkbox = document.getElementById(`present_${productKey}_${i}`);
                inspectionData[productKey][i] = checkbox && checkbox.checked ? 'present' : 'missing';
            }
        }
    });
}

function updateNavigation() {
    document.getElementById('prevBtn').disabled = currentIndex === 0;
    
    const isLast = currentIndex === ALL_COMPARTMENTS.length - 1;
    document.getElementById('nextBtn').style.display = isLast ? 'none' : 'block';
    document.getElementById('finishBtn').style.display = isLast ? 'block' : 'none';
}

function toggleInstances(productKey, isMissing) {
    const container = document.getElementById(`instances_${productKey}`);
    if (container) {
        container.style.display = isMissing ? 'none' : 'grid';
    }
}

function toggleMhdField(productKey, instanceIndex, isPresent) {
    const container = document.getElementById(`mhd_container_${productKey}_${instanceIndex}`);
    if (container) {
        container.style.display = isPresent ? 'block' : 'none';
    }
}

function nextCompartment() {
    saveCurrentData();
    if (currentIndex < ALL_COMPARTMENTS.length - 1) {
        currentIndex++;
        renderCompartment(currentIndex);
    }
}

function prevCompartment() {
    saveCurrentData();
    if (currentIndex > 0) {
        currentIndex--;
        renderCompartment(currentIndex);
    }
}

function showFinishModal() {
    saveCurrentData();
    document.getElementById('finishModal').style.display = 'flex';
}

function closeFinishModal() {
    document.getElementById('finishModal').style.display = 'none';
}

async function submitInspection() {
    const inspectorName = document.getElementById('inspectorName').value.trim();
    
    if (!inspectorName) {
        alert('Bitte geben Sie Ihren Namen ein.');
        return;
    }
    
    try {
        const res = await fetch('/api/container_inspection_items.php?action=complete&id=' + ITEM_ID, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify({
                inspector_name: inspectorName,
                inspection_data: inspectionData
            })
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert('✓ Container-Prüfung erfolgreich abgeschlossen!');
            window.location.href = '?page=container_inspection_overview&session_id=' + SESSION_ID;
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannt'));
        }
    } catch (err) {
        alert('Netzwerkfehler: ' + err);
    }
}

// Event Listeners
document.getElementById('prevBtn').addEventListener('click', prevCompartment);
document.getElementById('nextBtn').addEventListener('click', nextCompartment);
document.getElementById('finishBtn').addEventListener('click', showFinishModal);

// Init
renderCompartment(0);
</script>
