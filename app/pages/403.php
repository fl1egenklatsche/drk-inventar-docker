<?php
/**
 * pages/403.php
 * Zugriff verweigert Seite
 */
?>

<div class="error-page">
    <div class="error-content">
        <div class="error-illustration">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="error-icon">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        
        <div class="error-details">
            <h1>403</h1>
            <h2>Zugriff verweigert</h2>
            <p>Du hast keine Berechtigung, diese Seite zu besuchen. Diese Funktion ist nur für Administratoren verfügbar.</p>
            
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
            <h3>Benötigst du Administratorrechte?</h3>
            <p>Kontaktiere einen Administrator, wenn du Zugriff auf diese Funktion benötigst.</p>
            
            <div class="help-contacts">
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span>E-Mail: admin@drk-haltern.de</span>
                </div>
                
                <div class="contact-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span>Telefon: 02364 / 12345</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
}

.error-content {
    max-width: 500px;
    margin-bottom: 40px;
}

.error-illustration {
    margin-bottom: 32px;
}

.error-icon {
    width: 120px;
    height: 120px;
    color: var(--danger-red);
    opacity: 0.8;
}

.error-details h1 {
    font-size: 72px;
    font-weight: 700;
    color: var(--danger-red);
    margin-bottom: 16px;
    line-height: 1;
}

.error-details h2 {
    font-size: 32px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 16px;
}

.error-details p {
    font-size: 18px;
    color: var(--dark-gray);
    margin-bottom: 32px;
    line-height: 1.6;
}

.error-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
}

.error-help {
    max-width: 400px;
}

.help-card {
    background: var(--primary-white);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow-light);
    text-align: left;
}

.help-card h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 12px;
}

.help-card p {
    color: var(--dark-gray);
    margin-bottom: 20px;
}

.help-contacts {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--dark-gray);
    font-size: 14px;
}

.contact-item svg {
    width: 18px;
    height: 18px;
    color: var(--primary-red);
}

@media (max-width: 768px) {
    .error-details h1 {
        font-size: 48px;
    }
    
    .error-details h2 {
        font-size: 24px;
    }
    
    .error-details p {
        font-size: 16px;
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 100%;
        max-width: 200px;
    }
}
</style>