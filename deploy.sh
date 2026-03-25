#!/bin/bash
# Deploy Script für DRK Inventar Docker

set -e

echo "🚀 DRK Inventar Docker Deployment"
echo "=================================="
echo ""

# Check .env
if [ ! -f .env ]; then
    echo "❌ .env nicht gefunden!"
    echo "   Kopiere .env.example nach .env und passe HOSTNAME an!"
    echo ""
    echo "   cp .env.example .env"
    echo "   nano .env"
    exit 1
fi

# Load .env
export $(cat .env | grep -v '^#' | xargs)

if [ -z "$HOSTNAME" ] || [ "$HOSTNAME" == "mhd-tool.example.com" ]; then
    echo "❌ HOSTNAME in .env nicht gesetzt oder noch Standard-Wert!"
    echo "   Bitte passe HOSTNAME in .env an!"
    exit 1
fi

echo "✅ Hostname: $HOSTNAME"
echo ""

# Docker Compose Build & Start
echo "📦 Building containers..."
docker-compose build --pull

echo ""
echo "🚀 Starting containers..."
docker-compose up -d

echo ""
echo "⏳ Warte auf Datenbank..."
sleep 10

# Check DB Health
until docker-compose exec -T db mariadb-admin ping -h localhost -u root -p"${DB_ROOT_PASSWORD}" --silent 2>/dev/null; do
    echo "   Warte auf Datenbank..."
    sleep 2
done

echo "✅ Datenbank bereit!"
echo ""

# Install Database
echo "📊 Importiere Datenbank-Struktur..."
docker-compose exec -T db mariadb -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < sql/install.sql

echo ""
read -p "Demo-Daten importieren? (GW-SAN 1 + KTW-B 1) [y/N]: " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "📦 Importiere Demo-Daten..."
    docker-compose exec -T db mariadb -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < sql/demo.sql
    echo "✅ Demo-Daten importiert!"
fi

echo ""
echo "=================================="
echo "✅ Deployment abgeschlossen!"
echo ""
echo "🌐 App:         https://$HOSTNAME"
echo "🗄️  phpMyAdmin: http://$HOSTNAME:8080"
echo ""
echo "👤 Login:"
echo "   Username: admin"
echo "   Password: admin123"
echo ""
echo "⚠️  WICHTIG: Passwort nach erstem Login ändern!"
echo ""
echo "📋 Logs anzeigen:"
echo "   docker-compose logs -f app"
echo ""
echo "🛑 Stoppen:"
echo "   docker-compose down"
echo ""
