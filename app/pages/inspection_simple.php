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
const ALL_COMPARTMENTS = <?= json_encode($allCompartments, JSON_UNESCAPED_UNICODE) ?>;

console.log('=== SIMPLE INSPECTION ===');
console.log('Vehicle ID:', VEHICLE_ID);
console.log('Compartments:', ALL_COMPARTMENTS);
console.log('Compartments count:', ALL_COMPARTMENTS.length);

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
            html += `
                <div class="product-item">
                    <h3>${product.product_name}</h3>
                    <p>Sollmenge: ${product.quantity}</p>
                    
                    <label>
                        <input type="checkbox" id="missing_${productId}"> 
                        Fehlt komplett
                    </label>
                    
                    <div class="instances">
            `;
            
            for (let i = 0; i < product.quantity; i++) {
                html += `
                    <div class="instance">
                        <label>Stück ${i + 1} Ablaufdatum:</label>
                        <input type="date" id="date_${productId}_${i}">
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
    
    // Update navigation
    currentCompartment = index;
    updateNavigation();
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
        
        inspectionData[productId] = {
            missing: missingCheckbox ? missingCheckbox.checked : false,
            instances: []
        };
        
        if (!missingCheckbox || !missingCheckbox.checked) {
            for (let i = 0; i < product.quantity; i++) {
                const dateInput = document.getElementById(`date_${productId}_${i}`);
                if (dateInput && dateInput.value) {
                    inspectionData[productId].instances[i] = {
                        expiry_date: dateInput.value
                    };
                }
            }
        }
    });
    
    console.log('Current data saved:', inspectionData);
}

function finishInspection() {
    saveCurrentData();
    
    // Sende Daten an Server
    fetch('api/save-inspection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            vehicle_id: VEHICLE_ID,
            inspection_data: inspectionData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Kontrolle erfolgreich gespeichert!');
            window.location.href = 'dashboard.php';
        } else {
            alert('Fehler beim Speichern: ' + (data.message || 'Unbekannt'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Verbindungsfehler beim Speichern.');
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
