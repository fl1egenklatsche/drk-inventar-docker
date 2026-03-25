# DRK Inventar - Docker Deployment

Docker-Setup für DRK Inventar mit automatischem Let's Encrypt SSL-Zertifikat.

## Features

- 🐳 Docker Compose Setup
- 🔒 Automatisches Let's Encrypt SSL via Caddy
- 🗄️ MariaDB Datenbank
- 📦 Demo-Daten (GW-SAN 1 + KTW-B 1)
- ⚙️ Konfiguration via Environment Variables

## Quick Start

1. `.env` Datei erstellen:
```bash
cp .env.example .env
nano .env  # HOSTNAME anpassen!
```

2. Container starten:
```bash
docker-compose up -d
```

3. Datenbank initialisieren:
```bash
# Struktur + Admin User
docker-compose exec db mariadb -u drk_user -pdrk_password drk_inventar < /docker-entrypoint-initdb.d/install.sql

# Demo-Daten (optional)
docker-compose exec db mariadb -u drk_user -pdrk_password drk_inventar < /docker-entrypoint-initdb.d/demo.sql
```

4. Fertig! Öffne: `https://IHR-HOSTNAME:8443`

## Umgebungsvariablen

| Variable | Beschreibung | Standard |
|----------|--------------|----------|
| `HOSTNAME` | Domain für die App (z.B. `mhd-tool.example.com`) | **ERFORDERLICH** |
| `DB_PASSWORD` | MariaDB Passwort | `drk_password` |
| `DB_ROOT_PASSWORD` | MariaDB Root Passwort | `drk_root_password` |

## Standard Login

- **Username:** `admin`
- **Password:** `admin123`

⚠️ **Wichtig:** Passwort nach erstem Login ändern!

## Verzeichnisstruktur

```
drk-inventar-docker/
├── app/                    # App-Code (wird gemountet)
├── docker/
│   ├── caddy/
│   │   └── Caddyfile      # Caddy Webserver Config
│   └── php/
│       └── Dockerfile      # PHP-FPM Container
├── data/
│   ├── db/                 # MariaDB Daten (persistent)
│   ├── caddy/             # Caddy Daten & Zertifikate (persistent)
│   └── uploads/           # App Uploads (persistent)
├── sql/
│   ├── install.sql        # Datenbankstruktur
│   └── demo.sql           # Demo-Daten (optional)
├── docker-compose.yml
├── .env.example
└── README.md
```

## Datenbank-Management

**phpMyAdmin:** `http://IHR-HOSTNAME:8080`
- Username: `drk_user`
- Password: (aus .env)

## Logs anzeigen

```bash
docker-compose logs -f app
docker-compose logs -f caddy
docker-compose logs -f db
```

## Updates

```bash
# App-Code aktualisieren
cd app/
git pull origin main

# Container neu starten
docker-compose restart app
```

## Backup

```bash
# Datenbank Backup
docker-compose exec db mariadb-dump -u drk_user -pdrk_password drk_inventar > backup_$(date +%Y%m%d).sql

# Uploads Backup
tar -czf uploads_$(date +%Y%m%d).tar.gz data/uploads/
```

## Troubleshooting

### Let's Encrypt Fehler
- Stelle sicher dass der Hostname auf die Server-IP zeigt (DNS)
- Port 80 und 8443 müssen erreichbar sein
- Caddy braucht beim ersten Start 1-2 Minuten für das Zertifikat

### Datenbank Connection Error
- Check `config.php` in `app/includes/`
- Prüfe DB_HOST: `db` (Docker Service Name)

## Lizenz

MIT License
