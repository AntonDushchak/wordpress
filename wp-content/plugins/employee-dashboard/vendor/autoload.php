<?php
/**
 * Employee Dashboard - Autoloader
 * 
 * Diese Datei registriert einen Autoloader für die Klassen des Plugins.
 * Alle Klassen im "includes"-Verzeichnis werden automatisch geladen.
 * 
 * @package EmployeeDashboard
 */

spl_autoload_register(function ($class) {
    $prefix = 'Employee_Dashboard_';
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';

    // Prüfen, ob die Klasse das Plugin-Präfix hat
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Dateipfad generieren
    $relative_class = str_replace('_', '-', strtolower(substr($class, strlen($prefix))));
    $file = $base_dir . 'class-' . $relative_class . '.php';

    // Datei einbinden, falls sie existiert
    if (file_exists($file)) {
        require_once $file;
    }
});
