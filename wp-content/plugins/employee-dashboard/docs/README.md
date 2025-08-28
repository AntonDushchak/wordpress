# 📊 Employee Dashboard Plugin

**Modulares Dashboard für WordPress mit Rollenverwaltung, Echtzeit-Updates und KI-gestützten Empfehlungen.**

## 🚀 Funktionen
- ✅ Rollenbasierte Widget-Verwaltung
- ✅ Live-Updates für Widgets & Benachrichtigungen
- ✅ Drag & Drop Dashboard-Widgets
- ✅ PWA-Unterstützung & Dark Mode
- ✅ Automatische Berichte & CSV-Exporte
- ✅ Slack & Microsoft Teams Integration
- ✅ REST API-Unterstützung für externe Integrationen

## 🛠 Installation
1. Lade die ZIP-Datei in **WordPress → Plugins → Installieren** hoch.
2. Aktiviere das Plugin.
3. Gehe zu **Dashboard Settings** und konfiguriere dein Dashboard.

## 🔧 Entwicklung
- Repository klonen:  
  ```bash
  git clone https://github.com/DEIN_GITHUB_USERNAME/employee-dashboard.git
  cd employee-dashboard
  ```
- Plugin in einer lokalen Umgebung testen:
  ```bash
  docker-compose up -d
  ```

## 📡 REST API Endpunkte
| Methode | Endpunkt | Beschreibung |
|---------|---------|-------------|
| `GET`   | `/wp-json/employee-dashboard/v1/widgets` | Liste aller Widgets abrufen |
| `POST`  | `/wp-json/employee-dashboard/v1/add-widget` | Ein neues Widget hinzufügen |

## 📜 Lizenz
Dieses Plugin ist unter der **GPLv2-Lizenz** veröffentlicht.
