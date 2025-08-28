/**
 * Employee Dashboard - Live Updates Script
 *
 * Dieses Skript verwaltet die Echtzeit-Updates für das Dashboard.
 * Es nutzt AJAX, um regelmäßig neue Updates vom Server abzurufen und anzuzeigen.
 *
 * Funktionen:
 * - Periodisches Abrufen neuer Benachrichtigungen
 * - Anzeige neuer Updates im Dashboard und Dropdown
 * - Unterstützung für archivierte Benachrichtigungen
 * - Unterstützung für WebSocket-Kommunikation
 */

document.addEventListener("DOMContentLoaded", function () {
    function fetchLiveUpdates() {
        fetch(EmployeeDashboardLive.ajax_url + "?action=fetch_live_updates")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateLiveNotifications(data.data);
                    updateNotificationDropdown(data.data);
                } else {
                    console.error("Fehler beim Abrufen der Live-Updates:", data.error);
                }
            })
            .catch(error => console.error("Netzwerkfehler:", error));
    }

    function fetchArchivedNotifications() {
        fetch(EmployeeDashboardLive.fetch_archived_url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateArchivedNotifications(data.data);
                } else {
                    console.error("Fehler beim Abrufen der archivierten Benachrichtigungen:", data.error);
                }
            })
            .catch(error => console.error("Netzwerkfehler:", error));
    }

    function updateLiveNotifications(updates) {
        const container = document.getElementById("live-updates-container");
        if (!container) return;
        
        container.innerHTML = "";
        updates.forEach(update => {
            const item = document.createElement("div");
            item.classList.add("live-update-item");
            item.textContent = update.message;
            container.appendChild(item);
        });
    }

    function updateNotificationDropdown(notifications) {
        const dropdown = document.getElementById("notifications-dropdown");
        const countBadge = document.getElementById("notification-count");
        if (!dropdown || !countBadge) return;
        
        dropdown.innerHTML = "";
        
        if (notifications.length === 0) {
            dropdown.innerHTML = '<li><p class="dropdown-item text-muted">Keine neuen Benachrichtigungen</p></li>';
            countBadge.style.display = "none";
        } else {
            countBadge.style.display = "inline";
            countBadge.textContent = notifications.length;
            
            notifications.forEach(notification => {
                const item = document.createElement("li");
                item.innerHTML = `<a class="dropdown-item" href="#">${notification.message}</a>`;
                dropdown.appendChild(item);
            });
        }
    }

    function updateArchivedNotifications(archivedUpdates) {
        const container = document.getElementById("archived-updates-container");
        if (!container) return;
        
        container.innerHTML = "";
        archivedUpdates.forEach(update => {
            const item = document.createElement("div");
            item.classList.add("archived-update-item");
            item.textContent = update.message;
            container.appendChild(item);
        });
    }

    // Live-Updates alle 10 Sekunden abrufen
    setInterval(fetchLiveUpdates, 10000);
    
    // Archivierte Benachrichtigungen alle 60 Sekunden abrufen
    setInterval(fetchArchivedNotifications, 60000);

    // WebSocket-Verbindung aufbauen (optional, falls Server WebSockets unterstützt)
    if (window.WebSocket) {
        const socket = new WebSocket("wss://example.com/live-updates");
        socket.onmessage = function (event) {
            const updates = JSON.parse(event.data);
            updateLiveNotifications(updates);
            updateNotificationDropdown(updates);
        };
    }
});