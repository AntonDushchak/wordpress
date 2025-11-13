# Neo Domain Changer

**Version:** 1.0.0  
**Requires PHP:** 7.4+  
**License:** GPL-2.0-or-later

---

Ein einfaches WordPress-Plugin zum Ändern der Domain über die Admin-Oberfläche.

## 📦 Installation

1. Plugin aktivieren in **WP-Admin > Plugins**
2. Menüpunkt **"Domain ändern"** erscheint im Admin-Menü

---

## 🚀 Verwendung

1. Gehe zu **WP-Admin > Domain ändern**
2. Gib die neue Domain ein (z.B. `example.com`)
3. Klicke auf **"Domain ändern"**
4. Das Plugin führt aus: `/usr/bin/sudo /usr/local/bin/set_domain.sh <domain>`

---

## ⚙️ Voraussetzungen

### Server-Setup erforderlich:

1. **Skript erstellen**: `/usr/local/bin/set_domain.sh`
2. **Sudo-Rechte** für den Webserver-Benutzer:
   ```bash
   # /etc/sudoers.d/domain-changer
   www-data ALL=(ALL) NOPASSWD: /usr/local/bin/set_domain.sh
   ```

### Beispiel-Skript (`set_domain.sh`):
```bash
#!/bin/bash
NEW_DOMAIN="$1"

# WordPress-Datenbank aktualisieren
mysql -u root -p"password" wordpress <<EOF
UPDATE wp_options SET option_value='http://${NEW_DOMAIN}' WHERE option_name='siteurl';
UPDATE wp_options SET option_value='http://${NEW_DOMAIN}' WHERE option_name='home';
EOF

# Apache/Nginx Config anpassen (optional)
# sed -i "s/ServerName .*/ServerName ${NEW_DOMAIN}/" /etc/apache2/sites-available/000-default.conf
# systemctl reload apache2
```

---

## 🔒 Sicherheit

### Validierung
- ✅ Domain-Format wird validiert: `example.com`, `subdomain.example.com`
- ✅ Eingabe wird mit `escapeshellarg()` gesichert
- ✅ Nur Administratoren haben Zugriff
- ✅ Nonce-Prüfung bei Formular-Absendung

### Erlaubte Formate
- ✅ `example.com`
- ✅ `subdomain.example.com`
- ✅ `sub.domain.example.co.uk`
- ❌ `example` (kein TLD)
- ❌ `http://example.com` (wird automatisch bereinigt)
- ❌ `-example.com` (ungültiges Format)

---

## 📊 Logging

Das Plugin loggt alle Aktionen nach `error_log`:
- Ausgeführter Befehl
- Skript-Output
- Return-Code

**Log ansehen:**
```bash
# Docker
docker logs wordpress-app

# Direkter Zugriff
tail -f /var/log/apache2/error.log
```

---

## 🛠️ Troubleshooting

### "Permission denied"
```bash
# Sudo-Rechte prüfen
sudo -l -U www-data

# Skript ausführbar machen
chmod +x /usr/local/bin/set_domain.sh
```

### "Command not found"
```bash
# Prüfen ob Skript existiert
ls -l /usr/local/bin/set_domain.sh

# Pfad anpassen falls nötig (im Plugin)
```

### Domain-Änderung funktioniert nicht
- Prüfe error_log für Details
- Teste Skript manuell: `sudo /usr/local/bin/set_domain.sh test.com`
- Prüfe Datenbank-Verbindung im Skript

---

## 📝 Changelog

### 1.0.0
- Initial Release
- Domain-Validierung
- Sudo-Skript Ausführung
- Error-Logging
- WP-Admin Integration

