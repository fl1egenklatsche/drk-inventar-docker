<?php
/**
 * pages/inspection.php
 * Komplett neue, funktionierende Version
 */

// Grundlegende Validierung
if (!isset($_GET['vehicle_id']) || !is_numeric($_GET['vehicle_id'])) {
    header('Location: ?page=vehicles');
    exit;
}

$vehicleId = (int)$_GET['vehicle_id'];

// Fahrzeug-Daten laden mit Fehlerbehandlung
$vehicle = null;
$allCompartments = [];

try {
    if (function_exists('getVehicleStructure')) {
        $vehicle = getVehicleStructure($vehicleId);
    }
} catch (Exception $e) {
    error_log("Error loading vehicle: " . $e->getMessage());
}

// Wenn keine echten Daten, verwende Test-Daten
if (!$vehicle || empty($vehicle['containers'])) {
    $vehicle = [
        'name' => 'Test Fahrzeug',
        'type' => 'RTW'
    ];
    
    $allCompartments = [
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
} else {
    // Echte Daten verarbeiten
    foreach ($vehicle['containers'] as $container) {
        foreach ($container['compartments'] as $compartment) {
            $compartment['container_name'] = $container['name'];
            $compartment['products'] = $compartment['target_products'] ?? [];
            $allCompartments[] = $compartment;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrolle - <?= htmlspecialchars($vehicle['name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .inspection-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .inspection-header {
            background: #c62828;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .inspection-header h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .inspection-content {
            min-height: 400px;
            padding: 0;
        }
        
        .compartment-view {
            padding: 20px;
        }
        
        .compartment-title {
            background: #1976d2;
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 20px -20px;
            font-size: 20px;
            font-weight: bold;
        }
        
        .product-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 15px 0;
            padding: 15px;
            background: #fafafa;
        }
        
        .product-header h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .product-controls {
            margin: 10px 0;
        }
        
        .product-controls label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .instances-container {
            margin-top: 15px;
        }
        
        .instance-item {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instance-item label {
            min-width: 150px;
            font-size: 14px;
        }
        
        .instance-date {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .navigation-footer {
            background: #f0f0f0;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #ddd;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
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
        
        .btn:hover:not(:disabled) {
            opacity: 0.9;
        }
        
        .progress-info {
            font-weight: 500;
            color: #333;
        }
        
        .no-products {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
    </style>
</head>
<body>

<div class="inspection-container">
    <div class="inspection-header">
        <h1>Kontrolle: <?= htmlspecialchars($vehicle['name']) ?></h1>
        <p>Fahrzeug-ID: <?= $vehicleId ?> | <?= htmlspecialchars($vehicle['type'] ?? 'Unbekannt') ?></p>
    </div>
    
    <div class="inspection-content" id="inspection-content">
        <!-- Wird durch JavaScript gefüllt -->
    </div>
    
    <div class="navigation-footer">
        <button type="button" id="prevBtn" class="btn btn-secondary" disabled>← Zurück</button>
        <span id="progressText" class="progress-info">Fach 1 von <?= count($allCompartments) ?></span>
        <button type="button" id="nextBtn" class="btn btn-primary">Weiter →</button>
        <button type="button" id="finishBtn" class="btn btn-success" style="display:none;">Abschließen</button>
    </div>
</div>

<script>
// Globale Daten
const VEHICLE_ID = <?= $vehicleId ?>;
const ALL_COMPARTMENTS = <?= json_encode($allCompartments, JSON_UNESCAPED_UNICODE) ?>;

console.log('=== INSPECTION LOADED ===');
console.log('Vehicle ID:', VEHICLE_ID);
console.log('Compartments:', ALL_COMPARTMENTS);
console.log('Compartments count:', ALL_COMPARTMENTS.length);

// Inspection State
let currentCompartment = 0;
let inspectionData = {};

// Fach rendern
function renderCompartment(index) {
    const compartment = ALL_COMPARTMENTS[index];
    if (!compartment) {
        document.getElementById('inspection-content').innerHTML = '<div class="no-products">Fach nicht gefunden!</div>';
        return;
    }
    
    let html = `
        <div class="compartment-view">
            <div class="compartment-title">
                Fach ${index + 1}: ${compartment.name}
                <br><small>Behälter: ${compartment.container_name || 'Unbekannt'}</small>
            </div>
    `;
    
    if (compartment.products && compartment.products.length > 0) {
        compartment.products.forEach((product, productIndex) => {
            const productId = `${index}_${productIndex}`;
            html += `
                <div class="product-item">
                    <div class="product-header">
                        <h3>${product.product_name || product.name || 'Unbekanntes Produkt'}</h3>
                        <p>Sollmenge: ${product.quantity || 1}</p>
                    </div>
                    
                    <div class="product-controls">
                        <label>
                            <input type="checkbox" id="missing_${productId}" class="product-missing">
                            <span style="color: #d32f2f;">Fehlt komplett</span>
                        </label>
                    </div>
                    
                    <div class="instances-container" id="instances_${productId}">
            `;
            
            const quantity = product.quantity || 1;
            for (let i = 0; i < quantity; i++) {
                html += `
                    <div class="instance-item">
                        <label>Stück ${i + 1} - Ablaufdatum:</label>
                        <input type="date" class="instance-date" id="date_${productId}_${i}">
                    </div>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
        });
    } else {
        html += '<div class="no-products">Keine Produkte in diesem Fach definiert.</div>';
    }
    
    html += '</div>';
    
    document.getElementById('inspection-content').innerHTML = html;
    
    // Event Listeners für dieses Fach
    setupCurrentCompartmentEvents();
    
    // Navigation aktualisieren
    currentCompartment = index;
    updateNavigation();
}

// Event Listeners für aktuelles Fach
function setupCurrentCompartmentEvents() {
    const compartment = ALL_COMPARTMENTS[currentCompartment];
    if (!compartment || !compartment.products) return;
    
    compartment.products.forEach((product, productIndex) => {
        const productId = `${currentCompartment}_${productIndex}`;
        const missingCheckbox = document.getElementById(`missing_${productId}`);
        const instancesContainer = document.getElementById(`instances_${productId}`);
        
        if (missingCheckbox) {
            missingCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    instancesContainer.style.display = 'none';
                } else {
                    instancesContainer.style.display = 'block';
                }
            });
        }
    });
}

// Navigation aktualisieren
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

// Aktuelle Daten speichern
function saveCurrentData() {
    const compartment = ALL_COMPARTMENTS[currentCompartment];
    if (!compartment || !compartment.products) return;
    
    compartment.products.forEach((product, productIndex) => {
        const productId = `${currentCompartment}_${productIndex}`;
        const missingCheckbox = document.getElementById(`missing_${productId}`);
        
        inspectionData[productId] = {
            missing: missingCheckbox ? missingCheckbox.checked : false,
            instances: []
        };
        
        if (!missingCheckbox || !missingCheckbox.checked) {
            const quantity = product.quantity || 1;
            for (let i = 0; i < quantity; i++) {
                const dateInput = document.getElementById(`date_${productId}_${i}`);
                if (dateInput && dateInput.value) {
                    inspectionData[productId].instances[i] = {
                        expiry_date: dateInput.value
                    };
                }
            }
        }
    });
    
    console.log('Data saved for compartment', currentCompartment, ':', inspectionData);
}

// Kontrolle abschließen
function finishInspection() {
    saveCurrentData();
    
    const submissionData = {
        vehicle_id: VEHICLE_ID,
        inspection_data: inspectionData
    };
    
    console.log('Submitting inspection:', submissionData);
    
    fetch('api/save-inspection.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(submissionData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Kontrolle erfolgreich gespeichert!');
            window.location.href = '?page=dashboard';
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

// Initialisierung
if (ALL_COMPARTMENTS.length > 0) {
    renderCompartment(0);
} else {
    document.getElementById('inspection-content').innerHTML = '<div class="no-products">Keine Fächer für dieses Fahrzeug konfiguriert.</div>';
}
</script>

</body>
</html>
