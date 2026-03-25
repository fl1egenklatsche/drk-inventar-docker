#!/bin/bash
set -e

echo "🗄️ DRK Inventar DB Init Script"

# Wait for MariaDB to be ready
until mariadb -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT 1" &> /dev/null; do
    echo "⏳ Waiting for MariaDB to be ready..."
    sleep 2
done

echo "✅ MariaDB is ready"

# Check if tables already exist
TABLES=$(mariadb -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" -e "SHOW TABLES;" -s --skip-column-names | wc -l)

if [ "$TABLES" -eq 0 ]; then
    echo "📦 Database is empty - importing schema and demo data..."
    
    # Download SQL files from GitHub
    echo "📥 Downloading install.sql..."
    curl -fsSL https://raw.githubusercontent.com/fl1egenklatsche/drk-inventar-docker/main/sql/install.sql -o /tmp/install.sql
    
    echo "📥 Downloading demo.sql..."
    curl -fsSL https://raw.githubusercontent.com/fl1egenklatsche/drk-inventar-docker/main/sql/demo.sql -o /tmp/demo.sql
    
    # Import SQL files
    echo "📊 Importing install.sql..."
    mariadb -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < /tmp/install.sql
    
    echo "📊 Importing demo.sql..."
    mariadb -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < /tmp/demo.sql
    
    # Cleanup
    rm -f /tmp/install.sql /tmp/demo.sql
    
    echo "✅ Database initialized successfully!"
    
    # Show tables
    echo ""
    echo "📋 Tables created:"
    mariadb -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" -e "SHOW TABLES;"
else
    echo "✅ Database already initialized (${TABLES} tables found)"
fi

echo "🚀 DB Init complete"
