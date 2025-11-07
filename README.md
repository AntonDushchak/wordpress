# Neo Dashboard WordPress Projekt

Modernes Dashboard für WordPress unter Verwendung des Neo Dashboard Core Plugins.

## 🎉 Letzte Updates

### Version 3.0.3 (Oktober 2025)
- ✅ **Neo Umfrage**: Statistik-Seite mit Feld-Analyse implementiert
- ✅ **Bug Fixes**: Survey-Daten Speicherung und DataTable-Anzeige korrigiert
- ✅ **UI Verbesserungen**: Icon-Buttons statt Text-Labels
- ✅ **Responsive**: Layout-Probleme bei 768-1024px behoben
- ✅ **Dark Theme**: Vollständige Unterstützung für alle UI-Elemente
- ✅ **Notifications**: Fixed-Position Benachrichtigungen
- ✅ **WP-Admin**: Neo Dashboard Link im Admin-Menü
- ✅ **Domain Changer**: Neues Plugin für Domain-Verwaltung

## 🚀 Schnellstart

### Voraussetzungen
- **Docker** & **Docker Compose**
- **Git**
- Freie Ports: 8080 (WordPress), 3306 (MySQL)

### Installation mit Docker

1. **Repository klonen:**
```bash
git clone https://github.com/your-username/neo-dashboard-wordpress.git
cd neo-dashboard-wordpress
```

2. **Docker Container starten:**
```bash
docker-compose up -d
```

3. **Installation abwarten:**
   - WordPress wird automatisch installiert
   - Datenbank wird konfiguriert
   - Warten Sie ca. 1-2 Minuten

4. **WordPress Setup:**
   - Öffnen Sie: `http://localhost:8080`
   - Folgen Sie den WordPress-Installationsanweisungen
   - Oder verwenden Sie die bestehende Konfiguration in `wp-config.php`

5. **Plugins aktivieren:**
   - Admin-Panel: `http://localhost:8080/wp-admin`
   - Gehen Sie zu **Plugins**
   - Aktivieren Sie:
     - **Neo Dashboard Core** (erforderlich)
     - **Neo Umfrage**
     - **Neo Calendar**
     - **Neo Domain Changer** (optional)

6. **Dashboard öffnen:**
   - Klicken Sie auf **"Neo Dashboard"** im WP-Admin Menü
   - Oder navigieren Sie zu: `http://localhost:8080/neo-dashboard`

### Docker Befehle

```bash
# Container starten
docker-compose up -d

# Container stoppen
docker-compose down

# Logs ansehen
docker-compose logs -f

# In Container einsteigen
docker exec -it wordpress-app bash

# Datenbank-Backup
docker exec wordpress-db mysqldump -uwordpress -pwordpress wordpress > backup.sql

# Container neu bauen
docker-compose up -d --build
```

## 📁 Projektstruktur

```
wordpress/
├── wp-content/
│   ├── plugins/
│   │   ├── neo-dashboard/              # Core Dashboard Framework
│   │   │   ├── src/                    # PSR-4 Klassen
│   │   │   │   ├── Manager/            # Asset, Section, Widget Manager
│   │   │   │   ├── Bootstrap.php
│   │   │   │   ├── Dashboard.php
│   │   │   │   └── Router.php
│   │   │   ├── templates/              # Dashboard Templates
│   │   │   ├── assets/                 # CSS & JS
│   │   │   └── README.md
│   │   ├── neo-umfrage/                # Umfrage-Plugin
│   │   │   ├── assets/
│   │   │   │   ├── css/
│   │   │   │   └── js/
│   │   │   └── neo-umfrage.php
│   │   ├── neo-calendar/               # Kalender-Plugin
│   │   │   ├── assets/
│   │   │   └── neo-calendar.php
│   │   └── neo-domain-changer/         # Domain Changer
│   │       ├── neo-domain-changer.php
│   │       └── README.md
│   └── themes/
│       ├── global-responsive.css       # Globale responsive Styles
│       └── responsive-functions.php    # Responsive Funktionen
├── docker-compose.yml
└── README.md
```

## 🔧 Konfiguration

### Ports ändern (falls erforderlich)
In `docker-compose.yml` ändern Sie:
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
Verwenden Sie das **Neo Domain Changer** Plugin in WP-Admin oder aktualisieren Sie `wp-config.php`:
```php
define('WP_HOME','http://your-domain.com');
define('WP_SITEURL','http://your-domain.com');
```

### Docker Environment Variables
In `docker-compose.yml`:
```yaml
environment:
  WORDPRESS_DB_HOST: db
  WORDPRESS_DB_USER: wordpress
  WORDPRESS_DB_PASSWORD: wordpress
  WORDPRESS_DB_NAME: wordpress
```

## 🌟 Features

