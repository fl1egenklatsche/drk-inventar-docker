/**
 * Haupt-JavaScript für Medizinprodukt-Verwaltung
 * DRK Stadtverband Haltern am See e.V.
 */

const App = {
    // Initialisierung
    init() {
        this.setupEventListeners();
        this.setupMobileNavigation();
        this.setupDarkMode();
        this.setupForms();
        this.setupToasts();
        this.setupModals();
        this.checkConnection();
    },

    // Event Listeners einrichten
    setupEventListeners() {
        // Menu Toggle
        $('#menuToggle').on('click', () => {
            this.toggleSidebar();
        });

        // Sidebar Overlay
        $('#sidebarOverlay').on('click', () => {
            this.closeSidebar();
        });

        // Dark Mode Toggle
        $('#darkModeToggle').on('click', () => {
            this.toggleDarkMode();
        });

        // AJAX Setup
        $.ajaxSetup({
            beforeSend: () => {
                this.showLoading();
            },
            complete: () => {
                this.hideLoading();
            },
            error: (xhr, status, error) => {
                this.showToast('Fehler bei der Verbindung zum Server', 'error');
                console.error('AJAX Error:', xhr, status, error);
            }
        });

        // Prevent form double submission
        $('form').on('submit', function() {
            $(this).find('button[type="submit"]').prop('disabled', true);
            setTimeout(() => {
                $(this).find('button[type="submit"]').prop('disabled', false);
            }, 3000);
        });

        // Auto-resize textareas
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    },

    // Mobile Navigation
    setupMobileNavigation() {
        let startY = 0;
        let currentY = 0;
        let isScrolling = false;

        // Touch events für bessere mobile Navigation
        $(document).on('touchstart', (e) => {
            startY = e.touches[0].clientY;
            isScrolling = false;
        });

        $(document).on('touchmove', (e) => {
            currentY = e.touches[0].clientY;
            if (Math.abs(currentY - startY) > 10) {
                isScrolling = true;
            }
        });

        // Sidebar auto-close bei großen Screens
        $(window).on('resize', () => {
            if ($(window).width() > 768) {
                this.closeSidebar();
            }
        });

        // Escape key für Sidebar schließen
        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeSidebar();
                this.closeModal();
            }
        });
    },

    // Sidebar Toggle
    toggleSidebar() {
        const sidebar = $('#sidebar');
        const overlay = $('#sidebarOverlay');
        const toggle = $('#menuToggle');
        
        if (sidebar.hasClass('open')) {
            this.closeSidebar();
        } else {
            sidebar.addClass('open');
            overlay.addClass('show');
            toggle.addClass('active');
            $('body').css('overflow', 'hidden');
        }
    },

    closeSidebar() {
        $('#sidebar').removeClass('open');
        $('#sidebarOverlay').removeClass('show');
        $('#menuToggle').removeClass('active');
        $('body').css('overflow', '');
    },

    // Dark Mode
    setupDarkMode() {
        const darkMode = localStorage.getItem('darkMode') === 'true';
        if (darkMode) {
            $('body').addClass('dark-mode');
        }
    },

    toggleDarkMode() {
        const body = $('body');
        const isDark = body.hasClass('dark-mode');
        
        if (isDark) {
            body.removeClass('dark-mode');
            localStorage.setItem('darkMode', 'false');
            document.cookie = 'darkMode=false; path=/; max-age=31536000';
        } else {
            body.addClass('dark-mode');
            localStorage.setItem('darkMode', 'true');
            document.cookie = 'darkMode=true; path=/; max-age=31536000';
        }
    },

    // Form Handling
    setupForms() {
        // Form validation
        $('.form-input, .form-select, .form-textarea').on('blur', function() {
            App.validateField($(this));
        });

        // Real-time validation für Passwörter
        $('input[type="password"]').on('input', function() {
            if ($(this).attr('id') === 'password') {
                App.validatePassword($(this));
            }
        });

        // File upload handling
        $('input[type="file"]').on('change', function() {
            App.handleFileUpload($(this));
        });
    },

    validateField($field) {
        const value = $field.val().trim();
        const required = $field.prop('required');
        const type = $field.attr('type');
        
        let isValid = true;
        let message = '';

        if (required && !value) {
            isValid = false;
            message = 'Dieses Feld ist erforderlich.';
        } else if (type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            message = 'Bitte gib eine gültige E-Mail-Adresse ein.';
        }

        this.showFieldValidation($field, isValid, message);
        return isValid;
    },

    validatePassword($field) {
        const password = $field.val();
        const requirements = {
            length: password.length >= 10,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
        };

        let messages = [];
        if (!requirements.length) messages.push('Mindestens 10 Zeichen');
        if (!requirements.uppercase) messages.push('Großbuchstabe');
        if (!requirements.lowercase) messages.push('Kleinbuchstabe');
        if (!requirements.number) messages.push('Ziffer');
        if (!requirements.special) messages.push('Sonderzeichen');

        const isValid = Object.values(requirements).every(req => req);
        const message = messages.length > 0 ? 'Fehlt: ' + messages.join(', ') : '';

        this.showFieldValidation($field, isValid, message);
        return isValid;
    },

    showFieldValidation($field, isValid, message) {
        $field.removeClass('is-valid is-invalid');
        $field.siblings('.form-error').remove();

        if (!isValid && message) {
            $field.addClass('is-invalid');
            $field.after(`<div class="form-error">${message}</div>`);
        } else if (isValid && $field.val().trim()) {
            $field.addClass('is-valid');
        }
    },

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    handleFileUpload($input) {
        const file = $input[0].files[0];
        if (!file) return;

        // Dateigröße prüfen (5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showToast('Datei ist zu groß. Maximum: 5MB', 'error');
            $input.val('');
            return;
        }

        // Dateityp prüfen
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            this.showToast('Nur JPG und PNG Dateien sind erlaubt', 'error');
            $input.val('');
            return;
        }

        // Preview anzeigen
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = $input.siblings('.image-preview');
                if (preview.length) {
                    preview.html(`<img src="${e.target.result}" alt="Vorschau" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`);
                }
            };
            reader.readAsDataURL(file);
        }
    },

    // Loading Indicator
    showLoading(message = 'Wird geladen...') {
        $('#loadingIndicator').find('span').text(message);
        $('#loadingIndicator').addClass('show');
    },

    hideLoading() {
        $('#loadingIndicator').removeClass('show');
    },

    // Toast Notifications
    setupToasts() {
        // Auto-remove toasts after 5 seconds
        $(document).on('click', '.toast', function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    },

    showToast(message, type = 'info', duration = 5000) {
        const toast = $(`
            <div class="toast ${type} fade-in">
                <div class="toast-content">
                    ${message}
                </div>
            </div>
        `);

        $('#toastContainer').append(toast);

        // Auto-remove
        setTimeout(() => {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, duration);
    },

    // Modal Handling
    setupModals() {
        // Close modal on background click
        $('.modal').on('click', function(e) {
            if (e.target === this) {
                App.closeModal();
            }
        });

        // Confirmation modal
        $('#confirmCancel').on('click', () => {
            this.closeModal();
        });
    },

    showConfirmModal(title, message, callback) {
        console.log('🔴 showConfirmModal aufgerufen!', {title, message, stack: new Error().stack});
        $('#confirmTitle').text(title);
        $('#confirmMessage').text(message);
        $('#confirmModal').addClass('show');
        
        $('#confirmOk').off('click').on('click', () => {
            this.closeModal();
            if (callback) callback();
        });
    },

    closeModal() {
        $('.modal').removeClass('show');
    },

    // AJAX Helpers
    ajax(url, data = {}, method = 'POST') {
        return $.ajax({
            url: url,
            method: method,
            data: data,
            dataType: 'json'
        });
    },

    // Connection Check
    checkConnection() {
        // Prüfe Verbindung alle 30 Sekunden
        setInterval(() => {
            $.get('api/ping.php')
                .fail(() => {
                    this.showToast('Verbindung zum Server unterbrochen', 'error');
                });
        }, 30000);
    },

    // Utility Functions
    formatDate(dateString, format = 'DD.MM.YYYY') {
        if (!dateString) return '';
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        
        return format
            .replace('DD', day)
            .replace('MM', month)
            .replace('YYYY', year);
    },

    timeAgo(dateString) {
        if (!dateString) return '';
        
        const now = new Date();
        const date = new Date(dateString);
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'gerade eben';
        if (diffMins < 60) return `vor ${diffMins} Min`;
        if (diffHours < 24) return `vor ${diffHours} Std`;
        if (diffDays < 30) return `vor ${diffDays} Tagen`;
        
        return this.formatDate(dateString);
    },

    // Local Storage Helpers
    saveToStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.warn('LocalStorage not available:', e);
        }
    },

    loadFromStorage(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.warn('LocalStorage error:', e);
            return defaultValue;
        }
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Dashboard specific functions
const Dashboard = {
    init() {
        this.loadExpiringProducts();
        this.loadLastInspections();
        this.setupRefresh();
    },

    loadExpiringProducts() {
        App.ajax('api/expiring-products.php', {}, 'GET')
            .done((response) => {
                if (response.success) {
                    this.renderExpiringProducts(response.data);
                }
            });
    },

    loadLastInspections() {
        App.ajax('api/last-inspections.php', {}, 'GET')
            .done((response) => {
                if (response.success) {
                    this.renderLastInspections(response.data);
                }
            });
    },

    renderExpiringProducts(products) {
        let html = '';
        
        if (products.length === 0) {
            html = '<div class="text-center p-3"><p>Keine ablaufenden Produkte in den nächsten 4 Wochen.</p></div>';
        } else {
            products.forEach(product => {
                let expiryClass = '';
                if (product.days_until_expiry < 0) expiryClass = 'expired';
                else if (product.days_until_expiry <= 7) expiryClass = 'expiring-soon';
                else expiryClass = 'expiring-later';
                
                html += `
                    <div class="expiring-product">
                        <div class="product-info">
                            <div class="product-name">${product.product_name}</div>
                            <div class="product-location">${product.vehicle_name} › ${product.container_name} › ${product.compartment_name}</div>
                        </div>
                        <div class="expiry-info">
                            <div class="expiry-date ${expiryClass}">${App.formatDate(product.expiry_date)}</div>
                            <div class="days-remaining ${expiryClass}">
                                ${product.days_until_expiry < 0 ? 'Abgelaufen' : `${product.days_until_expiry} Tage`}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#expiring-products-list').html(html);
    },

    renderLastInspections(inspections) {
        let html = '';
        
        inspections.forEach(inspection => {
            const timeAgo = inspection.completed_at ? App.timeAgo(inspection.completed_at) : 'Noch nie';
            const inspectorName = inspection.inspector_name || 'Unbekannt';
            
            html += `
                <div class="vehicle-card">
                    <div class="vehicle-header">
                        <h3 class="vehicle-title">${inspection.vehicle_name}</h3>
                    </div>
                    <div class="vehicle-info">
                        <div class="last-inspection">
                            <span class="inspection-date">Letzte Kontrolle: ${timeAgo}</span>
                            <span class="inspector-name">von ${inspectorName}</span>
                        </div>
                        <a href="?page=inspection&vehicle_id=${inspection.vehicle_id}" class="btn btn-primary btn-block">
                            Kontrolle starten
                        </a>
                    </div>
                </div>
            `;
        });
        
        $('#last-inspections-list').html(html);
    },

    setupRefresh() {
        // Auto-refresh every 5 minutes
        setInterval(() => {
            this.loadExpiringProducts();
            this.loadLastInspections();
        }, 5 * 60 * 1000);
    }
};

// Reports specific functions
const Reports = {
    init() {
        this.setupDatePickers();
        this.setupExportButtons();
    },

    setupDatePickers() {
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        
        $('#date-from').val(thirtyDaysAgo.toISOString().split('T')[0]);
        $('#date-to').val(today.toISOString().split('T')[0]);
    },

    setupExportButtons() {
        $('#export-pdf').on('click', () => {
            this.exportReport('pdf');
        });
        
        $('#export-excel').on('click', () => {
            this.exportReport('excel');
        });
    },

    exportReport(format) {
        const dateFrom = $('#date-from').val();
        const dateTo = $('#date-to').val();
        const reportType = $('#report-type').val();
        
        if (!dateFrom || !dateTo) {
            App.showToast('Bitte wähle einen Zeitraum aus', 'error');
            return;
        }
        
        const params = new URLSearchParams({
            format: format,
            date_from: dateFrom,
            date_to: dateTo,
            report_type: reportType
        });
        
        window.open(`api/export-report.php?${params.toString()}`);
    }
};

// Global event handlers that need to be available
$(document).ready(function() {
    // Initialize the main App
    App.init();
    
    // Initialize page-specific functionality
    const page = new URLSearchParams(window.location.search).get('page');
    
    switch (page) {
        case 'dashboard':
            Dashboard.init();
            break;
        case 'reports':
            Reports.init();
            break;
        // Inspection wird jetzt durch inspection.js behandelt
    }
});