<?php
/**
 * Employee Dashboard - Slack Integration
 * 
 * Diese Datei ermöglicht die Integration von Slack für Benachrichtigungen und Updates.
 * Administratoren können Benachrichtigungen an einen bestimmten Slack-Kanal senden.
 * 
 * Funktionen:
 * - Senden von Nachrichten an Slack
 * - Konfiguration des Slack-Webhooks
 * - Fehlerbehandlung für API-Anfragen
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Slack_Integration {
    private $webhook_url = 'https://hooks.slack.com/services/default-webhook'; // Fallback-URL falls keine gesetzt ist

    public function __construct() {
        // Lese die gespeicherte Webhook-URL aus den Plugin-Optionen
        $this->webhook_url = get_option('employee_dashboard_slack_webhook', '');
        
        // AJAX-Handler für das Senden einer Nachricht an Slack
        add_action('wp_ajax_send_slack_notification', [$this, 'send_slack_notification']);
    }

    /**
     * Sendet eine Nachricht an den konfigurierten Slack-Kanal
     */
    public function send_slack_notification() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!', 'user' => wp_get_current_user()->user_login]);
        }
        
        $message = sanitize_text_field($_POST['message'] ?? '');
        if (empty($message)) {
            wp_send_json_error(['error' => 'Nachricht darf nicht leer sein oder enthält ungültige Zeichen.']);
        }

        if (empty($this->webhook_url)) {
            wp_send_json_error(['error' => 'Slack Webhook-URL nicht konfiguriert.']);
        }
        
        $payload = json_encode(['text' => $message]);
        $response = wp_remote_post($this->webhook_url, [
            'timeout' => 10,
            'body'    => $payload,
            'headers' => ['Content-Type' => 'application/json'],
            'method'  => 'POST'
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['error' => 'Slack API-Fehler: ' . $response->get_error_message()]);
        }
        
        wp_send_json_success(['message' => 'Benachrichtigung erfolgreich gesendet.']);
    }
}

// Instanziert die Klasse, um die Funktionalität zu aktivieren
new Employee_Dashboard_Slack_Integration();
?>
