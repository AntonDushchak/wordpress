<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

class Installer
{
    /**
     * Activation hook: erstellt die Dashboard-Seite, falls nicht vorhanden
     */
    public static function activate(): void
    {
        // Prüfen, ob Seite existiert
        if ( ! get_page_by_path('neo-dashboard') ) {
            $page_id = wp_insert_post([
                'post_title'   => 'Dashboard',
                'post_name'    => 'neo-dashboard',
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_content' => '[neo-dashboard]',
            ]);
            if ( ! is_wp_error( $page_id ) ) {
                update_post_meta( $page_id, '_wp_page_template', 'dashboard-blank.php' );
            }
        }
    }
}
