<?php
/**
 * Plugin Name: Employee Dashboard
 * Plugin URI:  https://example.com
 * Description: Modulares Dashboard mit Rollenverwaltung, Echtzeit-Updates und KI-gestützten Empfehlungen.
 * Version:     1.2
 * Author:      Dein Name
 * License:     GPL2
 */

// Direktzugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Autoload für Klassen einbinden (falls vorhanden)
if (file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php')) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

/**
 * Registriert das Admin-Menü für das Dashboard
 */
function employee_dashboard_menu() {
    add_menu_page(
        'Employee Dashboard',   // Seiten-Titel
        'Employee Dashboard',   // Menü-Name
        'manage_options',       // Benutzerrolle
        'employee_dashboard',   // Menü-Slug
        'employee_dashboard_admin_page', // Callback-Funktion
        'dashicons-chart-area', // Icon
        2                       // Position
    );
}
add_action('admin_menu', 'employee_dashboard_menu');

/**
 * Callback-Funktion für die Admin-Seite
 * Hier wird das Dashboard-Template eingebunden
 */
function employee_dashboard_admin_page() {
    include plugin_dir_path(__FILE__) . 'templates/admin-dashboard.php';
}

/**
 * Plugin initialisieren
 */
function employee_dashboard_init() {
    // Alle Klassen zentral initialisieren
    $classes = [
        'Employee_Dashboard_Role_Editor',
        'Employee_Dashboard_Role_Manager',
        'Employee_Dashboard_Live_Updates',
        'Employee_Dashboard_Export',
        'Employee_Dashboard_Slack_Integration',
        'Employee_Dashboard_Notification_Center'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            new $class();
        }
    }
}
add_action('plugins_loaded', 'employee_dashboard_init');

/**
 * Bereinigungsfunktion beim Deaktivieren des Plugins
 */
function employee_dashboard_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'employee_dashboard_deactivate');

/**
 * Entfernt Plugin-Daten bei vollständiger Deinstallation
 * Archiviert Benachrichtigungen anstatt sie zu löschen
 */
function employee_dashboard_uninstall() {
    delete_option('employee_dashboard_settings');
    delete_option('employee_dashboard_role_permissions');
    delete_option('employee_dashboard_live_updates');
    delete_option('employee_dashboard_slack_webhook');
    
    // Benachrichtigungen archivieren, anstatt sie zu löschen
    $notifications = get_option('employee_dashboard_notifications', []);
    update_option('employee_dashboard_notifications_archive', $notifications);
    delete_option('employee_dashboard_notifications');
}
register_uninstall_hook(__FILE__, 'employee_dashboard_uninstall');
