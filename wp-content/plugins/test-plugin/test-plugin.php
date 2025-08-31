<?php
/*
Plugin Name: Test Plugin
Description: Test Plugin
Version: 1.0
Author: Test
*/

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1) Dependency‑Check: Neo Dashboard Core muss aktiv sein
add_action( 'plugins_loaded', static function() {

    if ( ! class_exists( \NeoDashboard\Core\Router::class ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        add_action( 'admin_notices', static function() {
            echo '<div class="notice notice-error"><p>';
            esc_html_e(
                'Neo Dashboard Examples wurde deaktiviert, weil "Neo Dashboard Core" nicht aktiv ist.',
                'neo-dashboard-examples'
            );
            echo '</p></div>';
        } );
        return;
    }

    // 2) Plugin‑Assets nur im Dashboard laden
    add_action( 'neo_dashboard_enqueue_assets', static function() {
        // Beispiel: eigenes CSS
        wp_enqueue_style(
            'test-plugin-css',
            plugin_dir_url( __FILE__ ) . 'assets/test-plugin.css',
            [],
            '1.0.0'
        );
        // Beispiel: eigenes JS
        wp_enqueue_script(
            'test-plugin-js',
            plugin_dir_url( __FILE__ ) . 'assets/test-plugin.js',
            [ 'bootstrap-js' ],
            '1.0.0',
            true
        );
    } );

