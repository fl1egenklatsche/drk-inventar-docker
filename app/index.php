<?php
/**
 * Medizinprodukt-Verwaltungssystem
 * DRK Stadtverband Haltern am See e.V.
 * 
 * Haupteinstiegspunkt der Anwendung - Headers-Already-Sent Fix
 */

// Output Buffering starten um Headers-Probleme zu vermeiden
ob_start();

require_once 'includes/config.php';
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Authentifizierung prüfen
if (!isLoggedIn() && (!isset($_GET['page']) || $_GET['page'] !== 'login')) {
    // Output Buffer leeren und Redirect senden
    ob_end_clean();
    header('Location: index.php?page=login');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$darkMode = $_COOKIE['darkMode'] ?? 'false';

// Logout-Handling VOR HTML-Output
if ($page === 'logout') {
    session_destroy();
    ob_end_clean();
    header('Location: index.php?page=login');
    exit;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Medizinprodukt-Verwaltung - DRK Haltern</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#dc143c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="DRK MedVerwaltung">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/apple-touch-icon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/mobile.css?v=<?= time() ?>">
    <?php if($darkMode === 'true'): ?>
    <link rel="stylesheet" href="assets/css/dark-mode.css?v=<?= time() ?>">
    <?php endif; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/jquery-3.6.0.min.js?v=<?= time() ?>"></script>
    <?php if (isLoggedIn()): ?>
    <script>
        window.CSRF_TOKEN = '<?= generateCSRFToken() ?>';
        // CSRF-Token automatisch an alle AJAX-Requests anhängen
        $(document).ready(function() {
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-CSRF-Token', window.CSRF_TOKEN);
                }
            });
        });
    </script>
    <?php endif; ?>
    <script src="assets/js/main.js?v=<?= time() ?>"></script>
    
    <!-- Fix für Sidebar Toggle -->
    <style>
    /* Sidebar Active State */
    .sidebar.active {
        transform: translateX(0) !important;
    }
    
    .sidebar-overlay.active {
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
    }
    
    /* Menu Toggle Animation */
    .menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px) !important;
    }
    
    .menu-toggle.active span:nth-child(2) {
        opacity: 0 !important;
    }
    
    .menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px) !important;
    }
    
    /* Ensure sidebar starts hidden on mobile */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%) !important;
            transition: transform 0.3s ease !important;
        }
        
        .sidebar-overlay {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
            transition: all 0.3s ease !important;
        }
    }
    </style>
