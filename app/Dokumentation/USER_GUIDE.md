# USER GUIDE - DRK Container Inspection System

**Version:** 1.0  
**Last Updated:** 2026-03-22  
**Target Audience:** DRK Haltern am See - Fahrzeugwarte & Kontrolle-Personal

---

## 📋 ÜBERSICHT

Das Container-Prüfungssystem ermöglicht die systematische Kontrolle aller Container in DRK-Fahrzeugen. Jeder Container wird einzeln geprüft, Produkte mit MHD-Daten erfasst, und ein Prüfer dokumentiert die Kontrolle.

---

## 👥 ROLLEN & BERECHTIGUNGEN

| Rolle | Login-Redirect | Container-Prüfung starten | Container prüfen | Session abschließen | Stats sehen |
|-------|----------------|---------------------------|------------------|---------------------|-------------|
| **Admin** | Dashboard | ✅ Ja | ✅ Ja | ✅ Ja | ✅ Ja |
| **Fahrzeugwart** | Dashboard | ✅ Ja | ✅ Ja | ✅ Ja | ✅ Ja |
| **Kontrolle** | Container-Prüfung | ❌ Nein | ✅ Ja | ❌ Nein | ❌ Nein |
| **User** | Dashboard | ❌ Nein | ❌ Nein | ❌ Nein | ❌ Nein |

---

## 🔐 LOGIN

### URL
```
http://192.168.177.26:8082
```

### Test-Accounts
| Username | Passwort | Rolle |
|----------|----------|-------|
| `kontrolle` | `kontrolle2026` | Kontrolle-Personal |
| `pb` | _(ask admin)_ | Admin |
| `sm` | _(ask admin)_ | Admin |
| `ak` | _(ask admin)_ | Admin |

### Login-Verhalten
- **kontrolle** wird zu "Container-Prüfung" weitergeleitet
- **admin/fahrzeugwart** wird zu "Dashboard" weitergeleitet
- Bei falschen Zugangsdaten: Max. 5 Versuche → 15 Min Sperre

---

## 🚑 WORKFLOW: CONTAINER-PRÜFUNG

### PHASE 1: Session starten (Fahrzeugwart/Admin)

1. **Login als Fahrzeugwart/Admin**
2. **Navigation:**
   - Dashboard → "Zur Container-Prüfung" Button
   - ODER: URL `?page=container_inspection_start`

3. **Fahrzeug auswählen:**
   - Klick auf "Prüfung starten" bei gewünschtem Fahrzeug (z.B. RTW 1)
   - System erstellt neue Prüfungs-Session
   - Redirect zu `?page=container_inspection_overview&session_id=X`

4. **Übersicht:**
   - Liste aller Container für dieses Fahrzeug
   - Status: Nicht geprüft / In Bearbeitung / Geprüft ✅
   - Prüfer-Name (wenn geprüft)
   - Button "Container prüfen" für jeden Container

---

### PHASE 2: Container prüfen (Fahrzeugwart/Admin/Kontrolle)

1. **Container auswählen:**
   - Klick auf "Container prüfen" (z.B. "Notfallrucksack")
   - Öffnet `?page=container_inspection_check&session_id=X&container_id=Y`

2. **Produkte erfassen:**
   - **Für jedes Fach im Container:**
     - Fachname wird angezeigt (z.B. "Beatmung")
     - Produkte einzeln erfassen:
       - **Produkt wählen** (Dropdown: Beatmungsbeutel, Masken, etc.)
       - **Menge eingeben** (z.B. 2)
       - **MHD eingeben** (z.B. 2027-05-15)
       - **+ Hinzufügen** Button
     - Liste zeigt erfasste Produkte
     - **Löschen** Button wenn Fehler

3. **Container abschließen:**
   - Button "Container abschließen" unten
   - **Eingabe Prüfer-Name** (Pflichtfeld!)
   - Beispiel: "Max Mustermann"
   - Submit → Container markiert als ✅ geprüft

