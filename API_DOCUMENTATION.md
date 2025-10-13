# WordPress Job Board Plugin - Next.js API Integration Dokumentation

## Übersicht der Änderungen

Das WordPress-Plugin sendet Bewerbungsdaten an die Next.js API unter der Adresse `http://192.168.1.102:3000/api/admin/applications` mit folgenden Authentifizierungsparametern:
- `api_key`: `wp_admin_key_2025`
- `user_id`: `cmg1kapf90000v32kqqp0f0f7`

## Spezielle Feldtypen

Das Plugin unterstützt 5 spezielle Feldtypen mit festen Namen:

1. **position** - Gewünschte Positionen (mehrfach)
2. **bildung** - Bildung/Ausbildung (mehrfach)
3. **berufserfahrung** - Berufserfahrung (mehrfach)
4. **sprachkenntnisse** - Sprachkenntnisse (mehrfach)
5. **fuehrerschein** - Führerschein (Checkbox-Liste)

## JSON-Datenstruktur für jedes spezielle Feld

### 1. Position (Gewünschte Positionen)

**Feldname:** `position`
**Typ:** Array von Objekten

```json
{
  "positions": [
    {
      "position": "Software Developer",
      "priority": 1
    },
    {
      "position": "Full Stack Developer", 
      "priority": 2
    },
    {
      "position": "DevOps Engineer",
      "priority": 3
    }
  ]
}
```

**Felder:**
- `position` (string) - Name der gewünschten Position
- `priority` (integer) - Priorität der Position (1, 2, 3)

### 2. Bildung (Bildung/Ausbildung)

**Feldname:** `bildung`
**Typ:** Array von Objekten

```json
{
  "bildung": [
    {
      "institution": "Technische Universität München",
      "degree": "Bachelor of Science",
      "start_date": "2018-09-01",
      "end_date": "2022-07-31",
      "is_current": 0
    },
    {
      "institution": "Gymnasium München",
      "degree": "Abitur",
      "start_date": "2016-09-01", 
      "end_date": "2018-06-30",
      "is_current": 0
    },
    {
      "institution": "TU München",
      "degree": "Master of Science",
      "start_date": "2022-09-01",
      "end_date": "",
      "is_current": 1
    }
  ]
}
```

**Felder:**
- `institution` (string) - Name der Bildungseinrichtung
- `degree` (string) - Art/Name des Abschlusses oder Diploms  
- `start_date` (string) - Startdatum der Ausbildung (YYYY-MM-DD)
- `end_date` (string) - Enddatum der Ausbildung (YYYY-MM-DD oder leer)
- `is_current` (integer) - Flag für aktuelle Ausbildung (1 = noch in Ausbildung, 0 = abgeschlossen)

### 3. Berufserfahrung (Berufserfahrung)

**Feldname:** `berufserfahrung`
**Typ:** Array von Objekten

```json
{
  "berufserfahrung": [
    {
      "position": "Software Developer",
      "company": "Tech Solutions GmbH",
      "start_date": "2022-08-01",
      "end_date": "2024-12-31",
      "is_current": 0
    },
    {
      "position": "Junior Developer",
      "company": "StartUp Berlin",
      "start_date": "2020-06-01",
      "end_date": "2022-07-31", 
      "is_current": 0
    },
    {
      "position": "Senior Developer",
      "company": "Big Corp AG",
      "start_date": "2025-01-01",
      "end_date": "",
      "is_current": 1
    }
  ]
}
```

**Felder:**
- `position` (string) - Position/Stelle
- `company` (string) - Firmenname
- `start_date` (string) - Startdatum der Tätigkeit (YYYY-MM-DD)
- `end_date` (string) - Enddatum der Tätigkeit (YYYY-MM-DD oder leer)
- `is_current` (integer) - Flag für aktuelle Tätigkeit (1 = derzeit tätig, 0 = beendet)

### 4. Sprachkenntnisse (Sprachkenntnisse)

**Feldname:** `sprachkenntnisse`
**Typ:** Array von Objekten

```json
{
  "sprachkenntnisse": [
    {
      "language": "Deutsch",
      "level": "C2"
    },
    {
      "language": "Englisch", 
      "level": "B2"
    },
    {
      "language": "Französisch",
      "level": "A2"
    },
    {
      "language": "Russisch",
      "level": "C1"
    }
  ]
}
```

