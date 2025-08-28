<?php
/**
 * Employee Dashboard - Rollen-Editor
 * 
 * Diese Datei ermöglicht die Verwaltung von Benutzerrollen innerhalb des Employee Dashboard Plugins.
 * Administratoren können benutzerdefinierte Rollen hinzufügen und entfernen.
 * 
 * Funktionen:
 * - Anzeige der aktuellen Benutzerrollen
 * - Erstellung neuer Rollen
 * - Entfernung bestehender Rollen
 * - Speicherung der Rollen über AJAX
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Role_Editor {
    public function __construct() {
        // Registriert das Rollen-Editor-Menü im WordPress Admin-Panel
        add_action('admin_menu', [$this, 'register_role_editor_menu']);
        
        // AJAX-Handler für das Hinzufügen einer neuen Rolle
        add_action('wp_ajax_add_custom_role', [$this, 'add_custom_role']);
        
        // AJAX-Handler für das Löschen einer bestehenden Rolle
        add_action('wp_ajax_delete_custom_role', [$this, 'delete_custom_role']);
    }

    /**
     * Erstellt ein Untermenü für die Rollenverwaltung im Employee Dashboard.
     */
    public function register_role_editor_menu() {
        add_submenu_page(
            'employee_dashboard',
            'Rollen-Editor',
            'Rollen-Editor',
            'manage_options',
            'employee_dashboard_role_editor',
            [$this, 'render_role_editor_page']
        );
    }

    /**
     * Zeigt die Benutzeroberfläche für die Rollenverwaltung an.
     */
    public function render_role_editor_page() {
        global $wp_roles;
        $roles = $wp_roles->roles;

        ?>
        <div class="wrap">
            <h1>🎭 Rollenverwaltung</h1>
            <input type="text" id="new-role-name" placeholder="Name der neuen Rolle" required>
            <button id="add-role" class="button button-primary">➕ Rolle hinzufügen</button>

            <h2>🔸 Bestehende Rollen</h2>
            <ul id="role-list">
                <?php foreach ($roles as $role_id => $role_info): ?>
                    <li>
                        <strong><?= ucfirst($role_info['name']) ?></strong> 
                        <button class="delete-role" data-role="<?= $role_id ?>">🗑 Löschen</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Event-Listener für das Hinzufügen einer neuen Rolle
                $("#add-role").click(function () {
                    let roleName = $("#new-role-name").val().trim();
                    if (!roleName) return alert("Bitte einen Rollennamen eingeben.");

                    $.post("<?= admin_url('admin-ajax.php') ?>", {
                        action: "add_custom_role",
                        role_name: roleName
                    }, function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Fehler: " + response.data);
                        }
                    });
                });

                // Event-Listener für das Löschen einer Rolle
                $(".delete-role").click(function () {
                    let role = $(this).data("role");
                    if (!confirm("Möchtest du diese Rolle wirklich löschen?")) return;

                    $.post("<?= admin_url('admin-ajax.php') ?>", {
                        action: "delete_custom_role",
                        role: role
                    }, function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Fehler: " + response.data);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Fügt eine neue benutzerdefinierte Rolle hinzu.
     */
    public function add_custom_role() {
        if (!current_user_can('manage_options')) wp_send_json_error('Keine Berechtigung!');

        $role_name = sanitize_text_field($_POST['role_name']);
        $role_id = strtolower(str_replace(' ', '_', $role_name));

        if (get_role($role_id)) wp_send_json_error('Diese Rolle existiert bereits!');

        add_role($role_id, $role_name, ['read' => true]);
        wp_send_json_success();
    }

    /**
     * Löscht eine benutzerdefinierte Rolle.
     */
    public function delete_custom_role() {
        if (!current_user_can('manage_options')) wp_send_json_error('Keine Berechtigung!');

        $role = sanitize_text_field($_POST['role']);
        remove_role($role);

        wp_send_json_success();
    }
}
// Erstellt eine Instanz der Klasse, um die Funktionalität zu aktivieren
new Employee_Dashboard_Role_Editor();
?>