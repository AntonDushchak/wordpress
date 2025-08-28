/*
 * Neo Dashboard – Core JS Helpers
 * --------------------------------
 * Abhängigkeit: Bootstrap 5.3 (Bundle)
 *
 * Features
 *   • Auto‑Close der Offcanvas‑Sidebar auf mobilen Geräten nach Klick auf echten Nav‑Link
 *   • Automatisches Aktivieren von Tooltips & Popovers (data‑bs‑toggle‑Attribut)
 *   • Globales Custom Event "neoDashboardReady" nach DOM‑Load & Bootstrap‑Init
 */

(() => {
  "use strict";
  document.addEventListener("DOMContentLoaded", () => {
    // Tooltips & Popovers
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => new bootstrap.Popover(el));

    // Offcanvas‑Auto‑Hide: nur für Links ohne Collapse‑Toggle oder Gruppen‑Toggle-Href
    const off = document.getElementById('sidebarOffcanvas');
    if (off) {
      off.querySelectorAll('a.nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
          const toggle = link.getAttribute('data-bs-toggle');
          const href = link.getAttribute('href');
          // Ignoriere Collapse-Toggles und Group hrefs
          if ((toggle === 'collapse') || (href && href.startsWith('#group'))) {
            return;
          }
          const bs = bootstrap.Offcanvas.getInstance(off);
          if (bs) bs.hide();
        });
      });
    }

    // Globales Event
    document.dispatchEvent(new CustomEvent("neoDashboardReady"));
  });
})();