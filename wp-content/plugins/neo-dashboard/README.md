# Neo Dashboard Core

**Version:** 3.0.3  
**Requires PHP:** 8.1+  
**License:** GPL-2.0-or-later

---

Ein zentrales Dashboard-Framework, das WordPress-Plugins nahtlos in eine einheitliche Benutzeroberfläche integriert. Entwickler können eigene Sidebar-Gruppen, Sections, Widgets und Notifications per Hook-API registrieren.

## ✨ Neue Features (3.0.3)

- 🔗 **WP-Admin Integration**: Link zu Neo Dashboard im WordPress Admin-Menü
- 📊 **Neo Umfrage**: Vollständige Statistik-Seite mit Feld-Analyse
- 🎨 **Icon Buttons**: Moderne Icon-basierte Aktionsbuttons
- 📱 **Responsive Design**: Optimiert für alle Bildschirmgrößen
- 🌓 **Dark Theme Support**: Vollständige Unterstützung für dunkle Themes
- 🔧 **Domain Changer**: Neues Plugin für einfache Domain-Verwaltung

## 📦 Installation

1. **Upload**  
   Kopiere den Ordner `neo-dashboard-core` in dein Verzeichnis `wp-content/plugins/`.
2. **Aktivieren**  
   Aktiviere das Plugin im WordPress‑Admin unter **Plugins**.
3. **Seite anlegen**  
   Bei der ersten Aktivierung wird automatisch eine Seite mit dem Slug `neo-dashboard` erstellt und das Blank-Template zugewiesen.
4. **Permalinks**  
   (Nur bei URL-Problemen) Gehe zu **Einstellungen > Permalinks** und klicke auf „Änderungen speichern“, um Rewrite-Rules zu flushen.

---

## 🚀 Schnellstart

1. Im Browser aufrufen:  
   `https://deine-domain.de/neo-dashboard/`
2. Hooks nutzen:  
   Registriere in deinem Plugin unter `add_action('neo_dashboard_init', ...)` eigene Komponenten.

```php
add_action('neo_dashboard_init', function() {
    // Sidebar-Gruppe
    do_action('neo_dashboard_register_sidebar_item', [
        'slug'     => 'my-plugin',
        'label'    => 'My Plugin',
        'icon'     => 'bi-puzzle',
        'url'      => '/neo-dashboard/my-plugin',
        'position' => 20,
        'is_group' => true,
    ]);

    // Section unter Gruppe
    do_action('neo_dashboard_register_sidebar_item', [
        'slug'     => 'my-plugin-settings',
        'label'    => 'Einstellungen',
        'icon'     => 'bi-gear',
        'url'      => '/neo-dashboard/my-plugin-settings',
        'position' => 21,
        'parent'   => 'my-plugin',
    ]);

    // Section im Content-Bereich
    do_action('neo_dashboard_register_section', [
        'slug'          => 'my-plugin-settings',
        'label'         => 'Einstellungen',
        'icon'          => 'bi-gear',
        'template_path' => plugin_dir_path(__FILE__). 'templates/settings.php',
    ]);
});
```

---

## 🔌 Architektur & Extensibility

- **PSR‑4 Autoloader** lädt alle Klassen im Namespace `NeoDashboard\Core`.
- **Bootstrap**: Hook-Setup für Activation, Deactivation, Init.
- **DashboardCore**: Orchestriert die Manager.
- **Registry**: Singleton speichert Sidebar-Items, Sections, Widgets und Notifications.
- **Manager**-Klassen: SidebarManager, SectionManager, WidgetManager, NotificationManager, AssetManager.
- **Router**: URL-Routing, Shortcodes und Template-Override.
- **Templates**: Blank-Template (`dashboard-blank.php`) und Layout-Template (`dashboard-layout.php`) basierend auf Bootstrap 5.

---

## 🛠️ Hook-API Übersicht

| Hook                             | Parameter          | Beschreibung                                       |
|----------------------------------|--------------------|----------------------------------------------------|
| `neo_dashboard_init`             | —                  | Wird nach Core-Setup gefeuert.                     |
| `neo_dashboard_register_sidebar_item` | `array $args`   | Registriert ein Sidebar-Item (inkl. Gruppen).      |
| `neo_dashboard_register_section` | `array $args`      | Registriert eine Section im Hauptbereich.          |
| `neo_dashboard_register_widget`  | `array $args`      | Registriert ein Widget im Dashboard.               |
| `neo_dashboard_register_notification` | `array $args` | Registriert eine Notification oben im Layout.      |
| `neo_dashboard_enqueue_assets`   | —                  | Fügt eigene CSS/JS nach Core-Assets ein.           |