### Core Dashboard
- **Modernes UI** mit Bootstrap 5.3
- **Responsives Design** für alle Geräte (Desktop, Tablet, Mobile)
- **Modulare Architektur** zur Erweiterung
- **REST API** für Integration
- **Benachrichtigungssystem** mit Fixed-Position
- **Widgets und Sektionen**
- **Sidebar-Gruppierung**
- **Dark/Light Theme** mit Theme-Switcher
- **WP-Admin Integration** - Direkter Link zu Neo Dashboard

### Neo Umfrage
- Template-basierte Umfragen erstellen
- Verschiedene Feldtypen: Text, Nummer, Telefon, Email, Radio, Checkbox, Select, Textarea
- DataTables für Umfragen-Übersicht
- Detaillierte Statistik-Seite:
  - Text-Felder: Häufigste Antworten
  - Zahlen-Felder: Min/Avg/Max
  - Auswahl-Felder: Prozentuale Verteilung mit Progress Bars
- Filterung nach Template und Benutzer
- Icon-basierte Aktionsbuttons
- Vollständig responsive

### Neo Calendar
- FullCalendar-Integration
- Event-Verwaltung mit Modal-Dialogen
- Responsive Design für alle Bildschirmgrößen
- Mobile-optimierte Toolbar und Controls

### Neo Domain Changer
- Einfache Domain-Verwaltung über WP-Admin
- Sichere Domain-Validierung
- Automatische Skript-Ausführung via sudo
- Error-Logging und Debugging
- Benutzerfreundliche Oberfläche

## 🛠️ Entwicklung

### Neue Sektionen hinzufügen
```php
add_action('neo_dashboard_init', function() {
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
add_action('neo_dashboard_init', function() {
    do_action('neo_dashboard_register_widget', [
        'id'       => 'my-widget',
        'label'    => 'Mein Widget',
        'icon'     => 'bi-graph-up',
        'priority' => 10,
        'callback' => function() {
            echo '<p>Widget-Inhalt</p>';
        },
    ]);
});
```

## 🗄️ Datenbankstruktur

### Neo Umfrage
```sql
wp_neo_umfrage_templates        # Umfrage-Templates
wp_neo_umfrage_surveys          # Ausgefüllte Umfragen
wp_neo_umfrage_survey_values    # Feld-Werte der Umfragen
```

### Neo Calendar
```sql
wp_neo_calendar_events          # Kalender-Events
```

## 📚 Dokumentation

- [Neo Dashboard Core](wp-content/plugins/neo-dashboard/README.md)
- [Neo Umfrage](wp-content/plugins/neo-umfrage/)
- [Neo Calendar](wp-content/plugins/neo-calendar/)
- [Neo Domain Changer](wp-content/plugins/neo-domain-changer/README.md)

## 🐛 Fehlerbehebung

### Plugin aktiviert sich nicht
- Überprüfen Sie die PHP-Version (sollte 8.1+ sein)
- Überprüfen Sie Ordnerberechtigungen
- Leeren Sie den WordPress-Cache

### Dashboard-Seite nicht gefunden
- Erstellen Sie eine Seite mit Slug "neo-dashboard"
- Fügen Sie den Shortcode `[neo-dashboard]` hinzu
- Aktualisieren Sie die Permalinks

### Stile laden nicht
- Überprüfen Sie, ob CSS-Dateien existieren
- Leeren Sie den Browser-Cache (Ctrl+F5)
- Überprüfen Sie die Browser-Konsole auf Fehler

### Umfrage-Daten werden nicht gespeichert
- Prüfen Sie die Browser-Konsole auf Fehler
- Kontrollieren Sie die Tabellen in der Datenbank
- Aktivieren Sie Debug-Logging in `wp-config.php`

### Domain Changer funktioniert nicht
- Prüfen Sie sudo-Rechte: `sudo -l -U www-data`
- Testen Sie das Skript manuell
- Prüfen Sie `error_log` für Details

### SSL-Fehler bei localhost (SSL_ERROR_RX_RECORD_TOO_LONG)
Wenn Firefox versucht, HTTPS zu verwenden und den Fehler `SSL_ERROR_RX_RECORD_TOO_LONG` anzeigt:
1. **Firefox**: Öffnen Sie `about:config` und suchen Sie nach `security.tls.insecure_fallback_hosts`
2. Fügen Sie `localhost` zur Liste hinzu (oder bearbeiten Sie direkt: `localhost,localhost:8080`)
3. Alternative: Löschen Sie den HSTS-Cache: `about:config` → `security.tls.insecure_fallback_hosts` → Zurücksetzen
4. Chrome: Geben Sie `chrome://net-internals/#hsts` ein, scrollen Sie zu "Delete domain security policies" und löschen Sie `localhost`
5. **Einfachste Lösung**: Nutzen Sie Incognito/Private-Fenster mit `http://localhost:8080`