**Felder:**
- `language` (string) - Name der Sprache
- `level` (string) - Sprachniveau

**Verfügbare Sprachen:**
- Deutsch, Englisch, Französisch, Spanisch, Italienisch, Russisch, Türkisch, Polnisch, Niederländisch, Portugiesisch, Chinesisch, Japanisch, Arabisch

**Verfügbare Niveaustufen:**
- A1 (Anfänger)
- A2 (Grundkenntnisse) 
- B1 (Mittlere Kenntnisse)
- B2 (Gute Kenntnisse)
- C1 (Sehr gute Kenntnisse)
- C2 (Muttersprachlich)

### 5. Führerschein (Führerschein)

**Feldname:** `fuehrerschein`
**Typ:** Array von Strings (Checkbox-Werte)

```json
{
  "fuehrerschein": [
    "Klasse B (PKW)",
    "Klasse C (LKW)",
    "Klasse D (Bus)"
  ]
}
```

**Standardoptionen:**
- Klasse AM (Moped)
- Klasse A1 (Leichtkraftrad)
- Klasse A2 (Kraftrad)
- Klasse A (Schweres Kraftrad)
- Klasse B (PKW)
- Klasse BE (PKW mit Anhänger)
- Klasse C1 (Kleiner LKW)
- Klasse C (LKW)
- Klasse CE (LKW mit Anhänger)
- Klasse D1 (Kleinbusse)
- Klasse D (Bus)
- Klasse DE (Bus mit Anhänger)

## Vollständiges JSON-Beispiel einer Bewerbung

```json
{
  "template_id": 123,
  "wordpress_application_id": 456,
  "user_id": "cmg1kapf90000v32kqqp0f0f7",
  "api_key": "wp_admin_key_2025",
  "filled_data": {
    "full_name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "address": "Musterstraße 123, 80331 München",
    "cover_letter": "Sehr geehrte Damen und Herren...",
    "salary_expectation": "€55,000 - €65,000",
    "availability_type": "sofort",
    "availability_date": "2025-01-15",
    
    "positions": [
      {
        "position": "Software Developer",
        "priority": 1
      },
      {
        "position": "Full Stack Developer",
        "priority": 2
      }
    ],
    
    "bildung": [
      {
        "institution": "TU München",
        "degree": "Bachelor Informatik",
        "start_date": "2018-09-01",
        "end_date": "2022-07-31",
        "is_current": 0
      }
    ],
    
    "berufserfahrung": [
      {
        "position": "Junior Developer",
        "company": "Tech GmbH",
        "start_date": "2022-08-01",
        "end_date": "2024-12-31",
        "is_current": 0
      }
    ],
    
    "sprachkenntnisse": [
      {
        "language": "Deutsch",
        "level": "C2"
      },
      {
        "language": "Englisch",
        "level": "B2"
      }
    ],
    
    "fuehrerschein": [
      "Klasse B (PKW)",
      "Klasse BE (PKW mit Anhänger)"
    ]
  }
}
```

## GDPR Compliance

Wichtig! Felder, die als persönliche Daten markiert sind (`is_personal = true`) im Template, werden NICHT an die externe API gesendet. Sie werden nur in der lokalen WordPress-Datenbank gespeichert.

Persönliche Daten werden immer aus `filled_data` ausgeschlossen, bevor sie an die Next.js API gesendet werden.

## HTTP-Request Headers

```
POST /api/admin/applications
Content-Type: application/json
User-Agent: WordPress/Neo-Job-Board-Plugin
```

## API Endpunkte

1. **Template erstellen/aktualisieren:**
   - URL: `http://192.168.1.102:3000/api/admin/templates` 
   - Method: POST
   - Auth: api_key + user_id

2. **Ausgefüllte Bewerbung senden:**
   - URL: `http://192.168.1.102:3000/api/admin/applications`
   - Method: POST  
   - Auth: api_key + user_id

## Updates für die Next.js API

Ihre Next.js API sollte aktualisiert werden, um folgendes zu unterstützen:

1. **Neues Feld `positions`** (Array von Objekten mit position und priority)
2. **Mehrfache Einträge** für alle speziellen Felder
3. **Korrekte Verarbeitung** von Datenstrukturen mit verschachtelten Objekten
4. **Validierung** spezieller Felder gemäß ihren Schemas

Alle speziellen Felder haben feste Namen und können nicht vom Benutzer im WordPress-Admin geändert werden.