</head>
<body class="<?= $darkMode === 'true' ? 'dark-mode' : '' ?>">
    
    <?php if (isLoggedIn()): ?>
    <!-- Mobile Navigation Header -->
    <header class="mobile-header">
        <button class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h1 class="header-title">DRK Medizinprodukt-Verwaltung</h1>
        <button class="dark-mode-toggle" id="darkModeToggle">
            <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m21 12.79c-.85.23-1.74.35-2.65.35c-6.89 0-12.5-5.61-12.5-12.5c0-.91.12-1.8.35-2.65c-3.4 1.47-5.78 4.91-5.78 8.9c0 5.38 4.37 9.75 9.75 9.75c3.99 0 7.43-2.38 8.9-5.78"/>
            </svg>
        </button>
    </header>

    <!-- Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/drk-logo.png" alt="DRK Logo" class="logo">
            <span class="logo-text">Medizinprodukt-<br>Verwaltung</span>
        </div>
        
        <div class="user-info">
            <span class="user-name">Hallo <?= htmlspecialchars(getCurrentUser()['full_name']) ?></span>
            <span class="user-role"><?= getCurrentUser()['role'] === 'admin' ? 'Administrator' : 'Benutzer' ?></span>
        </div>
        
        <ul class="nav-menu">
            <?php 
            $userRole = getCurrentUser()['role'];
            $showContainerInspection = in_array($userRole, ['admin', 'fahrzeugwart', 'kontrolle']);
            $isKontrolleOnly = ($userRole === 'kontrolle');
            ?>
            
            <li><a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a></li>
            
            <?php if (!$isKontrolleOnly): ?>
            <li><a href="?page=vehicles" class="<?= $page === 'vehicles' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                    <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                    <path d="M5 17h-2v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="12" y1="9" x2="12" y2="15"/>
                </svg>
                Fahrzeuge
            </a></li>
            <?php endif; ?>
            
            <?php if ($showContainerInspection): ?>
            <li><a href="?page=container_inspection_start" class="<?= in_array($page, ['container_inspection_start', 'container_inspection_overview', 'container_inspection_check']) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                Container-Prüfung
            </a></li>
            <?php endif; ?>
            
            <?php if (isAdmin() && !$isKontrolleOnly): ?>
            <li><a href="?page=management" class="<?= $page === 'management' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Produktverwaltung
            </a></li>
            <?php endif; ?>
            
            <?php if (!$isKontrolleOnly): ?>
            <li><a href="?page=reports" class="<?= $page === 'reports' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10,9 9,9 8,9"/>
                </svg>
                Berichte
            </a></li>
            <?php endif; ?>
            
            <?php if (isAdmin() && !$isKontrolleOnly): ?>
            <li><a href="?page=users" class="<?= $page === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Benutzerverwaltung
            </a></li>
            
            <li><a href="?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="m12 1l3.09 3.09L22 4l-.91 6.91L22 17l-6.91-.09L12 23l-3.09-3.09L2 20l.91-6.91L2 7l6.91.09L12 1z"/>
                </svg>
                Einstellungen
            </a></li>
            <?php endif; ?>
        </ul>
        
        <div class="sidebar-footer">
            <a href="?page=logout" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16,17 21,12 16,7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Abmelden
            </a>
        </div>
    </nav>

    <!-- Overlay für Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php
        // Seiten-spezifische Inhalte laden
        switch ($page) {
            case 'login':
                if (!isLoggedIn()) {
                    include 'pages/login.php';
                } else {
                    // Buffer leeren und redirecten
                    ob_end_clean();
                    header('Location: index.php?page=dashboard');
                    exit;
                }
                break;
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'vehicles':
                include 'pages/vehicles.php';
                break;
            case 'inspection':
                include 'pages/inspection.php';
                break;
            case 'inspection_simple':
                include 'pages/inspection_simple.php';
                break;
            case 'inspection_debug':
                include 'pages/inspection_debug.php';
                break;
            case 'container_inspection_start':
                $userRole = getCurrentUser()['role'];
                if (in_array($userRole, ['admin', 'fahrzeugwart', 'kontrolle'])) {
                    include 'pages/container_inspection_start.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            case 'container_inspection_overview':
                $userRole = getCurrentUser()['role'];
                if (in_array($userRole, ['admin', 'fahrzeugwart', 'kontrolle'])) {
                    include 'pages/container_inspection_overview.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            case 'container_inspection_check':
                $userRole = getCurrentUser()['role'];
                if (in_array($userRole, ['admin', 'fahrzeugwart', 'kontrolle'])) {
                    include 'pages/container_inspection_check.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            case 'management':
                if (isAdmin()) {
                    include 'pages/management.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            case 'reports':
                include 'pages/reports.php';
                break;
            case 'users':
                if (isAdmin()) {
                    include 'pages/users.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            case 'settings':
                if (isAdmin()) {
                    include 'pages/settings.php';
                } else {
                    include 'pages/403.php';
                }
                break;
            default:
                include 'pages/404.php';
                break;
        }
        ?>
    </main>

    <!-- Loading Indicator -->
    <div class="loading-indicator" id="loadingIndicator">
        <div class="spinner"></div>
        <span>Wird geladen...</span>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h3 id="confirmTitle">Bestätigung</h3>
            <p id="confirmMessage"></p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="confirmCancel">Abbrechen</button>
                <button class="btn btn-danger" id="confirmOk">Bestätigen</button>
            </div>
        </div>
    </div>

    <script>
        // App initialisieren
        $(document).ready(function() {
            console.log('=== MENU DIRECT FIX ===');
            
            // Direkte Menu-Toggle Implementierung (Fallback)
            $('#menuToggle').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Menu toggle clicked!');
                
                const sidebar = $('#sidebar');
                const overlay = $('#sidebarOverlay');
                const menuBtn = $(this);
                
                // Toggle active classes
                sidebar.toggleClass('active');
                overlay.toggleClass('active');
                menuBtn.toggleClass('active');
                
                console.log('Sidebar has active class:', sidebar.hasClass('active'));
                console.log('Overlay has active class:', overlay.hasClass('active'));
                
                return false;
            });
            
            // Overlay click to close
            $('#sidebarOverlay').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Overlay clicked - closing menu');
                
                $('#sidebar').removeClass('active');
                $('#sidebarOverlay').removeClass('active');
                $('#menuToggle').removeClass('active');
                
                return false;
            });
            
            // Initialize App if available
            if (typeof App !== 'undefined' && App.init) {
                try {
                    App.init();
                    console.log('App.init() called successfully');
                } catch (e) {
                    console.error('App.init() failed:', e);
                }
            } else {
                console.log('App object not available, using direct implementation');
            }
            
            console.log('=== MENU SETUP COMPLETE ===');
        });
    </script>
</body>
</html>
<?php
// Output Buffer leeren und senden
ob_end_flush();
?>