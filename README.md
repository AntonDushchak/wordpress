# Neo Dashboard WordPress Projekt

Modernes Dashboard für WordPress unter Verwendung des Neo Dashboard Core Plugins.

## 🚀 Schnellstart

### Voraussetzungen
- **PHP:** 8.1 oder höher
- **WordPress:** 6.0 oder höher
- **XAMPP/WAMP/MAMP** oder ähnlicher lokaler Server
- **Git**

### Installation in 5 Minuten

1. **Repository klonen:**
```bash
git clone https://github.com/your-username/neo-dashboard-wordpress.git
cd neo-dashboard-wordpress
```

2. **XAMPP starten:**
   - Starten Sie Apache und MySQL
   - Stellen Sie sicher, dass die Ports 80 und 3306 frei sind

3. **Datenbank importieren:**
   - Öffnen Sie phpMyAdmin: `http://localhost/phpmyadmin`
   - Erstellen Sie eine neue Datenbank: `wordpress_neo`
   - Importieren Sie die Datei `database/wordpress_neo.sql`

4. **WordPress konfigurieren:**
   - Kopieren Sie den Ordner `wordpress` in `htdocs`
   - Öffnen Sie `http://localhost/wordpress`
   - Folgen Sie den WordPress-Installationsanweisungen
   - Verwenden Sie die Datenbankdaten: `wordpress_neo`

5. **Plugins aktivieren:**
   - Melden Sie sich im Admin-Panel an: `http://localhost/wordpress/wp-admin`
   - Gehen Sie zu **Plugins**
   - Aktivieren Sie **Neo Dashboard Core**
   - Aktivieren Sie **Neo Dashboard Examples**

6. **Dashboard öffnen:**
   - Navigieren Sie zu: `http://localhost/wordpress/neo-dashboard`

## 📁 Projektstruktur

```
neo-dashboard-wordpress/
├── wordpress/                          # WordPress Dateien
│   ├── wp-content/
│   │   ├── plugins/
│   │   │   ├── neo-dashboard/         # Haupt-Plugin
│   │   │   └── neo-dashboard-examples/ # Beispiele
│   │   └── themes/
│   ├── wp-config.php
│   └── index.php
├── database/                           # Datenbank
│   └── wordpress_neo.sql
├── docs/                              # Dokumentation
├── scripts/                           # Installationsskripte
└── README.md
```

## 🔧 Konfiguration

### Ports ändern (falls erforderlich)
Wenn Port 80 belegt ist, ändern Sie in `apache/conf/httpd.conf`:
```apache
Listen 8080
ServerName localhost:8080
```

### Datenbank-URL ändern
In `wp-config.php` ändern Sie:
```php
define('WP_HOME','http://localhost:8080/wordpress');
define('WP_SITEURL','http://localhost:8080/wordpress');
```

## 🌟 Features

- **Modernes UI** mit Bootstrap 5.3
- **Responsives Design** für alle Geräte
- **Modulare Architektur** zur Erweiterung
- **REST API** für Integration
- **Benachrichtigungssystem**
- **Widgets und Sektionen**
- **Sidebar-Gruppierung**

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

## 📚 Dokumentation

- [Neo Dashboard Core](docs/neo-dashboard-core.md)
- [API Referenz](docs/api-reference.md)
- [Verwendungsbeispiele](docs/examples.md)
- [Fehlerbehebung](docs/troubleshooting.md)

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
- Leeren Sie den Browser-Cache
- Überprüfen Sie die Browser-Konsole auf Fehler

## 🤝 Zum Projekt beitragen

1. Repository forken
2. Feature Branch erstellen: `git checkout -b feature/amazing-feature`
3. Änderungen committen: `git commit -m 'Add amazing feature'`
4. Push zum Branch: `git push origin feature/amazing-feature`
5. Pull Request öffnen

## 📄 Lizenz

Dieses Projekt wird unter der GPL-2.0-or-later Lizenz veröffentlicht.

## 🆘 Support

- **Issues:** [GitHub Issues](https://github.com/your-username/neo-dashboard-wordpress/issues)
- **Discussions:** [GitHub Discussions](https://github.com/your-username/neo-dashboard-wordpress/discussions)
- **Wiki:** [GitHub Wiki](https://github.com/your-username/neo-dashboard-wordpress/wiki)

## 🙏 Danksagungen

- WordPress Community
- Bootstrap Team
- Alle Projektmitarbeiter

---

**Mit ❤️ für die WordPress Community erstellt**
