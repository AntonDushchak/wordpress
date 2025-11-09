# Neo Dashboard WordPress Projekt

Modernes WordPress-Dashboard auf Basis des Neo Dashboard Core Plugins.

## 🚀 Schnellstart

### Voraussetzungen
- Docker & Docker Compose
- Git
- Freie Ports: 8080 (WP), 3306 (DB)

### Installation mit Docker

1. Repository klonen und Verzeichnis betreten.
2. `docker-compose up -d` ausführen.
3. WordPress unter `http://localhost:8080` installieren oder bestehende `wp-config.php` nutzen.
4. In `http://localhost:8080/wp-admin` die Plugins aktivieren:
   - Neo Dashboard Core (Pflicht)
   - Neo Umfrage
   - Neo Calendar
   - Job Board Integration (optional)
   - Neo Domain Changer (optional)
5. Dashboard im Menüpunkt „Neo Dashboard“ oder über `http://localhost:8080/neo-dashboard` öffnen.

### Docker Befehle

```bash
docker-compose up -d          # Start
docker-compose down           # Stopp
docker-compose logs -f        # Logs
docker exec -it wordpress-app bash
docker exec wordpress-db mysqldump -uwordpress -pwordpress wordpress > backup.sql
docker-compose up -d --build  # Rebuild
```

## 📁 Projektstruktur

```
wordpress/
├── wp-content/
│   ├── plugins/
│   │   ├── neo-dashboard/              # Core Framework
│   │   │   ├── src/
│   │   │   ├── templates/
│   │   │   ├── assets/
│   │   │   └── README.md
│   │   ├── neo-umfrage/                # Umfragen
│   │   ├── neo-calendar/               # Kalender
│   │   ├── job-board-integration/      # Bewerberbörse
│   │   └── neo-domain-changer/         # Domainwechsel
│   └── themes/
│       ├── global-responsive.css
│       └── responsive-functions.php
├── docker-compose.yml
└── README.md
```

## 🔧 Konfiguration

### Ports ändern (falls erforderlich)
In `docker-compose.yml` bei Bedarf anpassen:
```yaml
services:
  wordpress:
    ports:
      - "8081:80"  # Externer Port:Interner Port
  
  db:
    ports:
      - "3307:3306"
```

### Domain/URL ändern
Domain in WP-Admin über Neo Domain Changer setzen oder in `wp-config.php` anpassen:
```php
define('WP_HOME','http://your-domain.com');
define('WP_SITEURL','http://your-domain.com');
```

### Docker Environment Variables
Wichtige Variablen in `docker-compose.yml`:
```yaml
environment:
  WORDPRESS_DB_HOST: db
  WORDPRESS_DB_USER: wordpress
  WORDPRESS_DB_PASSWORD: wordpress
  WORDPRESS_DB_NAME: wordpress
```

## 🌟 Features

### Core Dashboard
- Bootstrap 5 UI, Dark/Light Theme
- Responsive Widgets und Sektionen
- REST-API, Benachrichtigungen, Admin-Menü-Link

### Neo Umfrage
- Vorlagenbasierte Formulare mit gängigen Feldtypen
- DataTables-Übersicht und Statistikseite
- Filter nach Vorlagen und Benutzern

### Neo Calendar
- FullCalendar mit Modal-Events
- Optimiert für Desktop & Mobile

### Neo Domain Changer
- Domainwechsel per Admin-Oberfläche
- Validierung, Logging und Skriptausführung

### Job Board Integration
- Sync mit Bewerberbörse via REST-API
- Vorlagen- und Bewerbungsverwaltung im Dashboard
- Automatische Cron-Synchronisation & Benachrichtigungen
## 🛠️ Entwicklung

### Neue Sektionen hinzufügen
```php
add_action('neo_dashboard_init', function () {
    do_action('neo_dashboard_register_section', [
        'slug'          => 'my-section',
        'label'         => 'Meine Sektion',
        'icon'          => 'bi-star',
        'template_path' => plugin_dir_path(__FILE__) . 'templates/my-section.php',
    ]);
});
```

### Widgets hinzufügen
```php
add_action('neo_dashboard_init', function () {
    do_action('neo_dashboard_register_widget', [
        'id'       => 'my-widget',
        'label'    => 'Mein Widget',
        'icon'     => 'bi-graph-up',
        'priority' => 10,
        'callback' => fn () => print '<p>Widget-Inhalt</p>',
    ]);
});
```

## 🗄️ Datenbankstruktur

### Neo Umfrage
```sql
wp_neo_umfrage_templates
wp_neo_umfrage_surveys
wp_neo_umfrage_survey_values
```

### Neo Calendar
```sql
wp_neo_calendar_events
```

### Job Board Integration
```sql
wp_neo_job_board_templates
wp_neo_job_board_applications
wp_neo_job_board_application_data
wp_neo_job_board_application_details
wp_neo_job_board_files
wp_neo_job_board_api_logs
wp_neo_job_board_contact_requests
```

## 📚 Dokumentation

- [Neo Dashboard Core](wp-content/plugins/neo-dashboard/README.md)
- [Neo Umfrage](wp-content/plugins/neo-umfrage/)
- [Neo Calendar](wp-content/plugins/neo-calendar/)
- [Job Board Integration](wp-content/plugins/job-board-integration/)
- [Neo Domain Changer](wp-content/plugins/neo-domain-changer/README.md)

## 🐛 Fehlerbehebung

- **Plugin aktiviert sich nicht**: PHP ≥ 8.1 prüfen, Rechte setzen, Cache leeren.
- **Dashboard nicht erreichbar**: Seite mit Slug `neo-dashboard`, Shortcode `[neo-dashboard]`, Permalinks speichern.
- **Assets fehlen**: CSS-Dateien, Browser-Cache und Konsole prüfen.
- **Umfrage speichert nicht**: Browser-Konsole, DB-Tabellen und `WP_DEBUG` kontrollieren.
- **Domain Changer streikt**: `sudo -l -U www-data`, Skript testen, `error_log` prüfen.
- **SSL_ERROR_RX_RECORD_TOO_LONG**: In Firefox `security.tls.insecure_fallback_hosts` anpassen oder HSTS löschen; alternativ Incognito nutzen.