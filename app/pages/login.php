<?php
/**
 * pages/login.php
 * Login-Seite
 */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Bitte alle Felder ausfüllen.';
    } else {
        // Rate-Limiting prüfen
        $rateCheck = checkLoginRateLimit();
        if ($rateCheck !== true) {
            $error = $rateCheck;
        } else if (login($username, $password)) {
            // Rolle-basierter Redirect
            $user = getCurrentUser();
            
            if ($user['role'] === 'kontrolle') {
                // Kontrolle-User zur Container-Prüfung
                header('Location: index.php?page=container_inspection_start');
            } elseif (in_array($user['role'], ['admin', 'fahrzeugwart'])) {
                // Admin/Fahrzeugwart zum Dashboard
                header('Location: index.php?page=dashboard');
            } else {
                // Fallback
                header('Location: index.php?page=dashboard');
            }
            exit;
        } else {
            $remaining = 5 - ($_SESSION['login_attempts'] ?? 0);
            if ($remaining > 0) {
                $error = "Benutzername oder Passwort falsch. ({$remaining} Versuche übrig)";
            } else {
                $error = 'Zu viele Fehlversuche. Account für 15 Minuten gesperrt.';
            }
        }
    }
}
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/drk-logo.png" alt="DRK Logo" class="login-logo">
            <h1>Medizinprodukt-Verwaltung</h1>
            <p>DRK Stadtverband Haltern am See e.V.</p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <?= h($error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" id="username" name="username" class="form-input" 
                       value="<?= h($username ?? '') ?>" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-large btn-block">
                Anmelden
            </button>
        </form>
        
        <div class="login-footer">
            <p class="version-info">Version <?= APP_VERSION ?></p>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: linear-gradient(135deg, var(--primary-red), #c41e3a);
}

.login-card {
    background: var(--primary-white);
    border-radius: 16px;
    box-shadow: var(--shadow-heavy);
    padding: 40px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.login-header {
    margin-bottom: 30px;
}

.login-logo {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    border-radius: 12px;
}

.login-header h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--primary-black);
    margin-bottom: 8px;
}

.login-header p {
    color: var(--dark-gray);
    font-size: 16px;
}

.login-form {
    text-align: left;
    margin-bottom: 20px;
}

.login-footer {
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.version-info {
    color: var(--dark-gray);
    font-size: 14px;
    margin: 0;
}

@media (max-width: 480px) {
    .login-card {
        padding: 30px 20px;
    }
}
</style>