4. **Zurück zur Übersicht:**
   - Automatisch zurück zu `container_inspection_overview`
   - Container zeigt jetzt grünen Haken ✅
   - Prüfer-Name wird angezeigt

---

### PHASE 3: Weitere Container prüfen

- **Wiederholen von Phase 2** für alle anderen Container
- Übersicht zeigt Fortschritt: "3 / 7 Container geprüft"
- Kontrolle-User können parallel prüfen (Mehrere Browser/Tablets)

---

### PHASE 4: Session abschließen (Fahrzeugwart/Admin)

1. **Alle Container geprüft?**
   - Check ob alle Container grünen Haken haben ✅
   - Falls nicht: Warnhinweis "Noch nicht alle Container geprüft"

2. **Prüfung abschließen:**
   - Button "Gesamte Prüfung abschließen" (nur wenn alle ✅)
   - Eingabe **Abschließender Name** (z.B. Fahrzeugwart-Name)
   - Submit → Session Status: `completed`
   - `completed_at` Timestamp gesetzt

3. **Redirect:**
   - Zurück zu `container_inspection_start`
   - Session verschwindet aus "Laufende Prüfungen"
   - Erscheint in Statistiken

---

## 📊 STATISTIKEN & REPORTS

### Zugriff (Admin/Fahrzeugwart)

**Dashboard → Schnellstatistiken:**
- Container-Prüfungen gesamt
- Container-Prüfungen diesen Monat
- Container geprüft (Monat)

**API-Zugriff:**
```
GET /api/container_inspection_stats.php?date_from=2026-03-01&date_to=2026-03-31
```

**Response-Daten:**
- **by_vehicle:** Prüfungen pro Fahrzeug
- **by_inspector:** Statistik pro Prüfer
- **avg_container_duration:** Durchschnittliche Prüfzeit
- **mhd_warnings:** Ablaufende Produkte gefunden

**Reports-Seite:**
- Navigation: `?page=reports`
- Export als PDF/Excel
- Filter: Zeitraum, Fahrzeug, etc.

---

## 🎨 UI-BESCHREIBUNG

### Container-Prüfung Start
```
┌─────────────────────────────────────────────┐
│ 🗂️ Container-Prüfungen                     │
├─────────────────────────────────────────────┤
│                                             │
│ [➕ Neue Prüfung starten]                  │
│                                             │
│ Laufende Prüfungen:                         │
│ ┌─────────────────────────────────────┐   │
│ │ 🚑 RTW 1                            │   │
│ │ 3 / 7 Container geprüft             │   │
│ │ Gestartet von Max M. am 21.03.      │   │
│ │ [Fortsetzen]                        │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ Fahrzeuge:                                  │
│ ┌─────────────────────────────────────┐   │
│ │ 🚑 RTW 1                            │   │
│ │ Letzte Prüfung: vor 3 Tagen         │   │
│ │ [Prüfung starten]                   │   │
│ └─────────────────────────────────────┘   │
│ ┌─────────────────────────────────────┐   │
│ │ 🚑 KTW 2                            │   │
│ │ Letzte Prüfung: vor 8 Tagen         │   │
│ │ [Prüfung starten]                   │   │
│ └─────────────────────────────────────┘   │
└─────────────────────────────────────────────┘
```

