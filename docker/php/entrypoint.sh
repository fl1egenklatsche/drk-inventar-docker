#!/bin/bash
set -e

echo "🚀 DRK Inventar Startup Script"

# Create config.php if it doesn't exist
if [ ! -f /var/www/html/includes/config.php ]; then
    echo "📝 Creating config.php..."
    cat > /var/www/html/includes/config.php << EOF
<?php
// Database Configuration
define('DB_HOST', '${DB_HOST:-db}');
define('DB_PORT', '${DB_PORT:-3306}');
define('DB_NAME', '${DB_NAME:-drk_inventar}');
define('DB_USER', '${DB_USER:-drk_user}');
define('DB_PASS', '${DB_PASSWORD:-drk_password}');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_LIFETIME', 3600);

// Application Configuration
define('APP_NAME', 'DRK Inventar');
define('APP_VERSION', '1.0.0');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB

// Debug Mode
define('DEBUG_MODE', false);

// Error Handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Berlin');
EOF
    chown www-data:www-data /var/www/html/includes/config.php
    chmod 644 /var/www/html/includes/config.php
    echo "✅ config.php created"
fi

# Start PHP-FPM
echo "🚀 Starting PHP-FPM..."
exec php-fpm
