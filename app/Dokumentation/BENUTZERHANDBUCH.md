# DRK Medizinprodukt-Verwaltung - Benutzerhandbuch

**Version:** 2.0  
**Stand:** März 2026  
**Für:** DRK Ortsverein - Inventar- und Prüfverwaltung

---

## Inhaltsverzeichnis

1. [Systemübersicht](#1-systemübersicht)
2. [Login & Benutzerverwaltung](#2-login--benutzerverwaltung)
3. [Dashboard](#3-dashboard)
4. [Fahrzeugprüfungen](#4-fahrzeugprüfungen)
5. [Container-Prüfungen](#5-container-prüfungen)
6. [Verwaltung](#6-verwaltung)
7. [Benutzer & Rollen](#7-benutzer--rollen)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Systemübersicht

### 1.1 Zweck
Das DRK Medizinprodukt-Verwaltungssystem dient zur:
- **Inventarverwaltung** von Medizinprodukten in Fahrzeugen und Containern
- **Prüfverwaltung** für regelmäßige Kontrollen (Fahrzeug-Level und Container-Level)
- **MHD-Überwachung** (Mindesthaltbarkeitsdatum) für Verbrauchsmaterialien
- **Multi-User-Koordination** bei parallelen Prüfungen

### 1.2 Zugriff
- **URL:** `http://192.168.177.26:8082`
- **Browser:** Chrome, Firefox, Safari, Edge (aktuelle Versionen)
- **Netzwerk:** Nur im internen Netzwerk erreichbar

### 1.3 Systemarchitektur
- **Frontend:** PHP 8+ (built-in server auf Port 8082)
- **Datenbank:** MariaDB 11 (Docker Container auf Port 3307)
- **Backup:** Automatische tägliche Backups (via Cron)

---

## 2. Login & Benutzerverwaltung

### 2.1 Anmeldung
1. Browser öffnen und URL aufrufen: `http://192.168.177.26:8082`
2. Login-Seite erscheint mit Feldern:
   - **Benutzername**
   - **Passwort**
3. Zugangsdaten eingeben und auf **"Anmelden"** klicken

### 2.2 Standard-Benutzer

| Benutzername | Passwort | Rolle | Beschreibung |
|--------------|----------|-------|--------------|
| `admin` | `admin2026` | Administrator | Vollzugriff auf alle Funktionen |
| `kontrolle` | `kontrolle2026` | Kontrolle | Nur Container-Prüfungen durchführen |

**⚠️ WICHTIG:** Passwörter nach Erstanmeldung ändern!

### 2.3 Passwort ändern
1. Oben rechts auf **Benutzernamen** klicken
2. **"Profil"** auswählen
3. Neues Passwort eingeben (min. 8 Zeichen, 1 Großbuchstabe, 1 Kleinbuchstabe, 1 Ziffer, 1 Sonderzeichen)
4. Passwort bestätigen
5. **"Speichern"** klicken

### 2.4 Abmelden
- Oben rechts auf **Benutzernamen** klicken → **"Abmelden"**

---

## 3. Dashboard

### 3.1 Übersicht
Das Dashboard ist die Startseite nach dem Login und zeigt:
- **Container-Prüfungen** (Widget) - nur wenn berechtigt
- **Ablaufende Produkte** (nächste 30 Tage)
- **Fällige Kontrollen** (Fahrzeuge mit anstehenden Prüfungen)
- **Statistiken** (oben): Fahrzeuge, Container, Produkte, Prüfungen

### 3.2 Statistik-Karten (oben)
Zeigen auf einen Blick:
- 🚗 **Anzahl Fahrzeuge**
- 📦 **Anzahl Container**
- 💊 **Anzahl Produkte**
- ✅ **Abgeschlossene Prüfungen** (aktueller Monat)

### 3.3 Container-Prüfungen Widget
**Nur sichtbar für:** Admin, Fahrzeugwart, Kontrolle

Zeigt:
- Aktive Prüfungen (Vehicle, gestartet von wem, wann)
- Container-Status (pending/in_progress/completed)
- **Button:** "Zur Container-Prüfung" (öffnet Container-Prüfungs-Overview)

### 3.4 Ablaufende Produkte
Liste der Produkte mit MHD in den nächsten 30 Tagen:
- Produktname
- Container-Zuordnung
- Aktuelles MHD
- Tage bis Ablauf (farblich markiert: rot < 7 Tage, orange < 14 Tage)

**Aktion:** Klick auf Produkt → Details anzeigen

### 3.5 Fällige Kontrollen
Liste der Fahrzeuge mit anstehenden Prüfungen:
- Fahrzeugname
- Letzte Kontrolle (Datum)
- Status (überfällig / in 7 Tagen / heute)
- **Button:** "Kontrolle starten" (öffnet Fahrzeugprüfung)

---

## 4. Fahrzeugprüfungen

### 4.1 Was sind Fahrzeugprüfungen?
**Komplette Prüfung aller Container und Compartments eines Fahrzeugs in einem Durchgang.**

**Geeignet für:**
- Regelmäßige Vollprüfungen (z.B. monatlich)
- Single-User Szenarien (eine Person prüft alles)
- Dokumentation der Gesamt-Fahrzeugprüfung

**Workflow:**
1. Fahrzeug auswählen
2. Alle Compartments durchgehen (Container für Container)
3. Produkte erfassen (vorhanden/fehlend, Menge, MHD)
4. Am Ende: Prüfung abschließen mit Unterschrift/Notiz

### 4.2 Fahrzeugprüfung starten

**Berechtigung:** Admin, Fahrzeugwart, User

1. **Dashboard** → Widget "Fällige Kontrollen"
2. Fahrzeug auswählen → **"Kontrolle starten"** klicken
3. Oder: Navigation links → **"Kontrolle"** → Fahrzeug aus Liste wählen

### 4.3 Prüfung durchführen

#### Schritt 1: Container-Navigation
- Oben: Container-Tabs (z.B. "RUCKSACK-1", "SA1", "Fahrzeug")
- Klick auf Tab → wechselt zum Container

#### Schritt 2: Compartment-Navigation
- Links: Liste der Compartments (z.B. "Beatmung", "Infusion", "Verbandmaterial")
- Klick auf Compartment → zeigt Produkte an

#### Schritt 3: Produkte prüfen
Für jedes Produkt im Compartment:

**Option A: Produkt vorhanden**
1. Menge prüfen: Eingabefeld zeigt Soll-Menge, trage IST-Menge ein
2. MHD prüfen (wenn Medizinprodukt):
   - Vorheriges MHD wird angezeigt
   - Neues MHD eingeben (Format: YYYY-MM-DD oder Kalender-Picker)
3. Zustand prüfen: Dropdown (einwandfrei / beschädigt / abgelaufen)
4. Optional: Notiz eingeben

**Option B: Produkt fehlt**
- Checkbox "Fehlt" aktivieren
- System trägt Menge = 0 ein

#### Schritt 4: Zum nächsten Compartment
- **"Weiter"** Button unten rechts
- Oder: Klick auf nächstes Compartment in Sidebar

#### Schritt 5: Prüfung abschließen
Wenn alle Container/Compartments geprüft:
1. **"Prüfung abschließen"** Button erscheint
2. Modal öffnet sich:
   - Name des Prüfers eingeben
   - Optional: Gesamtnotiz
3. **"Bestätigen"** → Prüfung wird gespeichert

### 4.4 Prüfung unterbrechen
- Daten werden automatisch gespeichert (alle 30 Sekunden)
- Abmelden möglich → nächster Login setzt fort wo aufgehört

### 4.5 Prüfberichte
**Navigation:** "Berichte" → "Fahrzeugprüfungen"

Zeigt:
- Liste aller abgeschlossenen Prüfungen
- Filter: Fahrzeug, Zeitraum, Prüfer
- **Aktion:** Klick auf Prüfung → PDF-Export

---

## 5. Container-Prüfungen

### 5.1 Was sind Container-Prüfungen?
**Einzelne Container unabhängig prüfen - geeignet für Multi-User Szenarien.**

**Vorteile:**
- **Parallele Arbeit:** Mehrere Personen prüfen gleichzeitig verschiedene Container
- **Flexibilität:** Nur bestimmte Container prüfen (nicht ganzes Fahrzeug)
- **Nachverfolgung:** Wer hat welchen Container wann geprüft

**Workflow:**
1. Admin/Fahrzeugwart startet Prüfung für ein Fahrzeug
2. System erstellt Container-Liste (Status: pending)
3. Mehrere Personen können Container parallel prüfen
4. Bei Abschluss: Name + Timestamp pro Container
5. Wenn alle Container completed: Gesamt-Prüfung kann abgeschlossen werden

### 5.2 Container-Prüfung starten

**Berechtigung:** Admin, Fahrzeugwart

1. **Dashboard** → Widget "Container-Prüfungen" → **"Zur Container-Prüfung"**
2. Oder: Navigation → **"Container-Prüfung"** → **"Prüfung starten"**
3. Fahrzeug auswählen aus Dropdown
4. **"Prüfung starten"** klicken
5. System erstellt Container-Liste → Weiterleitung zur Overview

### 5.3 Container-Prüfungs-Overview

Zeigt alle Container des Fahrzeugs mit Status:

| Status | Farbe | Bedeutung |
|--------|-------|-----------|
| **pending** | Grau | Noch nicht begonnen |
| **in_progress** | Orange | Wird gerade geprüft |
| **completed** | Grün | Abgeschlossen |

**Auto-Reload:** Seite aktualisiert sich alle 10 Sekunden (bei Multi-User Szenarien)

**Aktionen:**
- **"Container prüfen"** (bei pending/in_progress) → öffnet Prüfung
- **"Details"** (bei completed) → zeigt Prüfergebnis

### 5.4 Container prüfen

**Berechtigung:** Admin, Fahrzeugwart, Kontrolle

#### Schritt 1: Container auswählen
1. Overview → **"Container prüfen"** klicken
2. Container wird auf "in_progress" gesetzt
3. Prüfseite öffnet sich

#### Schritt 2: Compartments durchgehen
- Oben: Container-Name + Fortschritt (z.B. "Compartment 2/5")
- Navigation: **"Zurück"** / **"Weiter"** Buttons
- Oder: Dropdown "Zu Compartment springen"

#### Schritt 3: Produkte erfassen
Für jedes Produkt:

**A) Produkt vorhanden:**
1. Anzahl erfassen (Input-Feld)
2. MHD erfassen (nur wenn Medizinprodukt):
   - Kalender öffnet sich automatisch
   - Datum wählen (Format: MM/YYYY)
3. Optional: Notiz eingeben

**B) Produkt fehlt:**
- Checkbox **"Fehlt"** aktivieren
- Felder werden ausgegraut

**C) Produkt beschädigt:**
- Checkbox **"Beschädigt"** aktivieren
- Notiz-Feld wird Pflichtfeld

#### Schritt 4: Container abschließen
Wenn alle Compartments geprüft:
1. **"Container abschließen"** Button erscheint
2. Modal öffnet sich:
   - **Name eingeben** (Pflicht)
   - Optional: Notiz für gesamten Container
3. **"Bestätigen"** klicken
4. Container wird auf "completed" gesetzt
5. Weiterleitung zur Overview

### 5.5 Multi-User Koordination

**Szenario:** 3 Personen prüfen GW-SAN 1 parallel

**Person A (Admin):**
1. Startet Prüfung → erstellt Container-Liste
2. Wählt "RUCKSACK-1" → prüft diesen Container

**Person B (Kontrolle):**
1. Geht zur Overview → sieht alle Container
2. Wählt "SA1" → prüft diesen Container
3. Sieht in Overview dass "RUCKSACK-1" bereits "in_progress" ist

**Person C (Fahrzeugwart):**
1. Geht zur Overview
2. Sieht "RUCKSACK-1" (in_progress von Person A)
3. Sieht "SA1" (in_progress von Person B)
4. Wählt "SB2" → prüft diesen Container

**Ergebnis:**
- Alle 3 arbeiten parallel
- Keine Kollisionen (jeder prüft eigenen Container)
- Auto-Reload zeigt Fortschritt aller

### 5.6 Gesamt-Prüfung abschließen

**Berechtigung:** Admin, Fahrzeugwart

**Voraussetzung:** Alle Container auf "completed"

1. Overview → Button **"Prüfung abschließen"** erscheint (grün)
2. Klick → Modal:
   - Zusammenfassung anzeigen (wer hat welchen Container geprüft)
   - Optional: Gesamtnotiz
   - Unterschrift/Name
3. **"Abschließen"** → Prüfung wird archiviert

**Status danach:**
- Prüfung ist abgeschlossen
- Erscheint in Berichten
- Neue Prüfung kann für gleiches Fahrzeug gestartet werden

### 5.7 Container-Prüfung abbrechen

**Berechtigung:** Admin

1. Overview → oben rechts **"Prüfung abbrechen"**
2. Bestätigung: "Wirklich abbrechen? Alle Daten gehen verloren!"
3. **"Ja, abbrechen"** → Prüfung wird gelöscht

**Achtung:** Nur in Notfällen verwenden!

---

## 6. Verwaltung

### 6.1 Fahrzeuge verwalten

**Navigation:** Links → **"Verwaltung"** → **"Fahrzeuge"**

**Berechtigung:** Admin

#### Fahrzeug hinzufügen
1. Button **"Neues Fahrzeug"** klicken
2. Formular ausfüllen:
   - **Name** (z.B. "RTW 3")
   - **Kennzeichen** (optional)
   - **Funkrufname** (optional)
   - **Typ** (Dropdown: RTW, KTW, GW-SAN, etc.)
   - **Standort** (optional)
3. **"Speichern"** klicken

#### Fahrzeug bearbeiten
1. Fahrzeug in Liste suchen
2. **"Bearbeiten"** Symbol (Stift) klicken
3. Daten ändern
4. **"Speichern"**

#### Fahrzeug löschen
1. **"Löschen"** Symbol (Papierkorb) klicken
2. Bestätigung: "Wirklich löschen?"
3. **"Ja"** → Fahrzeug wird gelöscht

**⚠️ Achtung:** Löschen nur möglich wenn:
- Keine Container zugeordnet sind
- Keine offenen Prüfungen existieren

### 6.2 Container verwalten

**Navigation:** Links → **"Verwaltung"** → **"Container"**

**Berechtigung:** Admin

#### Container hinzufügen
1. Button **"Neuer Container"** klicken
2. Formular:
   - **Name** (z.B. "RUCKSACK-11")
   - **Fahrzeug** (Dropdown - welchem Fahrzeug zuordnen)
   - **Typ** (Dropdown: Rucksack, Koffer, Tasche, Fahrzeug-Intern)
   - **Beschreibung** (optional)
   - **Position** (optional, z.B. "Fahrerseite hinten")
3. **"Speichern"**

#### Container bearbeiten
1. Container suchen (Filter nach Fahrzeug möglich)
2. **"Bearbeiten"** klicken
3. Ändern
4. **"Speichern"**

#### Container löschen
1. **"Löschen"** klicken
2. Prüfung: System checkt ob Compartments zugeordnet sind
3. Wenn ja: "Container hat Compartments - wirklich löschen?"
4. Bestätigung → Container wird gelöscht (inkl. Compartments!)

### 6.3 Compartments verwalten

**Navigation:** Container-Liste → Container auswählen → Tab **"Compartments"**

**Berechtigung:** Admin

#### Compartment hinzufügen
1. Container wählen
2. **"Neues Fach"** klicken
3. Formular:
   - **Name** (z.B. "Beatmung", "Infusion")
   - **Beschreibung** (optional)
   - **Position** (optional, z.B. "Oben links")
   - **Reihenfolge** (Zahl für Sortierung)
4. **"Speichern"**

#### Compartment bearbeiten/löschen
- Analog zu Container (Bearbeiten/Löschen Buttons)

### 6.4 Produkte verwalten

**Navigation:** Links → **"Verwaltung"** → **"Produkte"**

**Berechtigung:** Admin

#### Produkt hinzufügen
1. **"Neues Produkt"** klicken
2. Formular:
   - **Name** (z.B. "Beatmungsbeutel Erwachsene")
   - **Artikelnummer** (optional)
   - **Kategorie** (Dropdown: Verbandmaterial, Medikament, Medizinprodukt, etc.)
   - **Einheit** (Stück, Packung, Liter, etc.)
   - **Hat MHD** (Checkbox - wenn Medizinprodukt)
   - **Beschreibung** (optional)
   - **Hersteller** (optional)
   - **Lagermindestbestand** (optional)
3. **"Speichern"**

#### Produkt bearbeiten
1. Suche Produkt (Filter: Name, Kategorie)
2. **"Bearbeiten"** klicken
3. Ändern
4. **"Speichern"**

#### Produkt löschen
**⚠️ Vorsicht:** Nur möglich wenn NICHT in Sollbeständen verwendet!

1. **"Löschen"** klicken
2. System prüft Verwendung
3. Wenn in Sollbeständen: Fehler "Produkt wird verwendet"
4. Sonst: Bestätigung → Löschen

### 6.5 Sollbestände verwalten

**Navigation:** Verwaltung → **"Sollbestände"**

**Berechtigung:** Admin

#### Was sind Sollbestände?
**Definition welche Produkte in welcher Menge in welchem Compartment sein SOLLEN.**

Beispiel:
- Container: RUCKSACK-1
- Compartment: Beatmung
- Produkte:
  - Beatmungsbeutel Erwachsene: 1 Stück
  - Beatmungsmaske Gr. 2: 2 Stück
  - Beatmungsmaske Gr. 5: 2 Stück

#### Sollbestand hinzufügen
1. **"Neuer Sollbestand"** klicken
2. Formular:
   - **Fahrzeug** wählen (Dropdown)
   - **Container** wählen (Dropdown - filtered nach Fahrzeug)
   - **Compartment** wählen (Dropdown - filtered nach Container)
   - **Produkt** wählen (Dropdown mit Suche)
   - **Sollmenge** eingeben (Zahl)
3. **"Speichern"**

#### Sollbestand bearbeiten
1. Liste filtern (Fahrzeug/Container/Compartment)
2. Produkt suchen
3. **"Bearbeiten"** → Sollmenge ändern
4. **"Speichern"**

#### Sollbestand löschen
1. **"Löschen"** Symbol
2. Bestätigung
3. Produkt wird aus Sollbestand entfernt

#### Massenimport von Sollbeständen
**Für große Mengen:** CSV-Import möglich

1. **"Import"** Button
2. CSV hochladen (Format: Container, Compartment, Produkt, Menge)
3. Vorschau prüfen
4. **"Importieren"** → System legt Sollbestände an

**CSV-Format:**
```csv
Container,Compartment,Produkt,Menge
RUCKSACK-1,Beatmung,Beatmungsbeutel Erwachsene,1
RUCKSACK-1,Beatmung,Beatmungsmaske Gr.2,2
```

---

## 7. Benutzer & Rollen

### 7.1 Rollen-Übersicht

| Rolle | Berechtigung | Typische Aufgaben |
|-------|--------------|-------------------|
| **Administrator** | Vollzugriff | System-Verwaltung, Benutzer anlegen, Strukturen ändern |
| **Fahrzeugwart** | Prüfungen starten/abschließen, Verwaltung lesen | Container-Prüfungen koordinieren, Vollprüfungen |
| **Kontrolle** | Nur Container prüfen | Container-Einzelprüfungen durchführen |
| **User** | Fahrzeugprüfungen, Berichte lesen | Klassische Vollprüfungen |

### 7.2 Benutzer verwalten

**Navigation:** Links → **"Verwaltung"** → **"Benutzer"**

**Berechtigung:** Admin

#### Benutzer hinzufügen
1. **"Neuer Benutzer"** klicken
2. Formular:
   - **Benutzername** (nur Kleinbuchstaben, Zahlen, Unterstrich)
   - **Passwort** (min. 8 Zeichen, Groß-/Kleinbuchstaben, Ziffern, Sonderzeichen)
   - **Vollständiger Name**
   - **E-Mail** (optional)
   - **Rolle** (Dropdown: Administrator, Fahrzeugwart, Kontrolle, User)
   - **Aktiv** (Checkbox - wenn deaktiviert kann User sich nicht anmelden)
3. **"Speichern"**

#### Benutzer bearbeiten
1. User in Liste suchen
2. **"Bearbeiten"** klicken
3. Daten ändern
4. Optional: **Neues Passwort** eingeben (Feld leer lassen um Passwort NICHT zu ändern)
5. **"Speichern"**

#### Benutzer deaktivieren
1. **"Bearbeiten"**
2. Checkbox **"Aktiv"** deaktivieren
3. **"Speichern"**
→ User kann sich nicht mehr anmelden (aber Daten bleiben erhalten)

#### Benutzer löschen
**⚠️ Vorsicht:** Nur möglich wenn User KEINE Prüfungen durchgeführt hat!

1. **"Löschen"** Symbol
2. System prüft Verwendung
3. Wenn Prüfungen vorhanden: Fehler "Benutzer hat Prüfungen"
4. Sonst: Bestätigung → Löschen

### 7.3 Passwort-Anforderungen
**Für alle Benutzer:**
- Mindestlänge: 8 Zeichen
- Mindestens 1 Großbuchstabe
- Mindestens 1 Kleinbuchstabe
- Mindestens 1 Ziffer
- Mindestens 1 Sonderzeichen (!@#$%^&*()_+-=[]{}|;:,.<>?)

**Beispiel gültiges Passwort:** `Drk2026!secure`

---

## 8. Troubleshooting

### 8.1 Häufige Probleme

#### Problem: Login funktioniert nicht
**Symptom:** "Benutzername oder Passwort falsch"

**Lösungen:**
1. Prüfen ob Caps Lock aktiv ist
2. Benutzername korrekt? (Groß-/Kleinschreibung beachten)
3. Passwort korrekt eingegeben?
4. Nach 5 Fehlversuchen: Account für 15 Minuten gesperrt → warten!
5. Wenn weiterhin Problem: Admin kontaktieren

#### Problem: Seite lädt nicht / "Datenbankverbindung fehlgeschlagen"
**Symptom:** Weißer Bildschirm oder Fehler

**Lösungen:**
1. Prüfen ob URL korrekt: `http://192.168.177.26:8082`
2. Prüfen ob im richtigen Netzwerk (nicht extern erreichbar!)
3. Server-Admin kontaktieren:
   - PHP Server läuft? (Port 8082)
   - MySQL Container läuft? (Port 3307)

**Für Admin: Services prüfen:**
```bash
# PHP Server prüfen
ps aux | grep 'php.*8082'

# MySQL prüfen
docker ps | grep drk-inventar-db

# Neustart falls nötig
cd ~/.openclaw/workspace/drk-inventar
nohup php -S 0.0.0.0:8082 -t . > /tmp/drk-inventar-web.log 2>&1 &
```

#### Problem: Container-Prüfung kann nicht gestartet werden
**Symptom:** Button "Container prüfen" ausgegraut oder Fehler

**Lösungen:**
1. Prüfen ob Berechtigung vorhanden (Admin/Fahrzeugwart/Kontrolle)
2. Prüfen ob Container bereits "in_progress" (andere Person prüft gerade)
3. Seite neu laden (F5)

#### Problem: Produkte zeigen "null" statt Namen
**Symptom:** In Container-Prüfung werden keine Produktnamen angezeigt

**Root Cause:** Produkt-IDs in Datenbank fehlen (Foreign Key Problem)

**Lösung für Admin:**
```bash
# Foreign Key Validation
docker exec drk-inventar-db mariadb -u root -pdrk_root_password_2026 drk_inventar -e "
SELECT 
  cpt.id, 
  cpt.product_id, 
  p.name 
FROM compartment_products_target cpt 
LEFT JOIN products p ON cpt.product_id = p.id 
WHERE p.id IS NULL 
LIMIT 10;"
```
Wenn Zeilen zurückkommen: Produkte fehlen → Production-Dump re-importieren

#### Problem: MHD-Abfrage erscheint nicht bei Medizinprodukten
**Symptom:** Kalender für MHD-Eingabe öffnet sich nicht

**Root Cause:** `has_expiry` Flag nicht gesetzt

**Lösung für Admin:**
```sql
-- Produkt prüfen
SELECT id, name, has_expiry FROM products WHERE name LIKE '%Beatmungsbeutel%';

-- Falls has_expiry = 0:
UPDATE products SET has_expiry = 1 WHERE id = <PRODUCT_ID>;
```

#### Problem: Container-Prüfung "eingefroren" - Fortschritt wird nicht gespeichert
**Symptom:** Nach Klick auf "Weiter" passiert nichts

**Lösungen:**
1. Browser-Konsole öffnen (F12) → Tab "Console" → Fehler?
2. Netzwerk-Tab prüfen: Gibt es 404-Fehler auf `/api/...`?
3. Seite neu laden (Daten sollten automatisch gespeichert sein)
4. Falls weiterhin Problem: Log prüfen `/tmp/drk-inventar-web.log`

### 8.2 Bekannte Einschränkungen

1. **Browser-Kompatibilität:** Internet Explorer NICHT unterstützt
2. **Mobile:** Touch-Optimierung nicht vollständig (Tablets OK, Smartphones bedingt)
3. **Offline:** System benötigt Netzwerkverbindung (keine Offline-Funktion)
4. **Gleichzeitigkeit:** Max. 10 parallele Prüfungen empfohlen
5. **Daten-Löschen:** Einmal gelöschte Prüfungen sind NICHT wiederherstellbar

### 8.3 System-Wartung

**Für Administrator:**

#### Backup
- **Automatisch:** Täglich um 03:00 Uhr (via Cron)
- **Manuell:**
  ```bash
  cd ~/.openclaw/workspace/drk-inventar
  docker exec drk-inventar-db mariadb-dump \
    -u root -pdrk_root_password_2026 \
    drk_inventar > backup_$(date +%Y%m%d).sql
  ```

#### Log-Dateien
- **Web-Server:** `/tmp/drk-inventar-web.log`
- **MySQL:** `docker logs drk-inventar-db`

#### Updates
```bash
cd ~/.openclaw/workspace/drk-inventar
git pull origin main
# Falls DB-Änderungen: Migrations laufen lassen
```

### 8.4 Support

**Bei technischen Problemen:**
1. Log-Dateien prüfen
2. Browser-Konsole checken (F12 → Console)
3. Screenshot vom Fehler machen
4. Admin kontaktieren mit:
   - Was wolltest du tun?
   - Welcher Fehler erschien?
   - Screenshot/Log-Auszug

**Bei fachlichen Fragen:**
- Fahrzeugwart oder DRK-Beauftragten kontaktieren

---

## Anhang A: Tastenkombinationen

| Tastenkombination | Funktion |
|-------------------|----------|
| `Alt + H` | Zurück zur Startseite (Dashboard) |
| `Alt + K` | Container-Prüfung Overview |
| `Alt + F` | Fahrzeugprüfung starten |
| `Alt + V` | Verwaltung |
| `Strg + S` | Speichern (in Formularen) |
| `Esc` | Modal schließen |
| `F5` | Seite neu laden |
| `Strg + F5` | Hard-Refresh (CSS-Cache löschen) |

---

## Anhang B: Datenbank-Schema (für Admins)

### Wichtige Tabellen

**users**
- `id`, `username`, `password_hash`, `full_name`, `email`, `role`, `active`, `created_at`

**vehicles**
- `id`, `name`, `license_plate`, `type`, `location`, `created_at`

**containers**
- `id`, `name`, `vehicle_id`, `type`, `description`, `position`, `created_at`

**compartments**
- `id`, `container_id`, `name`, `description`, `position`, `sort_order`, `created_at`

**products**
- `id`, `name`, `article_number`, `category`, `unit`, `has_expiry`, `description`, `manufacturer`, `created_at`

**compartment_products_target** (Sollbestände)
- `id`, `compartment_id`, `product_id`, `target_quantity`, `created_at`

**compartment_products_actual** (Istbestände)
- `id`, `compartment_id`, `product_id`, `actual_quantity`, `expiry_date`, `status`, `checked_at`, `checked_by`, `notes`

**container_inspections** (Container-Prüfungs-Sessions)
- `id`, `vehicle_id`, `started_by`, `started_at`, `completed_at`, `status`, `notes`

**container_inspection_items** (Container-Status)
- `id`, `container_inspection_id`, `container_id`, `status`, `inspected_by`, `inspector_name`, `started_at`, `completed_at`

**container_inspection_details** (Produkt-Prüfdaten)
- `id`, `container_inspection_item_id`, `compartment_id`, `product_id`, `expected_quantity`, `actual_quantity`, `expiry_date_before`, `expiry_date_after`, `status_before`, `status_after`, `action_taken`, `notes`, `created_at`

### Views

**v_expiring_products**
- Zeigt alle Produkte mit MHD in nächsten 30 Tagen
- Felder: `product_id`, `product_name`, `container_name`, `compartment_name`, `expiry_date`, `days_until_expiry`

**v_last_inspections**
- Zeigt letzte Prüfung pro Fahrzeug
- Felder: `vehicle_id`, `vehicle_name`, `last_inspection_date`, `days_since_inspection`

---

## Anhang C: API-Dokumentation (für Entwickler)

### Container-Inspection APIs

**GET /api/container_inspections.php?action=list**
- Liste aller Container-Prüfungen
- Parameter: `vehicle_id` (optional), `status` (optional)
- Response: JSON Array

**POST /api/container_inspections.php?action=create**
- Neue Container-Prüfung starten
- Body: `{"vehicle_id": 1}`
- Response: `{"success": true, "inspection_id": 123}`

**GET /api/container_inspection_items.php?id=X**
- Container-Item Details
- Response: JSON Object mit Container-Daten

**POST /api/container_inspection_items.php?action=complete&id=X**
- Container abschließen
- Body: `{"inspector_name": "Max Mustermann", "inspection_data": {...}}`
- Response: `{"success": true}`

---

## Glossar

**Compartment** - Fach innerhalb eines Containers (z.B. "Beatmung", "Infusion")

**Container** - Behälter/Tasche/Rucksack im Fahrzeug (z.B. "RUCKSACK-1", "SA1")

**Container-Prüfung** - Einzelne Container unabhängig prüfen (Multi-User)

**Fahrzeugprüfung** - Komplettes Fahrzeug in einem Durchgang prüfen (Single-User)

**Istbestand** - Tatsächlich vorhandene Menge eines Produkts

**MHD** - Mindesthaltbarkeitsdatum (Expiry Date)

**Sollbestand** - Definierte Menge die vorhanden sein SOLL

**Widget** - Informations-Karte auf dem Dashboard

---

**Ende des Benutzerhandbuchs**

**Version:** 2.0 | **Stand:** März 2026  
**Erstellt für:** DRK Ortsverein - Medizinprodukt-Verwaltung  
**System-URL:** http://192.168.177.26:8082
