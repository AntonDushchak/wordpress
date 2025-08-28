<?php
/**
 * Employee Dashboard - Rollenverwaltung
 * 
 * Diese Datei ermöglicht die Verwaltung von Benutzerrollen und deren Berechtigungen.
 * Administratoren können Berechtigungen für verschiedene Benutzerrollen anpassen.
 * 
 * Funktionen:
 * - Anzeige aller Benutzerrollen
 * - Zuweisung von Berechtigungen zu Rollen
 * - Speichern der Berechtigungen über AJAX
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Role_Manager {
    public function __construct() {
        // Registriert das Menü für die Rollenverwaltung
        add_action('admin_menu', [$this, 'register_role_menu']);
        
        // AJAX-Handler zum Speichern der Berechtigungen
        add_action('wp_ajax_save_role_permissions', [$this, 'save_role_permissions']);
    }

    /**
     * Erstellt ein Untermenü für die Rollenverwaltung im Employee Dashboard.
     */
    public function register_role_menu() {
        add_submenu_page(
            'employee_dashboard',
            'Rollenverwaltung',
            'Rollenverwaltung',
            'manage_options',
            'employee_dashboard_roles',
            [$this, 'render_role_page']
        );
    }

    /**
     * Zeigt die Benutzeroberfläche zur Verwaltung der Rollenberechtigungen an.
     */
    public function render_role_page() {
        global $wp_roles;
        $roles = $wp_roles->roles;
        ?>
        <div class="wrap">
            <h1>🛠 Rollenverwaltung</h1>
            <p>Wähle aus, welche Widgets für jede Rolle verfügbar sein sollen.</p>

            <h2>🔹 Rollenübersicht</h2>
            <p>Hier siehst du alle vorhandenen Rollen und kannst Berechtigungen anpassen.</p>

            <table class="widefat">
                <thead>
                    <tr>
                        <th>Rolle</th>
                        <th>Widgets erlaubt?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role_id => $role_info) : ?>
                        <tr>
                            <td><strong><?= ucfirst($role_info['name']) ?></strong></td>
                            <td><input type="checkbox" class="role-permission" data-role="<?= $role_id ?>" checked></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button id="save-role-permissions" class="button button-primary mt-3">💾 Speichern</button>
            <p id="save-status" style="color: green; display: none;">✅ Änderungen gespeichert!</p>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                $("#save-role-permissions").click(function () {
                    $("#save-status").show().delay(3000).fadeOut();
                });
            });
        </script>
        <?php
    }

    /**
     * Speichert die Rollenberechtigungen.
     */
    public function save_role_permissions() {
        if (!current_user_can('manage_options')) wp_send_json_error('Keine Berechtigung!');

        // Beispielhafte Verarbeitung von Berechtigungen (kann erweitert werden)
        $permissions = isset($_POST['permissions']) ? array_map('sanitize_text_field', $_POST['permissions']) : [];
        update_option('employee_dashboard_role_permissions', $permissions);
        
        wp_send_json_success();
    }
}
// Erstellt eine Instanz der Klasse, um die Funktionalität zu aktivieren
new Employee_Dashboard_Role_Manager();
?>
