# API DOCUMENTATION - DRK Container Inspection System

**Version:** 1.0  
**Last Updated:** 2026-03-22  
**Base URL:** `http://192.168.177.26:8082`

---

## 🔐 AUTHENTICATION

All API endpoints require authentication via session cookies.

**Login:**
```http
POST /api/auth-login.php
Content-Type: application/json

{
  "username": "kontrolle",
  "password": "kontrolle2026"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 4,
    "username": "kontrolle",
    "full_name": "Kontrolle User",
    "role": "kontrolle"
  }
}
```

**Session Cookie:**
- Cookie name: `PHPSESSID`
- Valid for: 24 hours (SESSION_LIFETIME)
- Include in all subsequent requests

---

## 📦 CONTAINER INSPECTION ENDPOINTS

### 1. Container Inspections (Sessions)

#### Create New Inspection Session
```http
POST /api/container_inspections.php
Content-Type: application/json

{
  "vehicle_id": 1,
  "user_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "session_id": 5,
  "message": "Container-Prüfung gestartet"
}
```

#### Get All Sessions
```http
GET /api/container_inspections.php
```

**Response:**
```json
{
  "success": true,
  "sessions": [
    {
      "id": 5,
      "vehicle_id": 1,
      "vehicle_name": "RTW 1",
      "started_by": 1,
      "starter_name": "Admin User",
      "started_at": "2026-03-21 14:30:00",
      "status": "in_progress",
      "completed_at": null,
      "completed_by": null,
      "total_containers": 7,
      "checked_containers": 3
    }
  ]
}
```

#### Get Session by ID
```http
GET /api/container_inspections.php?session_id=5
```

#### Complete Session
```http
POST /api/container_inspections.php
Content-Type: application/json

{
  "action": "complete",
  "session_id": 5,
  "completed_by_name": "Max Mustermann"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Container-Prüfung abgeschlossen"
}
```

---

### 2. Container Inspection Items

#### Get Items for Session
```http
GET /api/container_inspection_items.php?session_id=5
```

**Response:**
```json
{
  "success": true,
  "items": [
    {
      "id": 12,
      "session_id": 5,
      "container_id": 3,
      "container_name": "Notfallrucksack",
      "status": "completed",
      "started_at": "2026-03-21 14:32:00",
      "completed_at": "2026-03-21 14:45:00",
      "inspector_id": 4,
      "inspector_name": "Anna Schmidt"
    },
    {
      "id": 13,
      "session_id": 5,
      "container_id": 4,
      "container_name": "Sauerstoffkoffer",
      "status": "pending",
      "started_at": null,
      "completed_at": null,
      "inspector_id": null,
      "inspector_name": null
    }
  ]
}
```

#### Start Container Check
```http
POST /api/container_inspection_items.php
Content-Type: application/json

{
  "session_id": 5,
  "container_id": 4,
  "user_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "item_id": 14,
  "message": "Container-Check gestartet"
}
```

#### Complete Container Check
```http
POST /api/container_inspection_items.php
Content-Type: application/json

{
  "action": "complete",
  "item_id": 14,
  "inspector_name": "Max Mustermann"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Container-Check abgeschlossen"
}
```

---

### 3. Container Inspection Details (Products)

#### Get Details for Container Item
```http
GET /api/container_inspection_details.php?inspection_item_id=12
```

**Response:**
```json
{
  "success": true,
  "details": [
    {
      "id": 45,
      "inspection_item_id": 12,
      "compartment_id": 8,
      "compartment_name": "Beatmung",
      "product_id": 23,
      "product_name": "Beatmungsbeutel",
      "quantity": 2,
      "expiry_date": "2027-05-15",
      "days_until_expiry": 419
    },
    {
      "id": 46,
      "inspection_item_id": 12,
      "compartment_id": 8,
      "compartment_name": "Beatmung",
      "product_id": 24,
      "product_name": "Maske Gr. 3",
      "quantity": 5,
      "expiry_date": "2026-08-20",
      "days_until_expiry": 151
    }
  ]
}
```

#### Add Product Detail
```http
POST /api/container_inspection_details.php
Content-Type: application/json

{
  "inspection_item_id": 14,
  "compartment_id": 9,
  "product_id": 25,
  "quantity": 3,
  "expiry_date": "2027-01-15"
}
```

**Response:**
```json
{
  "success": true,
  "detail_id": 47,
  "message": "Produkt erfasst"
}
```

