# DRK Medizinprodukt-Verwaltungssystem

Webbasiertes Tool zur Verwaltung von Medizinprodukten und MHD-Überwachung für Rettungsfahrzeuge.

## Features

- 🚗 **Fahrzeugverwaltung** - RTW, KTW, GW-SAN, Lager
- 📦 **Produktverwaltung** - 150+ vordefinierte Medizinprodukte
- 🗄️ **Fächerstruktur** - Container und Compartments mit Min/Max-Mengen
- ⏰ **MHD-Überwachung** - Automatische Warnungen bei ablaufenden Produkten
- ✅ **Kontrollprotokoll** - Digitale Checklisten mit Zeitstempel
- 📊 **Dashboard** - Übersicht über alle Fahrzeuge und kritische Produkte
- 📱 **Mobile-Ready** - Responsive Design für Tablets
- 🌙 **Dark Mode** - Augenschonende Darstellung

## Tech Stack

- **Backend:** PHP 8.3+
- **Database:** MySQL/MariaDB 10.5+
- **Frontend:** Vanilla JS, CSS3
- **Libraries:** jQuery 3.6, Chart.js (optional)

## Installation

### 1. Voraussetzungen

```bash
# Ubuntu/Debian
sudo apt-get install php php-mysql mariadb-server

# PHP Extensions
php -m | grep -E '(mysqli|pdo_mysql|mbstring|json)'
```

### 2. Datenbank einrichten

```bash
mysql -u root -p

CREATE DATABASE drk_inventar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'drk_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON drk_inventar.* TO 'drk_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Schema und Daten importieren

```bash
mysql -u drk_user -p drk_inventar < database_schema.sql
mysql -u drk_user -p drk_inventar < products_data.sql
```

### 4. Konfiguration

```bash
cp includes/config.example.php includes/config.php
nano includes/config.php
```

Anpassen:
- `DB_NAME` → Ihr Datenbankname
- `DB_USER` → Ihr Datenbankuser
- `DB_PASS` → Ihr Passwort
- `BASE_URL` → Ihre URL

### 5. Webserver

**Option A: PHP Development Server (nur für Tests)**

```bash
php -S localhost:8080
```

**Option B: Apache/Nginx (Produktion)**

Beispiel Apache VirtualHost:

```apache
<VirtualHost *:80>
    ServerName mhd-tool.example.org
    DocumentRoot /var/www/drk-inventar
    
    <Directory /var/www/drk-inventar>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/drk-inventar-error.log
    CustomLog ${APACHE_LOG_DIR}/drk-inventar-access.log combined
</VirtualHost>
```

### 6. Erster Login

Erstellen Sie einen Admin-User:

```bash
php -r "echo password_hash('IhrPasswort', PASSWORD_DEFAULT);"
```

Fügen Sie den Hash in die Datenbank ein:

```sql
INSERT INTO users (username, password_hash, full_name, role) 
VALUES ('admin', 'HASH_VON_OBEN', 'Administrator', 'admin');
```

Login: `http://localhost:8080`

## Datenbankstruktur

### Haupttabellen

- **vehicles** - Fahrzeuge (RTW, KTW, etc.)
- **containers** - Bereiche im Fahrzeug (Patientenraum, Fahrerhaus, etc.)
- **compartments** - Fächer/Schubladen in Containern
- **products** - Produktkatalog
- **compartment_products_target** - Soll-Zustand (Min/Max)
- **compartment_products_actual** - Ist-Zustand (tatsächliche Produkte)
- **inspections** - Kontrollprotokolle
- **inspection_items** - Einzelne Kontrollpunkte
- **users** - Benutzer

### Views

- **v_expiring_products** - Produkte die in 28 Tagen ablaufen
- **v_last_inspections** - Letzte Kontrolle pro Fahrzeug

## Entwicklung

### Lokales Setup

```bash
git clone https://github.com/fl1egenklatsche/drk-inventar.git
cd drk-inventar
cp includes/config.example.php includes/config.php
# Config anpassen
php -S localhost:8080
```

### Debug-Tools

Im Projekt enthalten:
- `debug_*.php` - Diverse Debug-Skripte
- `repair_database.php` - Datenbank-Reparatur
- `test_inspection_data.php` - Testdaten

### API Endpoints

- `api/get-vehicles.php` - Fahrzeugliste
- `api/get-containers.php` - Container eines Fahrzeugs
- `api/get-compartments.php` - Compartments eines Containers
- `api/start-inspection.php` - Kontrolle starten
- `api/save-inspection-item.php` - Kontrollpunkt speichern

## Sicherheit

⚠️ **Wichtig für Produktion:**

1. `error_reporting` in `config.php` ausschalten
2. `display_errors = 0` setzen
3. Starke Passwörter verwenden
4. HTTPS aktivieren
5. `.htaccess` für Upload-Verzeichnis prüfen
6. Regelmäßige Backups einrichten

## Backup

```bash
# Datenbank
mysqldump -u drk_user -p drk_inventar > backup_$(date +%Y%m%d).sql

# Uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

## Lizenz

Proprietär - DRK Stadtverband Haltern am See e.V.

## Support

Bei Fragen oder Problemen:
- GitHub Issues: https://github.com/fl1egenklatsche/drk-inventar/issues
- Dokumentation: Siehe Wiki

## Changelog

### Version 1.0.0 (2026-03-11)
- Initial Release
- 154 vordefinierte Produkte
- 5 Fahrzeug-Templates
- Vollständige MHD-Überwachung
- Mobile-optimierte Kontrollen
