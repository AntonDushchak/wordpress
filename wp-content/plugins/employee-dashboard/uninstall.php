<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Entferne alle gespeicherten Optionen und Tabellen
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}dashboard_widget_tracking");
delete_option('employee_dashboard_installed');
delete_option('employee_dashboard_role_permissions');
delete_option('employee_dashboard_version');
?>
