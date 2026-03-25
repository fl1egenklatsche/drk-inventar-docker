<?php
/**
 * pages/404.php
 * Seite nicht gefunden
 */
?>

<div class="error-page">
    <div class="error-content">
        <div class="error-illustration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="error-icon">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
                <line x1="11" y1="8" x2="11" y2="14"/>
                <line x1="11" y1="16" x2="11.01" y2="16"/>
            </svg>
        </div>
        
        <div class="error-details">
            <h1>404</h1>
            <h2>Seite nicht gefunden</h2>
            <p>Die angeforderte Seite konnte nicht gefunden werden. Möglicherweise wurde sie verschoben oder ist nicht mehr verfügbar.</p>
            
            <div class="error-actions">
                <a href="?page=dashboard" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9,22 9,12 15,12 15,22"/>
                    </svg>
                    Zum Dashboard
                </a>
                
                <button onclick="history.back()" class="btn btn-outline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"/>
                    </svg>
                    Zurück
                </button>
            </div>
        </div>
    </div>
    
    <div class="error-help">
        <div class="help-card">
            <h3>Hilfreiche Links</h3>
            <div class="quick-links">
                <a href="?page=dashboard" class="quick-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Dashboard
                </a>
                
                <a href="?page=vehicles" class="quick-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>
                        <path d="M5 17h-2v-6l2-5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/>
                    </svg>
                    Fahrzeuge
                </a>
                
                <a href="?page=reports" class="quick-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14,2 14,8 20,8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Berichte
                </a>
                
                <?php if (isAdmin()): ?>
                <a href="?page=management" class="quick-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Produktverwaltung
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.quick-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: var(--light-gray);
    border-radius: 8px;
    color: var(--primary-black);
    text-decoration: none;
    transition: var(--transition-fast);
    font-size: 14px;
}

.quick-link:hover {
    background: rgba(220, 20, 60, 0.1);
    color: var(--primary-red);
}

.quick-link svg {
    width: 16px;
    height: 16px;
}

@media (max-width: 768px) {
    .quick-links {
        grid-template-columns: 1fr;
    }
}
</style>
