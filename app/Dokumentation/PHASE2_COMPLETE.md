# Phase 2: Backend APIs - Abgeschlossen ✅

## Übersicht der erstellten/erweiterten Dateien

### 1. includes/auth.php (ERWEITERT)
**Neue Funktionen:**
- `canStartContainerInspection($user)` - Prüft admin/fahrzeugwart
- `canCompleteContainerInspection($user)` - Prüft admin/fahrzeugwart  
- `canInspectContainer($user)` - Prüft admin/fahrzeugwart/kontrolle
- `isKontrolleOnly($user)` - Prüft nur kontrolle-Rolle

---

### 2. api/container_inspections.php (NEU)
**Container-Prüfungs-Sessions verwalten**

#### POST / - Session starten
- Input: `vehicle_id`, `user_id` (optional)
- Permission: admin, fahrzeugwart
- Logik:
  - Prüft ob bereits `in_progress` Session existiert
  - Erstellt Session in `container_inspections`
  - Holt alle Container des Fahrzeugs
  - Erstellt für jeden Container ein `container_inspection_items` Entry (status='pending')
- Response: `session_id`, `container_count`

#### GET ?vehicle_id=X - Offene Sessions für Fahrzeug
- Permission: admin, fahrzeugwart, kontrolle
- Response: Liste aller `in_progress` Sessions mit Vehicle + User Name

#### GET ?id=X - Session Details
- Permission: admin, fahrzeugwart, kontrolle
- Response: Session-Daten + Liste aller Container-Items mit Status

#### PUT ?id=X&action=complete - Session abschließen
- Permission: admin, fahrzeugwart
- Prüft: Alle Items müssen `status='completed'` haben
- Setzt: `completed_at = NOW()`, `status = 'completed'`
- Response: Success-Message

---

### 3. api/container_inspection_items.php (NEU)
**Einzelne Container-Prüfungen verwalten**

#### GET ?container_inspection_id=X - Liste aller Container
- Permission: admin, fahrzeugwart, kontrolle
- Response: Alle Items mit Container-Name, Status, Prüfer-Name, Timestamp

#### POST ?id=X&action=start - Container-Prüfung starten
- Permission: admin, fahrzeugwart, kontrolle
- Prüft: `status = 'pending'`
- Update: `inspected_by = current_user_id`, `started_at = NOW()`, `status = 'in_progress'`

#### POST ?id=X&action=complete - Container-Prüfung abschließen
- Input: `inspector_name` (Freitext für Dummy-Login)
- Permission: admin, fahrzeugwart, kontrolle
- Prüft: `status = 'in_progress'`
- Update: `inspector_name = Input`, `completed_at = NOW()`, `status = 'completed'`

#### GET ?id=X&compartments=1 - Compartments für Container laden
- Permission: admin, fahrzeugwart, kontrolle
- Response: Alle Compartments des Containers gruppiert mit ihren Target-Produkten

---

### 4. api/container_inspection_details.php (NEU)
**Produkt-Prüfdaten speichern und laden**

#### GET ?container_inspection_item_id=X - Produkt-Daten laden
- Permission: admin, fahrzeugwart, kontrolle
- Response: Alle gespeicherten Details mit Compartment + Produkt-Namen

#### POST ?container_inspection_item_id=X - Produkt-Daten speichern
- Input: Array `products[]` mit:
  - `compartment_id`, `product_id`, `actual_quantity`
  - `expiry_date_after`, `status_after`, `action_taken`, `notes`
- Permission: admin, fahrzeugwart, kontrolle
- Logik für jedes Produkt:
  1. Hole `expected_quantity` aus `compartment_products_target`
  2. Hole `expiry_date_before`, `status_before` aus `compartment_products_actual`
  3. INSERT INTO `container_inspection_details`
  4. UPDATE/INSERT `compartment_products_actual` (neue Menge + MHD)
- Response: Anzahl gespeicherter Produkte

---

## API-Endpoint Übersicht

| Datei | Method | Endpoint | Beschreibung |
|-------|--------|----------|--------------|
| container_inspections.php | POST | / | Session starten |
| container_inspections.php | GET | ?vehicle_id=X | Offene Sessions für Fahrzeug |
| container_inspections.php | GET | ?id=X | Session Details |
| container_inspections.php | PUT | ?id=X&action=complete | Session abschließen |
| container_inspection_items.php | GET | ?container_inspection_id=X | Alle Container-Items einer Session |
| container_inspection_items.php | POST | ?id=X&action=start | Container-Prüfung starten |
| container_inspection_items.php | POST | ?id=X&action=complete | Container-Prüfung abschließen |
| container_inspection_items.php | GET | ?id=X&compartments=1 | Compartments + Produkte laden |
| container_inspection_details.php | GET | ?container_inspection_item_id=X | Gespeicherte Prüfdaten laden |
| container_inspection_details.php | POST | ?container_inspection_item_id=X | Prüfdaten speichern |

---

## Features

✅ **Auth-Integration:** Alle APIs prüfen Login + Permissions via `includes/auth.php`  
✅ **CORS-Headers:** Vorbereitet für Frontend-Zugriff  
✅ **Konsistentes Error-Handling:** HTTP-Statuscodes + JSON-Response  
✅ **Database-Class:** Nutzt `Database::getInstance()`  
✅ **Input-Validation:** Parameter-Checks + SQL-Injection-Schutz via Prepared Statements  
✅ **Transaktionslogik:** Automatische Updates von `compartment_products_actual` bei Prüfung  

---

## Testing

Alle Dateien haben PHP-Syntax-Check bestanden:
```
✅ container_inspections.php syntax OK
✅ container_inspection_items.php syntax OK
✅ container_inspection_details.php syntax OK
✅ auth.php syntax OK
```

---

## Nächste Schritte (Phase 3)

1. Frontend erstellen (Container-Prüfung UI)
2. Testszenario durchspielen (Session starten → Container prüfen → Session abschließen)
3. Edge-Cases testen (doppelte Sessions, ungültige Status-Übergänge)
4. Integration mit bestehendem Inventar-System

---

**Erstellt am:** 2026-03-22 00:31 UTC  
**Status:** ✅ Phase 2 komplett
