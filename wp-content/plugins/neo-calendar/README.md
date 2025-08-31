# Neo Calendar - Formular-Umschaltung

## Beschreibung

Das Neo Calendar Plugin unterstützt jetzt das Umschalten zwischen Arbeitszeit- und Urlaubsformularen. Beim Klicken auf den "Urlaub"-Button wechselt das Formular automatisch und ermöglicht es Benutzern, einfach zwischen verschiedenen Ereignistypen zu wechseln.

## Funktionalität

### Hauptformular
- **Arbeitszeitformular** (standardmäßig sichtbar)
  - Felder für Datum, Start- und Endzeit
  - "Hinzufügen"-Button für Arbeitszeit
  - "Urlaub"-Button zum Umschalten zum Urlaubsformular

- **Urlaubsformular** (standardmäßig ausgeblendet)
  - Felder für Urlaubsstart- und -enddatum
  - "Hinzufügen"-Button für Urlaub
  - "Zurück"-Button zum Zurückkehren zum Arbeitszeitformular

### Widget
- **Arbeitszeitformular** (standardmäßig sichtbar)
  - Kompakte Felder für Datum und Zeit
  - "Hinzufügen"-Button für Arbeitszeit
  - "Urlaub"-Button zum Umschalten zum Urlaubsformular

- **Urlaubsformular** (standardmäßig ausgeblendet)
  - Felder für Urlaubsstart- und -enddatum
  - "Urlaub hinzufügen"-Button für Urlaub
  - "Zurück"-Button zum Zurückkehren zum Arbeitszeitformular

## JavaScript API

### Hauptfunktionen
```javascript
// Urlaubsformular anzeigen
window.NeoCalendar.showVacationForm()

// Arbeitszeitformular anzeigen
window.NeoCalendar.showWorkForm()

// Formulare im Widget umschalten
window.NeoCalendar.toggleWidgetForms()

// Arbeitszeit hinzufügen
window.NeoCalendar.addWorkTime(dateElement, fromElement, toElement)

// Urlaub hinzufügen
window.NeoCalendar.addVacation(dateFromElement, dateToElement)
```

## CSS-Klassen

### Hauptstile
- `.calendar-form` - Stile für Formulare
- `.btn-toggle-form` - Stile für Umschalt-Buttons
- `.widget-card` - Stile für Widget

### Animationen
- Sanfte Übergänge zwischen Formularen
- Hover-Effekte für Buttons
- Ein-/Ausblend-Animationen

## Dateistruktur

```
neo-calendar/
├── neo-calendar.php              # Haupt-PHP-Datei
├── assets/
│   ├── css/
│   │   └── neo-calendar.css     # Stile
│   └── js/
│       ├── neo-calendar.js       # Haupt-JavaScript
│       ├── neo-calendar-common.js # Gemeinsame Funktionen
│       └── widget-neo-calendar.js # Widget-JavaScript
└── test-forms.html               # Testdatei
```

## Verwendung

### In WordPress
1. Aktivieren Sie das Neo Calendar Plugin
2. Wechseln Sie zu Neo Dashboard
3. Verwenden Sie die "Urlaub"-Buttons zum Umschalten zwischen Formularen

### Testing
1. Öffnen Sie `test-forms.html` im Browser
2. Testen Sie das Umschalten zwischen Formularen
3. Stellen Sie sicher, dass alle Buttons korrekt funktionieren

## Besonderheiten

- **Automatisches Umschalten** - Formulare wechseln sofort
- **Zustandserhaltung** - Aktuelles Formular bleibt aktiv
- **Responsive Design** - Funktioniert auf allen Geräten
- **Bootstrap-Kompatibilität** - Verwendet Standard-Bootstrap-Klassen
- **Bootstrap Icons** - Modernes Aussehen

## Support

Bei Problemen:
1. Überprüfen Sie die Browser-Konsole auf JavaScript-Fehler
2. Stellen Sie sicher, dass alle Dateien korrekt geladen werden
3. Überprüfen Sie, dass jQuery vor dem Plugin geladen wird
