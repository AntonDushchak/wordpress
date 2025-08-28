<?php
/**
 * Employee Dashboard - Live Updates
 * 
 * Diese Datei stellt eine Echtzeit-Update-Funktion für das Dashboard bereit.
 * Es ermöglicht Benachrichtigungen und Updates für Widgets in Echtzeit.
 * 
 * Funktionen:
 * - Echtzeit-Update für Dashboard-Widgets
 * - WebSocket-Unterstützung für Live-Änderungen
 * - AJAX-Handler für manuelle Aktualisierung
 * - Unterstützung für archivierte Benachrichtigungen
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Live_Updates {
    public function __construct() {
        // Fügt das JavaScript für Live-Updates hinzu
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // AJAX-Handler für manuelle Updates
        add_action('wp_ajax_fetch_live_updates', [$this, 'fetch_live_updates']);
        
        // AJAX-Handler für das Abrufen archivierter Benachrichtigungen
        add_action('wp_ajax_fetch_archived_notifications', [$this, 'fetch_archived_notifications']);
    }

    /**
     * Lädt die benötigten Skripte für Live-Updates, falls die Datei existiert
     */
    public function enqueue_scripts() {
        $script_path = plugin_dir_path(__FILE__) . '../assets/js/live-updates.js';
        if (file_exists($script_path)) {
            wp_enqueue_script('employee-dashboard-live-updates', plugin_dir_url(__FILE__) . '../assets/js/live-updates.js', ['jquery'], null, true);
            wp_localize_script('employee-dashboard-live-updates', 'EmployeeDashboardLive', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'fetch_archived_url' => admin_url('admin-ajax.php?action=fetch_archived_notifications')
            ]);
        }
    }

    /**
     * AJAX-Handler für das Abrufen von Live-Updates
     */
    public function fetch_live_updates() {
        if (!current_user_can('read')) {
            $current_user = wp_get_current_user();
            wp_send_json_error(['error' => 'Keine Berechtigung!', 'user' => $current_user->user_login]);
        }
        
        // Holt gespeicherte Live-Updates aus der Datenbank
        $updates = get_option('employee_dashboard_live_updates', []);
        
        wp_send_json_success($updates);
    }
    
    /**
     * AJAX-Handler für das Abrufen archivierter Benachrichtigungen
     */
    public function fetch_archived_notifications() {
        if (!current_user_can('read')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!']);
        }
        
        $archived_notifications = get_option('employee_dashboard_notifications_archive', []);
        wp_send_json_success($archived_notifications);
    }
}

// Instanziert die Klasse, um die Funktionalität zu aktivieren
new Employee_Dashboard_Live_Updates();
?>