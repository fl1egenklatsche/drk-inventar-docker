<?php
/**
 * Vereinfachte Inspection Page
 * - Kein "Fehlt komplett" Toggle
 * - MHD-Felder immer sichtbar
 * - Produkte ohne MHD: Einfache Vorhanden-Checkbox
 */

if (!isset($_GET['vehicle_id']) || !is_numeric($_GET['vehicle_id'])) {
    header('Location: ?page=vehicles');
    exit;
}

$vehicleId = (int)$_GET['vehicle_id'];
$vehicle = null;
$allCompartments = [];
$db = Database::getInstance();

try {
    $vehicle = getVehicleStructure($vehicleId);
    if ($vehicle && !empty($vehicle['containers'])) {
        foreach ($vehicle['containers'] as $container) {
            foreach ($container['compartments'] as $compartment) {
                $compartment['container_name'] = $container['name'];
                $compartment['products'] = $compartment['target_products'] ?? [];
                $allCompartments[] = $compartment;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading vehicle structure: " . $e->getMessage());
    header('Location: ?page=vehicles');
    exit;
}

if (empty($allCompartments)) {
    die("Keine Fächer gefunden für dieses Fahrzeug.");
}
?>

<div class="inspection-container">
    <div class="inspection-header">
        <h1>Kontrolle: <?= h($vehicle['name'] ?? 'Fahrzeug') ?></h1>
        <p class="text-muted">Fach <span id="current-index">1</span> von <?= count($allCompartments) ?></p>
    </div>
    
    <div id="inspection-content" class="inspection-content">
        <!-- Dynamisch gefüllt -->
    </div>
    
    <div class="inspection-footer">
        <button type="button" id="prevBtn" class="btn btn-outline" disabled>← Zurück</button>
        <button type="button" id="nextBtn" class="btn btn-primary">Weiter →</button>
        <button type="button" id="finishBtn" class="btn btn-success" style="display:none;">Abschließen</button>
    </div>
</div>

<style>
.inspection-container {
    max-width: 800px;
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
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.instance-input {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.instance-input label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--gray-700);
}

.instance-input input[type="date"],
.instance-input input[type="checkbox"] {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
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
</style>

<script>
const VEHICLE_ID = <?= $vehicleId ?>;
const ALL_COMPARTMENTS = <?= json_encode($allCompartments, JSON_UNESCAPED_UNICODE) ?>;
let currentIndex = 0;
let inspectionData = {};

function renderCompartment(index) {
    const comp = ALL_COMPARTMENTS[index];
    if (!comp) return;
    
    let html = `
        <div class="compartment-view">
            <h2>${comp.name}</h2>
            <p class="text-muted">Behälter: ${comp.container_name || 'Unbekannt'}</p>
            
            <div class="products-list">
    `;
    
    if (comp.products && comp.products.length > 0) {
        comp.products.forEach((product, pidx) => {
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
            
            // "Fehlt komplett" Checkbox (optional)
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
                // Produkte ohne MHD: Einfache Vorhanden-Checkboxen (default: checked)
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
                // Produkte mit MHD: Vorhanden-Checkbox + MHD-Feld (default: checked)
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
                                       data-instance="${i}"
                                       style="width: 100%;">
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
    
    // Lade gespeicherte Daten falls vorhanden
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
            // Prüfe ob komplett fehlend markiert
            const missingCheckbox = document.getElementById(`missing_${productKey}`);
            if (missingCheckbox && savedData.missing) {
                missingCheckbox.checked = true;
                toggleInstances(productKey, true);
                return; // Skip loading instances
            }
            
            const hasExpiry = product.has_expiry !== '0' && product.has_expiry !== 0;
            
            // Lade einzelne Instanzen
            for (let i = 0; i < product.quantity; i++) {
                if (hasExpiry) {
                    // MHD-Produkt: Checkbox + MHD-Feld
                    const presentCheckbox = document.getElementById(`present_${productKey}_${i}`);
                    const mhdInput = document.getElementById(`mhd_${productKey}_${i}`);
                    
                    if (savedData[i]) {
                        if (savedData[i].present !== undefined) {
                            // Neue Struktur: { present: true/false, mhd: "2026-12-31" }
                            if (presentCheckbox) {
                                presentCheckbox.checked = savedData[i].present;
                                toggleMhdField(productKey, i, savedData[i].present);
                            }
                            if (mhdInput && savedData[i].mhd) {
                                mhdInput.value = savedData[i].mhd;
                            }
                        } else if (typeof savedData[i] === 'string') {
                            // Legacy: nur MHD-Datum gespeichert
                            if (presentCheckbox) {
                                presentCheckbox.checked = true;
                                toggleMhdField(productKey, i, true);
                            }
                            if (mhdInput) {
                                mhdInput.value = savedData[i];
                            }
                        }
                    }
                } else {
                    // Kein MHD: Nur Checkbox
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
        
        // Prüfe "Fehlt komplett" Checkbox
        const missingCheckbox = document.getElementById(`missing_${productKey}`);
        if (missingCheckbox && missingCheckbox.checked) {
            inspectionData[productKey] = { missing: true };
            return; // Skip instances
        }
        
        // Speichere einzelne Instanzen
        inspectionData[productKey] = {};
        
        for (let i = 0; i < product.quantity; i++) {
            if (hasExpiry) {
                // MHD-Produkt: Checkbox + MHD
                const presentCheckbox = document.getElementById(`present_${productKey}_${i}`);
                const mhdInput = document.getElementById(`mhd_${productKey}_${i}`);
                
                inspectionData[productKey][i] = {
                    present: presentCheckbox ? presentCheckbox.checked : false,
                    mhd: (mhdInput && presentCheckbox?.checked) ? mhdInput.value : ''
                };
            } else {
                // Kein MHD: Nur Checkbox
                const checkbox = document.getElementById(`present_${productKey}_${i}`);
                if (checkbox) {
                    inspectionData[productKey][i] = checkbox.checked ? 'present' : 'missing';
                }
            }
        }
    });
}

function updateNavigation() {
    document.getElementById('prevBtn').disabled = currentIndex === 0;
    
    const isLast = currentIndex === ALL_COMPARTMENTS.length - 1;
    document.getElementById('nextBtn').style.display = isLast ? 'none' : 'inline-block';
    document.getElementById('finishBtn').style.display = isLast ? 'inline-block' : 'none';
}

function toggleInstances(productKey, isMissing) {
    const instancesDiv = document.getElementById(`instances_${productKey}`);
    if (!instancesDiv) return;
    
    if (isMissing) {
        // Verstecke Instanzen und lösche alle Eingaben
        instancesDiv.style.display = 'none';
        
        // Lösche alle Datumseingaben
        const dateInputs = instancesDiv.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => input.value = '');
        
        // Deaktiviere alle Checkboxen
        const checkboxes = instancesDiv.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Markiere in inspectionData als komplett fehlend
        inspectionData[productKey] = { missing: true };
    } else {
        // Zeige Instanzen wieder an
        instancesDiv.style.display = 'grid';
        
        // Entferne missing-Flag
        if (inspectionData[productKey]) {
            delete inspectionData[productKey].missing;
        }
    }
}

function toggleMhdField(productKey, instanceIndex, isPresent) {
    const mhdContainer = document.getElementById(`mhd_container_${productKey}_${instanceIndex}`);
    const mhdInput = document.getElementById(`mhd_${productKey}_${instanceIndex}`);
    
    if (!mhdContainer) return;
    
    if (isPresent) {
        // Zeige MHD-Feld
        mhdContainer.style.display = 'block';
    } else {
        // Verstecke MHD-Feld und lösche Datum
        mhdContainer.style.display = 'none';
        if (mhdInput) mhdInput.value = '';
    }
}

document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentIndex > 0) {
        saveCurrentData();
        currentIndex--;
        renderCompartment(currentIndex);
    }
});

document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentIndex < ALL_COMPARTMENTS.length - 1) {
        saveCurrentData();
        currentIndex++;
        renderCompartment(currentIndex);
    }
});

document.getElementById('finishBtn').addEventListener('click', async () => {
    saveCurrentData();
    
    if (confirm('Kontrolle wirklich abschließen?')) {
        // TODO: Daten ans Backend senden
        console.log('Inspection data:', inspectionData);
        alert('Kontrolle gespeichert! (Backend-Integration folgt)');
        window.location.href = '?page=vehicles';
    }
});

// Initial render
renderCompartment(0);
</script>
