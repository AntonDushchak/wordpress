<?php
/**
 * Willkommenstemplate für Neo Dashboard Examples
 * Wird angezeigt, wenn die Section 'Willkommen' geladen wird.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0"><?php esc_html_e( 'Willkommen zum Neo Dashboard Examples Plugin', 'neo-dashboard-examples' ); ?></h3>
    </div>
    <div class="card-body">
        <p class="lead"><?php esc_html_e( 'Dieses Dashboard demonstriert, wie du eigene Sections, Widgets, Sidebar-Gruppen und Notifications mit Neo Dashboard Core erstellen kannst.', 'neo-dashboard-examples' ); ?></p>
        <hr />
        <ul class="list-unstyled mb-0">
            <li><i class="bi bi-check-circle-fill text-success me-2"></i><?php esc_html_e( 'Sidebar-Gruppen & Unterpunkte', 'neo-dashboard-examples' ); ?></li>
            <li><i class="bi bi-check-circle-fill text-success me-2"></i><?php esc_html_e( 'Dynamische Widgets mit Callback-Funktion', 'neo-dashboard-examples' ); ?></li>
            <li><i class="bi bi-check-circle-fill text-success me-2"></i><?php esc_html_e( 'Benutzerdefinierte Notifications', 'neo-dashboard-examples' ); ?></li>
            <li><i class="bi bi-check-circle-fill text-success me-2"></i><?php esc_html_e( 'Custom Assets via Hook-API', 'neo-dashboard-examples' ); ?></li>
        </ul>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-6">
        <div class="card h-100 mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php esc_html_e( 'Nächste Schritte', 'neo-dashboard-examples' ); ?></h5>
                <p><?php esc_html_e( 'Ergänze weitere Sections über den Hook neo_dashboard_init und experimentiere mit Templates und Callbacks.', 'neo-dashboard-examples' ); ?></p>
                <a href="https://github.com/your-repo/neo-dashboard-examples" class="btn btn-outline-primary" target="_blank"><?php esc_html_e( 'Zum Repository', 'neo-dashboard-examples' ); ?></a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card h-100 mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php esc_html_e( 'Dokumentation', 'neo-dashboard-examples' ); ?></h5>
                <p><?php esc_html_e( 'Sieh dir die README.md im Core-Plugin an, um alle verfügbaren Hooks und Beispiele zu sehen.', 'neo-dashboard-examples' ); ?></p>
                <a href="https://github.com/your-repo/neo-dashboard-core/blob/main/README.md" class="btn btn-outline-secondary" target="_blank"><?php esc_html_e( 'Neo Dashboard Core README', 'neo-dashboard-examples' ); ?></a>
            </div>
        </div>
    </div>
</div>