**Common `$args`** (Beispiel Sidebar-Item):
```php
[
  'slug'      => 'key',
  'label'     => 'Titel',
  'icon'      => 'bi-icon',
  'url'       => '/neo-dashboard/key',
  'position'  => 10,
  'roles'     => ['administrator'],
  // Sidebar only:
  'parent'    => 'group-slug',
  'is_group'  => true|false,
  // Section only:
  'template_path' => '/path/to/template.php',
  'callback'      => callable,
  // Widget only:
  'priority'  => 5,
  // Notification only:
  'message'   => 'Text',
  'dismissible' => true,
]
```

---

## 📚 Beispiele

### Sidebar-Gruppierung

```php
add_action('neo_dashboard_init', function() {
    // Gruppe definieren
    do_action('neo_dashboard_register_sidebar_item', [
        'slug'     => 'weather-plugin',
        'label'    => 'Wetter-Plugin',
        'icon'     => 'bi-cloud',
        'url'      => '/neo-dashboard/weather-plugin',
        'position' => 10,
        'is_group' => true,
    ]);

    // Unterpunkte
    foreach (['3days','7days'] as $type) {
        do_action('neo_dashboard_register_sidebar_item', [
            'slug'     => 'weather-'.$type,
            'label'    => ($type==='3days'?'3-Tage':'7-Tage')."-Wetter",
            'icon'     => 'bi-calendar',
            'url'      => '/neo-dashboard/weather-'.$type,
            'position' => ($type==='3days'?11:12),
            'parent'   => 'weather-plugin',
        ]);
    }
});
```

### Wetter-Widget & Notification
```php
add_action('neo_dashboard_init', function() {
    // Aktuelles Wetter-Widget
    do_action('neo_dashboard_register_widget', [
        'slug'     => 'current-weather',
        'label'    => 'Aktuelles Wetter',
        'icon'     => 'bi-thermometer-half',
        'priority' => 5,
        'callback' => function(){ /* Anzeige-Code */ },
    ]);

    // Unwetter-Warnung
    if ( $will_storm ) {
        do_action('neo_dashboard_register_notification', [
            'slug'        => 'storm-alert',
            'message'     => '<strong>⚠️ Gewitter erwartet!</strong>',
            'priority'    => 1,
            'dismissible' => true,
        ]);
    }
});
```

---

## 🎨 Templates & Styling

- **`dashboard-blank.php`**: Komplettes Standalone-HTML ohne Theme-Header/Footer.  
- **`dashboard-layout.php`**: Haupt-Layout mit Navbar, Offcanvas-Sidebar (mobil) und Desktop-Sidebar, Content-Bereich.  
- **Styles**: Passe `assets/dashboard.css` an oder füge eigene CSS/JS mit `neo_dashboard_enqueue_assets` hinzu.

---

## 🔌 Integrierte Plugins

### Neo Umfrage
Plugin für Erstellung und Verwaltung von Umfragen.

**Features:**
- Template-basierte Umfragen
- Verschiedene Feldtypen (Text, Nummer, Radio, Checkbox, Select)
- Detaillierte Statistik-Seite mit Feld-Analyse
- DataTables-Integration für Übersichten
- Vollständig responsive

### Neo Calendar
Kalender-Plugin mit FullCalendar-Integration.

**Features:**
- Event-Verwaltung
- Responsive Design für alle Geräte
- Mobile-optimierte Bedienung

### Neo Domain Changer
Einfache Domain-Verwaltung direkt aus WordPress.

**Features:**
- Sichere Domain-Validierung
- Automatische Skript-Ausführung
- Logging für Debugging
- WP-Admin Integration

---

## 🎨 UI Components

### Buttons
- Icon-basierte Aktionsbuttons mit Bootstrap Icons
- Farbvarianten: Primary, Secondary, Danger, Warning, Success
- Hover-Effekte und Tooltips

### Tables
- DataTables-Integration
- Responsive Design
- Filterung und Sortierung
- Dark Theme Support

### Forms
- Moderne Form-Gestaltung
- Validierung
- Responsive Layouts

### Notifications
- Fixed-Position Benachrichtigungen
- Automatisches Ausblenden nach 5 Sekunden
- Slide-In Animation
- Farben bleiben in Dark Theme erhalten

---

## 📱 Responsive Breakpoints

- **Desktop**: ≥ 1200px (4 Spalten Grid)
- **Tablet**: 768px - 1024px (25% Sidebar, 75% Content)
- **Mobile**: < 768px (100% Width, Offcanvas Sidebar)
- **Small Mobile**: < 480px (Kompakte UI-Elemente)

---



