/**
 * DRK Medical Equipment Inspection System - JavaScript
 * Handles inspection workflow and product instance management
 */

const InspectionAdvanced = {
    // Core properties
    inspectionData: {},
    currentDateEntries: {},
    currentCompartment: 0,
    totalCompartments: 0,
    
    // Initialize the inspection system
    init() {
        console.log('InspectionAdvanced.init() called');
        
        if (typeof ALL_COMPARTMENTS === 'undefined' || typeof VEHICLE_ID === 'undefined') {
            console.error('Required globals not found: ALL_COMPARTMENTS, VEHICLE_ID');
            this.showMessage('Fehler: Fahrzeugdaten konnten nicht geladen werden.', 'error');
            return;
        }
        
        if (!ALL_COMPARTMENTS || !Array.isArray(ALL_COMPARTMENTS)) {
            console.error('ALL_COMPARTMENTS is not an array:', ALL_COMPARTMENTS);
            this.showMessage('Fehler: Ungültige Fächerdaten.', 'error');
            return;
        }
        
        this.inspectionData = {};
        this.currentDateEntries = {};
        this.totalCompartments = ALL_COMPARTMENTS.length;
        
        console.log('Total compartments found:', this.totalCompartments);
        
        // Start with first compartment
        if (this.totalCompartments > 0) {
            this.renderCompartment(0);
        } else {
            this.showMessage('Keine Fächer gefunden für dieses Fahrzeug.', 'warning');
            $('.inspection-content').html('<div class="no-data">Keine Fächer für dieses Fahrzeug konfiguriert.</div>');
        }
        
        // Set up event handlers
        this.setupEventHandlers();
        
        console.log('InspectionAdvanced initialized with', this.totalCompartments, 'compartments');
    },
    
    // Set up all event handlers
    setupEventHandlers() {
        // Previous/Next buttons
        $(document).on('click', '.btn-previous', () => {
            if (this.currentCompartment > 0) {
                this.saveCurrentData();
                this.renderCompartment(this.currentCompartment - 1);
            }
        });
        
        $(document).on('click', '.btn-next', () => {
            if (this.currentCompartment < this.totalCompartments - 1) {
                this.saveCurrentData();
                this.renderCompartment(this.currentCompartment + 1);
            } else {
                this.finishInspection();
            }
        });
        
        // Finish inspection button
        $(document).on('click', '.btn-finish', () => {
            this.finishInspection();
        });
        
        // Instance date inputs
        $(document).on('change', '.instance-date', (e) => {
            const $input = $(e.target);
            const productId = $input.data('product-id');
            const instanceIndex = $input.data('instance-index');
            const newDate = $input.val();
            
            this.updateInstanceDate(productId, instanceIndex, newDate);
        });
        
        // Missing checkboxes
        $(document).on('change', '.product-missing', (e) => {
            const $checkbox = $(e.target);
            const productId = $checkbox.data('product-id');
            const isMissing = $checkbox.is(':checked');
            
            this.updateProductMissing(productId, isMissing);
        });
    },
    
    // Render a specific compartment
    renderCompartment(compartmentIndex) {
        if (!ALL_COMPARTMENTS[compartmentIndex]) {
            console.error('Compartment not found:', compartmentIndex);
            return;
        }
        
        this.currentCompartment = compartmentIndex;
        const compartment = ALL_COMPARTMENTS[compartmentIndex];
        
        let html = `
            <div class="compartment-header" style="padding: 20px; background: #c62828; color: white; margin-bottom: 20px;">
                <h2 style="margin: 0 0 5px 0; font-size: 24px;">Fach ${compartmentIndex + 1}: ${compartment.name}</h2>
                <div class="progress-info" style="font-size: 14px; opacity: 0.9;">
                    Fach ${compartmentIndex + 1} von ${this.totalCompartments}
                </div>
            </div>
        `;
        
        if (compartment.products && compartment.products.length > 0) {
            html += '<div class="products-list" style="padding: 0 20px;">';
            
            compartment.products.forEach((product, productIndex) => {
                html += this.renderProduct(product, productIndex);
            });
            
            html += '</div>';
        } else {
            html += '<div class="no-products" style="padding: 40px 20px; text-align: center; color: #666; font-style: italic;">Keine Produkte in diesem Fach definiert.</div>';
        }
        
        // Navigation buttons
        html += this.renderNavigationButtons();
        
        $('.inspection-content').html(html);
        
        // Load existing data for this compartment
        this.loadCompartmentData(compartmentIndex);
    },
    
    // Render a single product
    renderProduct(product, productIndex) {
        const productId = `${this.currentCompartment}_${productIndex}`;
        const savedData = this.inspectionData[productId] || {};
        
        // Verwende product_name aus der Datenbank (nicht nur "name")
        const productName = product.product_name || product.name || 'Unbekanntes Produkt';
        const productQuantity = product.quantity || 1;
        
        let html = `
            <div class="product-item" data-product-id="${productId}" style="margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background: white;">
                <div class="product-header" style="margin-bottom: 10px;">
                    <h3 style="margin: 0 0 5px 0; color: #333;">${productName}</h3>
                    <div class="product-info">
                        <span class="quantity" style="color: #666; font-size: 14px;">Sollmenge: ${productQuantity}</span>
                    </div>
                </div>
        `;
        
        // Missing checkbox
        html += `
            <div class="product-controls" style="margin: 10px 0;">
                <label class="missing-control" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" class="product-missing" data-product-id="${productId}" 
                           ${savedData.missing ? 'checked' : ''}
                           style="width: 16px; height: 16px;">
                    <span style="font-weight: 500; color: #d32f2f;">Fehlt komplett</span>
                </label>
            </div>
        `;
        
        // Instance inputs (only if not missing)
        if (!savedData.missing) {
            html += '<div class="instances-container" style="margin-top: 15px;">';
            
            for (let i = 0; i < productQuantity; i++) {
                const instanceDate = savedData.instances && savedData.instances[i] 
                    ? savedData.instances[i].expiry_date 
                    : '';
                
                html += `
                    <div class="instance-item" style="margin: 8px 0; display: flex; align-items: center; gap: 10px;">
                        <label style="min-width: 150px;">Stück ${i + 1} - Ablaufdatum:</label>
                        <input type="date" 
                               class="instance-date" 
                               data-product-id="${productId}" 
                               data-instance-index="${i}"
                               value="${instanceDate}"
                               style="padding: 5px; border: 1px solid #ccc; border-radius: 3px;">
                    </div>
                `;
            }
            
            html += '</div>';
        }
        
        html += '</div>';
        return html;
    },
    
    // Render navigation buttons
    renderNavigationButtons() {
        let html = '<div class="navigation-buttons" style="padding: 20px; background: #f5f5f5; border-top: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">';
        
        // Previous button
        if (this.currentCompartment > 0) {
            html += '<button type="button" class="btn btn-secondary btn-previous" style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">← Vorheriges Fach</button>';
        } else {
            html += '<div></div>'; // Spacer
        }
        
        // Progress indicator
        html += `<span style="font-weight: 500; color: #333;">Fach ${this.currentCompartment + 1} von ${this.totalCompartments}</span>`;
        
        // Next/Finish button
        if (this.currentCompartment < this.totalCompartments - 1) {
            html += '<button type="button" class="btn btn-primary btn-next" style="padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Nächstes Fach →</button>';
        } else {
            html += '<button type="button" class="btn btn-success btn-finish" style="padding: 12px 24px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Kontrolle abschließen</button>';
        }
        
        html += '</div>';
        return html;
    },
    
    // Update instance expiry date
    updateInstanceDate(productId, instanceIndex, newDate) {
        if (!this.inspectionData[productId]) {
            this.inspectionData[productId] = { instances: [] };
        }
        
        if (!this.inspectionData[productId].instances) {
            this.inspectionData[productId].instances = [];
        }
        
        // Initialize instance if needed
        if (!this.inspectionData[productId].instances[instanceIndex]) {
            this.inspectionData[productId].instances[instanceIndex] = {};
        }
        
        this.inspectionData[productId].instances[instanceIndex].expiry_date = newDate;
        
        console.log('Updated instance date:', productId, instanceIndex, newDate);
    },
    
    // Update product missing status
    updateProductMissing(productId, isMissing) {
        if (!this.inspectionData[productId]) {
            this.inspectionData[productId] = {};
        }
        
        this.inspectionData[productId].missing = isMissing;
        
        // Toggle instances container visibility
        const $productItem = $(`.product-item[data-product-id="${productId}"]`);
        const $instancesContainer = $productItem.find('.instances-container');
        
        if (isMissing) {
            $instancesContainer.hide();
            // Clear instance data when marked as missing
            delete this.inspectionData[productId].instances;
        } else {
            $instancesContainer.show();
        }
        
        console.log('Updated missing status:', productId, isMissing);
    },
    
    // Save current compartment data
    saveCurrentData() {
        console.log('Saving current compartment data for compartment:', this.currentCompartment);
        // Data is already saved in real-time through event handlers
    },
    
    // Load data for a compartment
    loadCompartmentData(compartmentIndex) {
        // Data is already loaded from this.inspectionData during rendering
        console.log('Loading data for compartment:', compartmentIndex);
    },
    
    // Finish the inspection
    finishInspection() {
        // Save current data first
        this.saveCurrentData();
        
        if (Object.keys(this.inspectionData).length === 0) {
            this.showMessage('Keine Daten zur Speicherung vorhanden.', 'warning');
            return;
        }
        
        // Show loading
        this.showMessage('Kontrolle wird gespeichert...', 'info');
        
        // Prepare data for submission
        const submissionData = {
            vehicle_id: VEHICLE_ID,
            inspection_data: this.inspectionData,
            compartments: ALL_COMPARTMENTS
        };
        
        // Submit via AJAX
        $.ajax({
            url: 'api/save-inspection.php',
            method: 'POST',
            data: JSON.stringify(submissionData),
            contentType: 'application/json',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showMessage('Kontrolle erfolgreich gespeichert!', 'success');
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    this.showMessage('Fehler beim Speichern: ' + (response.message || 'Unbekannter Fehler'), 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX Error:', status, error);
                this.showMessage('Verbindungsfehler beim Speichern der Kontrolle.', 'error');
            }
        });
    },
    
    // Show user messages
    showMessage(message, type = 'info') {
        // Remove existing messages
        $('.message').remove();
        
        const messageClass = `message message-${type}`;
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        
        $('.inspection-content').prepend(messageHtml);
        
        // Auto-remove after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(() => {
                $('.message').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }
};

// Initialize when document is ready
$(document).ready(function() {
    InspectionAdvanced.init();
});