#### Delete Product Detail
```http
POST /api/container_inspection_details.php
Content-Type: application/json

{
  "action": "delete",
  "detail_id": 47
}
```

**Response:**
```json
{
  "success": true,
  "message": "Produkt gelöscht"
}
```

---

### 4. Container Inspection Statistics

#### Get Stats for Period
```http
GET /api/container_inspection_stats.php?date_from=2026-03-01&date_to=2026-03-31
```

**Query Parameters:**
- `date_from` (optional): Start date (YYYY-MM-DD), default: 30 days ago
- `date_to` (optional): End date (YYYY-MM-DD), default: today

**Response:**
```json
{
  "success": true,
  "period": {
    "from": "2026-03-01",
    "to": "2026-03-31"
  },
  "total": {
    "total_sessions": 12,
    "completed_sessions": 10,
    "active_sessions": 2,
    "total_containers_inspected": 67,
    "unique_inspectors": 5
  },
  "by_vehicle": [
    {
      "id": 1,
      "vehicle_name": "RTW 1",
      "total_inspections": 5,
      "completed_inspections": 4,
      "total_containers_checked": 28,
      "avg_duration_minutes": 45.5
    }
  ],
  "by_inspector": [
    {
      "id": 4,
      "full_name": "Anna Schmidt",
      "inspections_started": 3,
      "containers_checked": 18,
      "avg_session_duration": 52.3
    }
  ],
  "avg_container_duration": {
    "avg_minutes": 8.5,
    "min_minutes": 3.2,
    "max_minutes": 15.7,
    "total_completed": 67
  },
  "mhd_warnings": [
    {
      "id": 123,
      "product_name": "Beatmungsbeutel",
      "vehicle_name": "RTW 1",
      "container_name": "Notfallrucksack",
      "compartment_name": "Beatmung",
      "expiry_date": "2026-04-15",
      "days_until_expiry": 24,
      "found_at": "2026-03-21 14:45:00",
      "inspector_name": "Anna Schmidt"
    }
  ]
}
```

---

## 🚗 VEHICLE & STRUCTURE ENDPOINTS

### 5. Get Vehicles
```http
GET /api/get-vehicles.php
```

**Response:**
```json
{
  "success": true,
  "vehicles": [
    {
      "id": 1,
      "name": "RTW 1",
      "description": "Rettungswagen 1",
      "created_at": "2026-01-15 10:00:00"
    }
  ]
}
```

---

### 6. Get Containers for Vehicle
```http
GET /api/get-containers.php?vehicle_id=1
```

**Response:**
```json
{
  "success": true,
  "containers": [
    {
      "id": 3,
      "vehicle_id": 1,
      "name": "Notfallrucksack",
      "position": 1,
      "created_at": "2026-01-15 10:05:00"
    },
    {
      "id": 4,
      "vehicle_id": 1,
      "name": "Sauerstoffkoffer",
      "position": 2,
      "created_at": "2026-01-15 10:06:00"
    }
  ]
}
```

---

### 7. Get Compartments for Container
```http
GET /api/get-compartments.php?container_id=3
```

**Response:**
```json
{
  "success": true,
  "compartments": [
    {
      "id": 8,
      "container_id": 3,
      "name": "Beatmung",
      "position": 1,
      "created_at": "2026-01-15 10:10:00"
    },
    {
      "id": 9,
      "container_id": 3,
      "name": "Verbandsmaterial",
      "position": 2,
      "created_at": "2026-01-15 10:11:00"
    }
  ]
}
```

---

### 8. Get Products for Compartment
```http
GET /api/get-compartment-products.php?compartment_id=8
```

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 45,
      "compartment_id": 8,
      "product_id": 23,
      "product_name": "Beatmungsbeutel",
      "quantity": 2,
      "expiry_date": "2027-05-15",
      "days_until_expiry": 419
    }
  ]
}
```

---

## 📊 REPORTS & EXPORTS

### 9. Export Report
```http
GET /api/export-report.php?format=pdf&report_type=inspections&date_from=2026-03-01&date_to=2026-03-31
```

**Query Parameters:**
- `format`: `pdf` or `excel`
- `report_type`: `expiring` or `inspections`
- `date_from`: Start date (YYYY-MM-DD)
- `date_to`: End date (YYYY-MM-DD)

**Response:**
- PDF: `Content-Type: application/pdf`
- Excel: `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- File download triggered

