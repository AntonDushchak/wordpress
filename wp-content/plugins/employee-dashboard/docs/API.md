# 📡 Employee Dashboard - REST API Dokumentation

Die Employee Dashboard API ermöglicht es, Widgets zu verwalten und externe Integrationen vorzunehmen.

## 🚀 API-Endpunkte

### 📌 Widgets abrufen
**GET** `/wp-json/employee-dashboard/v1/widgets`  
Antwort:
```json
[
  {"id": 1, "name": "Zeiterfassung"},
  {"id": 2, "name": "Urlaubsplanung"}
]
```

### 📌 Widget hinzufügen
**POST** `/wp-json/employee-dashboard/v1/add-widget`  
Erwarteter JSON-Body:
```json
{"name": "Neues Widget"}
```
Antwort:
```json
{"message": "Widget hinzugefügt"}
```

### 📌 Widget löschen
**DELETE** `/wp-json/employee-dashboard/v1/delete-widget/{id}`  
Antwort:
```json
{"message": "Widget gelöscht"}
```

## 🛠 Authentifizierung
Alle API-Calls benötigen **einen authentifizierten Benutzer mit ausreichenden Rechten**.