### Container-Übersicht (Session aktiv)
```
┌─────────────────────────────────────────────┐
│ 🚑 RTW 1 - Container-Prüfung               │
│ Session gestartet von Max M. am 21.03.      │
├─────────────────────────────────────────────┤
│ Fortschritt: 3 / 7 Container                │
│ [████████░░░░░░░░░░░░░░] 43%               │
├─────────────────────────────────────────────┤
│ Container:                                  │
│ ┌─────────────────────────────────────┐   │
│ │ ✅ Notfallrucksack                  │   │
│ │ Geprüft von Anna Schmidt            │   │
│ │ 21.03.2026 14:23                    │   │
│ └─────────────────────────────────────┘   │
│ ┌─────────────────────────────────────┐   │
│ │ ⏳ Sauerstoffkoffer                 │   │
│ │ In Bearbeitung von Max M.           │   │
│ │ [Fortsetzen]                        │   │
│ └─────────────────────────────────────┘   │
│ ┌─────────────────────────────────────┐   │
│ │ ⚪ Beatmungskoffer                  │   │
│ │ Noch nicht geprüft                  │   │
│ │ [Container prüfen]                  │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ [Zurück] [Prüfung abschließen] (disabled)  │
└─────────────────────────────────────────────┘
```

### Container prüfen
```
┌─────────────────────────────────────────────┐
│ Container: Notfallrucksack                  │
│ Fahrzeug: RTW 1                             │
├─────────────────────────────────────────────┤
│ Fach: Beatmung                              │
│ ┌─────────────────────────────────────┐   │
│ │ Produkt: [Beatmungsbeutel ▼]       │   │
│ │ Menge:   [2]                        │   │
│ │ MHD:     [2027-05-15]               │   │
│ │ [➕ Hinzufügen]                     │   │
│ └─────────────────────────────────────┘   │
│ Erfasste Produkte:                          │
│ • Beatmungsbeutel (2x) - MHD 2027-05-15 🗑️ │
│ • Maske Gr.3 (5x) - MHD 2026-08-20 🗑️      │
│                                             │
│ Fach: Verbandsmaterial                      │
│ ┌─────────────────────────────────────┐   │
│ │ Produkt: [Kompressen ▼]            │   │
│ │ Menge:   [10]                       │   │
│ │ MHD:     [2026-12-31]               │   │
│ │ [➕ Hinzufügen]                     │   │
│ └─────────────────────────────────────┘   │
│                                             │
│ [Zurück] [Container abschließen]           │
└─────────────────────────────────────────────┘
```

### Abschluss-Dialog
```
┌──────────────────────────────────────┐
│ Container abschließen                │
├──────────────────────────────────────┤
│ Bitte Namen des Prüfers eingeben:    │
│                                      │
│ [Max Mustermann____________]         │
│                                      │
│ [Abbrechen] [Abschließen]            │
└──────────────────────────────────────┘
```

---

## 🔍 TROUBLESHOOTING

### Problem: "Keine Berechtigung"
**Ursache:** Rolle `user` hat keinen Zugriff  
**Lösung:** Admin kontaktieren → Rolle zu `kontrolle`, `fahrzeugwart` oder `admin` ändern

### Problem: Container nicht in Liste
**Ursache:** Container im System nicht angelegt  
**Lösung:** `?page=management` → Fahrzeug → Container hinzufügen

### Problem: Produkt fehlt in Dropdown
**Ursache:** Produkt nicht in Datenbank  
**Lösung:** `?page=management` → Produkte → Neues Produkt anlegen

### Problem: Session verschwindet
**Ursache:** Status `completed` gesetzt  
**Lösung:** Neue Prüfung starten (alte Sessions in Statistiken sichtbar)

### Problem: "Container-Items nicht geladen"
**Ursache:** Session-ID falsch oder nicht vorhanden  
**Lösung:** Zurück zu `container_inspection_start` → Session neu auswählen

---

## 📞 SUPPORT

**Bei technischen Problemen:**
- Admin-Team kontaktieren: pb, sm, ak
- Log-Files checken: `/tmp/drk-inventar-web.log`
- Browser-Konsole öffnen (F12) → Fehlermeldungen screenshotten

**Bei Fragen zum Workflow:**
- Fahrzeugwart-Leitung kontaktieren
- Dokumentation: `TESTING_RESULTS.md`, `API_DOCUMENTATION.md`

---

_Version 1.0 | DRK Stadtverband Haltern am See e.V._
