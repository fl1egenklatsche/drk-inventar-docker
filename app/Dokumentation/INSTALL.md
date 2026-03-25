# DRK Inventar - Installation

## Voraussetzungen

- PHP 8.0+
- MariaDB 10.5+ oder MySQL 8.0+
- Apache/Nginx Webserver
- Mindestens 512MB RAM

## Installation

### 1. Datenbank erstellen

```bash
mysql -u root -p

CREATE DATABASE drk_inventar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'drk_user'@'localhost' IDENTIFIED BY 'IHR_SICHERES_PASSWORT';
GRANT ALL PRIVILEGES ON drk_inventar.* TO 'drk_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Datenbank importieren

```bash
mysql -u drk_user -p drk_inventar < install.sql
```

**Hinweis:** `install.sql` enthält:
- Komplette Datenbank-Struktur (alle Tabellen)
- Fahrzeuge: GW-SAN 1, KTW-B 1, RTW 1, RTW 2, Materiallager
- 1300+ Produkte mit Kategorisierung
- Container und Fächer mit SOLL-Bestückungen
- Standard-Admin-User

### 3. Konfiguration anpassen

Kopiere `includes/config.example.php` nach `includes/config.php`:

```bash
cp includes/config.example.php includes/config.php
```

Passe die Datenbank-Zugangsdaten an:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'drk_inventar');
define('DB_USER', 'drk_user');
define('DB_PASS', 'IHR_SICHERES_PASSWORT');
```

### 4. Webserver konfigurieren

**Apache VirtualHost Beispiel:**

```apache
<VirtualHost *:80>
    ServerName drk-inventar.local
    DocumentRoot /var/www/drk-inventar
    
    <Directory /var/www/drk-inventar>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/drk-inventar-error.log
    CustomLog ${APACHE_LOG_DIR}/drk-inventar-access.log combined
</VirtualHost>
```

Aktivieren:

```bash
sudo a2ensite drk-inventar.conf
sudo systemctl reload apache2
```

### 5. Berechtigungen setzen

```bash
sudo chown -R www-data:www-data /var/www/drk-inventar
sudo chmod -R 755 /var/www/drk-inventar
```

### 6. Erste Anmeldung

- URL: http://drk-inventar.local (oder deine konfigurierte Domain)
- **Username:** `admin`
- **Password:** `admin123`

**⚠️ WICHTIG:** Ändere das Admin-Passwort sofort nach der ersten Anmeldung!

## Standard-Inhalte

### Fahrzeuge (5 Stück)
1. **GW-SAN 1** (Gerätewagen Sanitätsdienst)
   - 22 Container (12 Kisten, 10 Rucksäcke)
   - 1199 Produkte
   
2. **KTW-B 1** (Krankentransportwagen Typ B)
   - 4 Container (Notfall-Rucksack, Baby-Koffer, Verbandkasten, Fahrzeug)
   - 168 Produkte

3. **RTW 1** (Rettungswagen)
   - 47 Fächer
   - 112 Produkte

4. **RTW 2** (Rettungswagen)
   - 29 Fächer
   - 116 Produkte

5. **Materiallager**
   - Leer (zum eigenen Befüllen)

### Produkte
- **1300+ Produkte** bereits angelegt
- Kategorisiert nach medizinischen Bereichen
- `has_expiry` Flag für MHD-Pflicht gesetzt

## Nächste Schritte

1. **Admin-Passwort ändern**: Management → Benutzer → admin bearbeiten
2. **Weitere Benutzer anlegen**: Management → Benutzer → Hinzufügen
3. **Erste Kontrolle durchführen**: Fahrzeuge → Fahrzeug auswählen → Kontrolle starten
4. **Produktverwaltung prüfen**: Management → Produkte → MHD-Pflicht bei Bedarf anpassen

## Troubleshooting

### Fehler: "Database connection failed"
- Prüfe `includes/config.php` Zugangsdaten
- Prüfe ob MariaDB läuft: `sudo systemctl status mariadb`
- Prüfe Firewall-Regeln

### Fehler: "Access denied"
- Prüfe Dateiberechtigungen: `ls -la /var/www/drk-inventar`
- Prüfe Apache-Error-Log: `sudo tail -f /var/log/apache2/error.log`

### Produkte ohne MHD-Pflicht
Standardmäßig sind alle Produkte MHD-pflichtig. Für dauerhafte Gegenstände (Stethoskop, Schere):

```sql
UPDATE products SET has_expiry = 0 WHERE name LIKE '%Stethoskop%';
```

Oder über GUI: Management → Produkte → [Produkt bearbeiten] → MHD-pflichtig Checkbox

## Support

- GitHub: https://github.com/fl1egenklatsche/drk-inventar
- Issues: https://github.com/fl1egenklatsche/drk-inventar/issues

## Lizenz

Entwickelt für DRK Haltern am See.
