<?php
/**
 * pages/inspection_simple.php
 * Einfache Kontrollseite für Tests
 */

if (!isset($_GET['vehicle_id']) || !is_numeric($_GET['vehicle_id'])) {
    header('Location: ?page=vehicles');
    exit;
}

$vehicleId = (int)$_GET['vehicle_id'];

// Einfache Test-Daten wenn keine DB-Daten vorhanden
$testCompartments = [
    [
        'id' => 1,
        'name' => 'Notfallkoffer',
        'container_name' => 'Hauptraum',
        'products' => [
            ['product_name' => 'Einmalhandschuhe', 'quantity' => 20],
            ['product_name' => 'Kompressen', 'quantity' => 10],
            ['product_name' => 'Pflaster', 'quantity' => 5]
        ]
    ],
    [
        'id' => 2,
        'name' => 'Medikamentenfach',
        'container_name' => 'Hauptraum',
        'products' => [
            ['product_name' => 'Schmerzmittel', 'quantity' => 3],
            ['product_name' => 'Desinfektionsmittel', 'quantity' => 2]
        ]
    ]
];

// Versuche echte Daten zu laden
$vehicle = null;
$allCompartments = [];
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    $vehicle = getVehicleStructure($vehicleId);
    if ($vehicle && !empty($vehicle['containers'])) {
        foreach ($vehicle['containers'] as $container) {
            foreach ($container['compartments'] as $compartment) {
                $compartment['container_name'] = $container['name'];
                $compartment['products'] = $compartment['target_products'] ?? [];
                
                // Lade bereits erfasste IST-Daten für jedes Produkt
                foreach ($compartment['products'] as &$product) {
                    $sql = "SELECT * FROM compartment_products_actual 
                            WHERE compartment_id = ? AND product_id = ? 
                            ORDER BY expiry_date";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$compartment['id'], $product['product_id']]);
                    $actualInstances = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $product['existing_instances'] = $actualInstances;
                }
                
                $allCompartments[] = $compartment;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading vehicle structure: " . $e->getMessage());
}

// Fallback auf Test-Daten wenn keine echten Daten
if (empty($allCompartments)) {
    $allCompartments = $testCompartments;
    $vehicle = ['name' => 'Test Fahrzeug', 'type' => 'RTW'];
}
?>

<div class="inspection-container">
    <div class="inspection-header">
        <h1>Kontrolle: <?= h($vehicle['name'] ?? 'Fahrzeug') ?></h1>
        <p>Fahrzeug-ID: <?= $vehicleId ?></p>
    </div>
    
    <div class="inspection-content" id="inspection-content">
        <!-- Wird dynamisch gefüllt -->
    </div>
    
    <div class="inspection-footer">
        <button type="button" id="prevBtn" class="btn btn-secondary" disabled>← Zurück</button>
        <span id="progressText">Fach 1 von <?= count($allCompartments) ?></span>
        <button type="button" id="nextBtn" class="btn btn-primary">Weiter →</button>
        <button type="button" id="finishBtn" class="btn btn-success" style="display:none;">Abschließen</button>
    </div>
</div>

<script>
// Globale Daten für JavaScript
const VEHICLE_ID = <?= $vehicleId ?>;
const ALL_COMPARTMENTS = <?= json_encode($allCompartments ?: [], JSON_UNESCAPED_UNICODE) ?>;

console.log('=== INSPECTION DEBUG START ===');
console.log('Vehicle ID:', VEHICLE_ID);
console.log('Compartments raw:', <?= json_encode($allCompartments ?: [], JSON_UNESCAPED_UNICODE) ?>);
console.log('Compartments count:', ALL_COMPARTMENTS ? ALL_COMPARTMENTS.length : 'undefined');

if (ALL_COMPARTMENTS && ALL_COMPARTMENTS.length > 0) {
    console.log('✓ Compartments found:', ALL_COMPARTMENTS.length);
    ALL_COMPARTMENTS.forEach((comp, idx) => {
        console.log(`Compartment ${idx}:`, comp.name, 'Products:', comp.products ? comp.products.length : 0);
        if (comp.products && comp.products.length > 0) {
            comp.products.forEach((prod, pidx) => {
                console.log(`  Product ${pidx}:`, prod.product_name || prod.name, 'Quantity:', prod.quantity);
            });
        } else {
            console.log(`  No products in compartment ${idx}`);
        }
    });
} else {
    console.error('✗ NO COMPARTMENTS FOUND!');
    console.log('ALL_COMPARTMENTS value:', ALL_COMPARTMENTS);
}
console.log('=== INSPECTION DEBUG END ===');

// Einfache Inspection-Logik
let currentCompartment = 0;
let inspectionData = {};

function renderCompartment(index) {
    const compartment = ALL_COMPARTMENTS[index];
    if (!compartment) return;
    
    let html = `
        <div class="compartment-view">
            <h2>Fach: ${compartment.name}</h2>
            <p>Behälter: ${compartment.container_name || 'Unbekannt'}</p>
            
            <div class="products-list">
    `;
    
    if (compartment.products && compartment.products.length > 0) {
        compartment.products.forEach((product, idx) => {
            const productId = `${index}_${idx}`;
            
            // Prüfe ob alle Instanzen fehlen (dann ist "fehlt komplett" gesetzt)
            const hasAnyInstance = product.existing_instances && product.existing_instances.length > 0;
            const isCompletelyMissing = !hasAnyInstance && product.quantity > 0;
            
            html += `
                <div class="product-item">
                    <h3>${product.product_name}</h3>
                    <p>Sollmenge: ${product.quantity}</p>
                    
                    <label>
                        <input type="checkbox" id="missing_${productId}" ${isCompletelyMissing ? 'checked' : ''}> 
                        Fehlt komplett
                    </label>
                    
                    <div class="instances" ${isCompletelyMissing ? 'style="display:none;"' : ''}>
            `;
            
            for (let i = 0; i < product.quantity; i++) {
                // Versuche existierende Daten zu laden
                let existingDate = '';
                if (product.existing_instances && product.existing_instances[i]) {
                    existingDate = product.existing_instances[i].expiry_date;
                }
                
                html += `
                    <div class="instance">
                        <label>Stück ${i + 1} Ablaufdatum:</label>
                        <input type="date" id="date_${productId}_${i}" value="${existingDate}">
                    </div>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
        });
    } else {
        html += '<p>Keine Produkte in diesem Fach.</p>';
    }
    
    html += `
            </div>
        </div>
    `;
    
    document.getElementById('inspection-content').innerHTML = html;
    
    // Event Listeners für "Fehlt komplett" Checkboxen
    setupCurrentCompartmentEvents();
    
    // Debug: Test all checkboxes after setup
    setTimeout(() => {
        console.log('=== CHECKBOX DEBUG ===');
        const compartment = ALL_COMPARTMENTS[index];
        if (compartment && compartment.products) {
            compartment.products.forEach((product, idx) => {
                const productId = `${index}_${idx}`;
                const checkbox = document.getElementById(`missing_${productId}`);
                const instancesDiv = checkbox ? checkbox.closest('.product-item').querySelector('.instances') : null;
                console.log(`Product ${idx}:`, {
                    productId,
                    checkboxExists: !!checkbox,
                    checkboxChecked: checkbox ? checkbox.checked : 'N/A',
                    instancesDivExists: !!instancesDiv,
                    instancesDivVisible: instancesDiv ? instancesDiv.style.display !== 'none' : 'N/A'
                });
            });
        }
        console.log('=== END DEBUG ===');
    }, 100);
    
    // Update navigation
    currentCompartment = index;
    updateNavigation();
}

function setupCurrentCompartmentEvents() {
    const compartment = ALL_COMPARTMENTS[currentCompartment];
    if (!compartment || !compartment.products) return;
    
    console.log('Setting up events for compartment:', currentCompartment);
    
    compartment.products.forEach((product, idx) => {
        const productId = `${currentCompartment}_${idx}`;
        const missingCheckbox = document.getElementById(`missing_${productId}`);
        const instancesDiv = missingCheckbox ? missingCheckbox.closest('.product-item').querySelector('.instances') : null;
        
        console.log(`Product ${idx}: checkbox found:`, !!missingCheckbox, 'instances div found:', !!instancesDiv);
        
        if (missingCheckbox && instancesDiv) {
            // Remove existing event listeners to prevent duplicates
            missingCheckbox.removeEventListener('change', missingCheckbox._changeHandler);
            
            // Create new event handler
            const changeHandler = function() {
                console.log(`Checkbox ${productId} changed to:`, this.checked);
                if (this.checked) {
                    instancesDiv.style.display = 'none';
                    // Alle Datumseingaben in diesem Produkt leeren
                    const dateInputs = instancesDiv.querySelectorAll('input[type="date"]');
                    dateInputs.forEach(input => input.value = '');
                    console.log(`Hidden instances for ${productId}`);
                } else {
                    instancesDiv.style.display = 'block';
                    console.log(`Showed instances for ${productId}`);
                }
            };
            
            // Store the handler reference and add the event listener
            missingCheckbox._changeHandler = changeHandler;
            missingCheckbox.addEventListener('change', changeHandler);
            
            // Set initial state based on existing data
            if (missingCheckbox.checked) {
                instancesDiv.style.display = 'none';
            } else {
                instancesDiv.style.display = 'block';
            }
        }
    });
}

function updateNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const finishBtn = document.getElementById('finishBtn');
    const progressText = document.getElementById('progressText');
    
    prevBtn.disabled = currentCompartment === 0;
    
    if (currentCompartment >= ALL_COMPARTMENTS.length - 1) {
        nextBtn.style.display = 'none';
        finishBtn.style.display = 'inline-block';
    } else {
        nextBtn.style.display = 'inline-block';
        finishBtn.style.display = 'none';
    }
    
    progressText.textContent = `Fach ${currentCompartment + 1} von ${ALL_COMPARTMENTS.length}`;
}

function saveCurrentData() {
    const compartment = ALL_COMPARTMENTS[currentCompartment];
    if (!compartment || !compartment.products) return;
    
    compartment.products.forEach((product, idx) => {
        const productId = `${currentCompartment}_${idx}`;
        const missingCheckbox = document.getElementById(`missing_${productId}`);
        
        // Verwende echte IDs aus der Datenbank, falls vorhanden
        const realCompartmentId = compartment.id || currentCompartment;
        const realProductId = product.product_id || product.id || idx;
        
        if (!inspectionData[realCompartmentId]) {
            inspectionData[realCompartmentId] = { products: {} };
        }
        
        inspectionData[realCompartmentId].products[realProductId] = {
            missing: missingCheckbox ? missingCheckbox.checked : false,
            instances: []
        };
        
        if (!missingCheckbox || !missingCheckbox.checked) {
            for (let i = 0; i < product.quantity; i++) {
                const dateInput = document.getElementById(`date_${productId}_${i}`);
                if (dateInput && dateInput.value) {
                    inspectionData[realCompartmentId].products[realProductId].instances.push({
                        expiry_date: dateInput.value,
                        status: 'ok'
                    });
                }
            }
        }
    });
    
    console.log('Current data saved:', inspectionData);
}

function finishInspection() {
    saveCurrentData();
    
    // Aktuelles Datum im MySQL-Format
    const now = new Date();
    const mysqlDateTime = now.getFullYear() + '-' + 
                         String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(now.getDate()).padStart(2, '0') + ' ' + 
                         String(now.getHours()).padStart(2, '0') + ':' + 
                         String(now.getMinutes()).padStart(2, '0') + ':' + 
                         String(now.getSeconds()).padStart(2, '0');
    
    // Sende Daten an Server
    const submissionData = {
        vehicle_id: VEHICLE_ID,
        inspection_data: inspectionData,
        started_at: mysqlDateTime
    };
    
    console.log('Submitting inspection data:', submissionData);
    
    // Teste zuerst mit der Test-API
    fetch('api/save-inspection-test.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(submissionData),
        credentials: 'same-origin'  // Wichtig für Session-Cookies
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Raw response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response from server');
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('Test-Kontrolle erfolgreich gespeichert!');
            // Jetzt versuche die echte API
            return submitToRealAPI(submissionData);
        } else {
            alert('Fehler beim Test-Speichern: ' + (data.message || 'Unbekannt'));
        }
    })
    .catch(error => {
        console.error('Detailed error:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        alert('Verbindungsfehler beim Test-Speichern: ' + error.message);
    });
}

function submitToRealAPI(submissionData) {
    return fetch('api/save-inspection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(submissionData),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Real API Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Real API Raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Echte Kontrolle erfolgreich gespeichert!');
                window.location.href = 'index.php?page=dashboard';
            } else {
                alert('Echte API Fehler: ' + (data.message || 'Unbekannt'));
            }
        } catch (e) {
            console.error('Real API JSON parse error:', e);
            alert('Echte API: Ungültige Antwort vom Server');
        }
    })
    .catch(error => {
        console.error('Real API Error:', error);
        alert('Echte API Verbindungsfehler: ' + error.message);
    });
}

// Event Listeners
document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentCompartment > 0) {
        saveCurrentData();
        renderCompartment(currentCompartment - 1);
    }
});

document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentCompartment < ALL_COMPARTMENTS.length - 1) {
        saveCurrentData();
        renderCompartment(currentCompartment + 1);
    }
});

document.getElementById('finishBtn').addEventListener('click', finishInspection);

// Initialize
if (ALL_COMPARTMENTS.length > 0) {
    renderCompartment(0);
} else {
    document.getElementById('inspection-content').innerHTML = '<p>Keine Fächer gefunden!</p>';
}
</script>

<style>
.inspection-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.inspection-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.inspection-content {
    min-height: 400px;
    margin-bottom: 30px;
}

.compartment-view {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}

.products-list {
    margin-top: 20px;
}

.product-item {
    padding: 15px;
    margin: 10px 0;
    border: 1px solid #eee;
    border-radius: 5px;
    background: #fafafa;
}

.instances {
    margin-top: 10px;
}

.instance {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.inspection-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
