<?php
/**
 * Employee Dashboard - Benachrichtigungszentrale
 * 
 * Diese Datei verwaltet alle Benachrichtigungen im Dashboard.
 * Administratoren und Benutzer können systemweite Meldungen erhalten.
 * 
 * Funktionen:
 * - Speichern von Benachrichtigungen in der Datenbank
 * - Abrufen ungelesener und archivierter Benachrichtigungen
 * - Markieren von Benachrichtigungen als gelesen oder archiviert
 * - AJAX-Handler für Benachrichtigungsaktionen
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Notification_Center {
    public function __construct() {
        // AJAX-Handler für das Abrufen von Benachrichtigungen
        add_action('wp_ajax_fetch_notifications', [$this, 'fetch_notifications']);
        
        // AJAX-Handler für das Markieren als gelesen
        add_action('wp_ajax_mark_notification_read', [$this, 'mark_notification_read']);

        // AJAX-Handler für das Abrufen archivierter Benachrichtigungen
        add_action('wp_ajax_fetch_archived_notifications', [$this, 'fetch_archived_notifications']);
    }

    /**
     * Holt ungelesene Benachrichtigungen aus der Datenbank
     */
    public function fetch_notifications() {
        if (!current_user_can('read')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!']);
        }
        
        $user_id = get_current_user_id();
        $notifications = get_user_meta($user_id, 'employee_dashboard_notifications', true) ?: [];
        
        wp_send_json_success($notifications);
    }

    /**
     * Markiert eine Benachrichtigung als gelesen oder archiviert sie
     */
    public function mark_notification_read() {
        if (!current_user_can('read')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!']);
        }
        
        $user_id = get_current_user_id();
        $notification_id = sanitize_text_field($_POST['notification_id'] ?? '');
        
        if (!$notification_id) {
            wp_send_json_error(['error' => 'Ungültige Benachrichtigungs-ID.']);
        }
        
        $notifications = get_user_meta($user_id, 'employee_dashboard_notifications', true) ?: [];
        $archived_notifications = get_option('employee_dashboard_notifications_archive', []);
        
        foreach ($notifications as $key => $notification) {
            if ($notification['id'] == $notification_id) {
                // Benachrichtigung archivieren statt löschen
                $archived_notifications[] = $notification;
                update_option('employee_dashboard_notifications_archive', $archived_notifications);
                unset($notifications[$key]);
                update_user_meta($user_id, 'employee_dashboard_notifications', $notifications);
                wp_send_json_success(['message' => 'Benachrichtigung archiviert.']);
            }
        }
        
        wp_send_json_error(['error' => 'Benachrichtigung nicht gefunden.']);
    }

    /**
     * Holt archivierte Benachrichtigungen
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
new Employee_Dashboard_Notification_Center();
?>
