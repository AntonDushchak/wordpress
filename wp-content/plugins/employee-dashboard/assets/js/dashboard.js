/**
 * Employee Dashboard - Dashboard Funktionen
 *
 * Dieses Skript verwaltet das Dashboard-Layout und Interaktionen.
 *
 * Funktionen:
 * - Drag & Drop für Widgets
 * - Speicherung der Widget-Reihenfolge in localStorage
 */

document.addEventListener("DOMContentLoaded", function () {
    let container = document.getElementById("dashboard-widgets");
    if (container) {
        new Sortable(container, {
            animation: 150,
            handle: ".drag-handle",
            onEnd: function () {
                let order = [];
                document.querySelectorAll(".widget").forEach(widget => {
                    order.push(widget.id);
                });
                localStorage.setItem("widgetOrder", JSON.stringify(order));
            }
        });

        // Gespeicherte Reihenfolge wiederherstellen
        let savedOrder = JSON.parse(localStorage.getItem("widgetOrder"));
        if (savedOrder) {
            savedOrder.forEach(id => {
                let widget = document.getElementById(id);
                if (widget) container.appendChild(widget);
            });
        }
    }

    console.log("Dashboard geladen und Widget-Reihenfolge gespeichert.");
});
