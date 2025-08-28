<?php
/**
 * Employee Dashboard - Export-Funktion
 * 
 * Diese Datei stellt eine Export-Funktion für Berichte bereit.
 * Unterstützt CSV- und PDF-Exporte für Dashboard-Daten.
 * 
 * Funktionen:
 * - Export von Daten im CSV-Format
 * - Export von Daten im PDF-Format
 * - AJAX-Handler für den Exportprozess
 * 
 * @package EmployeeDashboard
 * @version 1.2
 */

class Employee_Dashboard_Export {
    public function __construct() {
        // AJAX-Handler für CSV-Export
        add_action('wp_ajax_export_csv', [$this, 'export_csv']);
        
        // AJAX-Handler für PDF-Export
        add_action('wp_ajax_export_pdf', [$this, 'export_pdf']);
    }

    /**
     * Exportiert die Dashboard-Daten als CSV-Datei mit UTF-8-Kodierung
     */
    public function export_csv() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!', 'user' => wp_get_current_user()->user_login]);
        }
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="dashboard-export.csv"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM für korrekte Zeichenkodierung
        fputcsv($output, ['Spalte 1', 'Spalte 2', 'Spalte 3']);
        
        $data = get_option('employee_dashboard_export_data', []);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Exportiert die Dashboard-Daten als PDF-Datei
     */
    public function export_pdf() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => 'Keine Berechtigung!', 'user' => wp_get_current_user()->user_login]);
        }
        
        if (!class_exists('\Mpdf\Mpdf')) {
            wp_send_json_error(['error' => 'MPDF-Bibliothek nicht gefunden! Stelle sicher, dass MPDF installiert ist.']);
        }
        
        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML('<h1>Dashboard-Bericht</h1>');
        
        $data = get_option('employee_dashboard_export_data', []);
        foreach ($data as $row) {
            $mpdf->WriteHTML('<p>' . implode(' | ', $row) . '</p>');
        }
        
        $mpdf->Output('dashboard-export.pdf', 'D');
        exit;
    }
}

// Instanziert die Klasse, um die Funktionalität zu aktivieren
new Employee_Dashboard_Export();
?>