---

### 10. Get Expiring Products
```http
GET /api/expiring-products.php?weeks=4
```

**Query Parameters:**
- `weeks` (optional): Number of weeks to look ahead, default: 4

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 45,
      "product_name": "Beatmungsbeutel",
      "vehicle_name": "RTW 1",
      "container_name": "Notfallrucksack",
      "compartment_name": "Beatmung",
      "quantity": 2,
      "expiry_date": "2026-04-15",
      "days_until_expiry": 24
    }
  ]
}
```

---

## 🔒 ROLE-BASED ACCESS CONTROL

| Endpoint | Admin | Fahrzeugwart | Kontrolle | User |
|----------|-------|--------------|-----------|------|
| **container_inspections.php (POST create)** | ✅ | ✅ | ❌ | ❌ |
| **container_inspections.php (POST complete)** | ✅ | ✅ | ❌ | ❌ |
| **container_inspections.php (GET)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_items.php (POST start)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_items.php (POST complete)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_items.php (GET)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_details.php (POST add)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_details.php (POST delete)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_details.php (GET)** | ✅ | ✅ | ✅ | ❌ |
| **container_inspection_stats.php** | ✅ | ✅ | ❌ | ❌ |

---

## ⚠️ ERROR RESPONSES

### 401 Unauthorized
```json
{
  "error": "Nicht eingeloggt"
}
```

### 403 Forbidden
```json
{
  "error": "Keine Berechtigung"
}
```

### 404 Not Found
```json
{
  "error": "Session nicht gefunden"
}
```

### 400 Bad Request
```json
{
  "error": "Fehlende Parameter: vehicle_id"
}
```

### 500 Internal Server Error
```json
{
  "error": "Fehler beim Laden der Daten",
  "details": "SQL error message"
}
```

---

## 🧪 TESTING WITH CURL

### Login
```bash
curl -X POST http://192.168.177.26:8082/api/auth-login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"kontrolle","password":"kontrolle2026"}' \
  -c cookies.txt
```

### Start Inspection Session
```bash
curl -X POST http://192.168.177.26:8082/api/container_inspections.php \
  -H "Content-Type: application/json" \
  -d '{"vehicle_id":1,"user_id":4}' \
  -b cookies.txt
```

### Get Session Items
```bash
curl http://192.168.177.26:8082/api/container_inspection_items.php?session_id=5 \
  -b cookies.txt
```

### Start Container Check
```bash
curl -X POST http://192.168.177.26:8082/api/container_inspection_items.php \
  -H "Content-Type: application/json" \
  -d '{"session_id":5,"container_id":3,"user_id":4}' \
  -b cookies.txt
```

### Add Product Detail
```bash
curl -X POST http://192.168.177.26:8082/api/container_inspection_details.php \
  -H "Content-Type: application/json" \
  -d '{"inspection_item_id":12,"compartment_id":8,"product_id":23,"quantity":2,"expiry_date":"2027-05-15"}' \
  -b cookies.txt
```

### Complete Container Check
```bash
curl -X POST http://192.168.177.26:8082/api/container_inspection_items.php \
  -H "Content-Type: application/json" \
  -d '{"action":"complete","item_id":12,"inspector_name":"Max Mustermann"}' \
  -b cookies.txt
```

### Complete Session
```bash
curl -X POST http://192.168.177.26:8082/api/container_inspections.php \
  -H "Content-Type: application/json" \
  -d '{"action":"complete","session_id":5,"completed_by_name":"Admin User"}' \
  -b cookies.txt
```

### Get Stats
```bash
curl "http://192.168.177.26:8082/api/container_inspection_stats.php?date_from=2026-03-01&date_to=2026-03-31" \
  -b cookies.txt
```

---

## 📝 NOTES

**Database:**
- Engine: MariaDB 10.6
- Character Set: utf8mb4
- Collation: utf8mb4_unicode_ci
- Timezone: Europe/Berlin

**API Design:**
- RESTful principles
- JSON request/response bodies
- Session-based authentication
- CSRF protection on state-changing operations
- Input validation on all endpoints
- SQL injection protection via prepared statements

**Rate Limiting:**
- Login: 5 attempts per 15 minutes per session
- Other endpoints: No rate limiting (authenticated users only)

---

_Version 1.0 | DRK Stadtverband Haltern am See e.V._
