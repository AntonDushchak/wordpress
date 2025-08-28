<?php
/**
 * Plugin Name:     Neo Dashboard Examples
 * Description:     Beispiel‑Erweiterung für Neo Dashboard Core (Sidebar‑Gruppen, Sections, Widgets, Notifications, Custom Assets).
 * Version:         1.1.0
 * Author:          Dein Name
 * Text Domain:     neo-dashboard-examples
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
            'neo-dashboard-examples-css',
            plugin_dir_url( __FILE__ ) . 'assets/css/examples.css',
            [],
            '1.0.0'
        );
        // Beispiel: eigenes JS
        wp_enqueue_script(
            'neo-dashboard-examples-js',
            plugin_dir_url( __FILE__ ) . 'assets/js/examples.js',
            [ 'bootstrap-js' ],
            '1.0.0',
            true
        );
    } );


    // 4) REST-API Beispiel-Routen registrieren
    add_action( 'neo_dashboard_register_rest_routes', static function( \NeoDashboard\Core\Manager\RestManager $rest ) {
        // a) Einfaches Hello-World-Beispiel
        $rest->registerRoute(
            '/examples/hello',
            static function( \WP_REST_Request $request ) {
                return [
                    'message' => 'Hello from Neo Dashboard Examples!',
                    'time'    => current_time( 'mysql' ),
                ];
            },
            WP_REST_Server::READABLE
        );

        // b) Beispiel: Letzte 5 Beiträge zurückliefern
        $rest->registerRoute(
            '/examples/posts',
            static function( \WP_REST_Request $request ) {
                $count = (int) $request->get_param( 'count' ) ?: 5;
                $posts = get_posts( [
                    'numberposts' => $count,
                    'post_status' => 'publish',
                ] );
                return array_map( static fn( $p ) => [
                    'ID'    => $p->ID,
                    'title' => get_the_title( $p ),
                    'link'  => get_permalink( $p ),
                ], $posts );
            },
            WP_REST_Server::READABLE,
            // optional: validiere den Parameter "count"
            [
                'count' => [
                    'required'          => false,
                    'type'              => 'integer',
                    'validate_callback' => static fn( $v, $req, $key ) => is_numeric( $v ) && $v > 0 && $v <= 20,
                    'sanitize_callback' => 'absint',
                ],
            ]
        );
    }, 10 );


    // 3) Dashboard‑Registrierungen
    add_action( 'neo_dashboard_init', static function() {
        // Sidebar‑Gruppe „Examples“
        do_action( 'neo_dashboard_register_sidebar_item', [
            'slug'     => 'examples-group',
            'label'    => __( 'Examples', 'neo-dashboard-examples' ),
            'icon'     => 'bi-lightbulb',
            'url'      => '/neo-dashboard/examples',
            'position' => 5,
            'is_group' => true,
        ] );

        // Unterpunkt 1: Willkommen
        do_action( 'neo_dashboard_register_sidebar_item', [
            'slug'     => 'examples-welcome',
            'label'    => __( 'Willkommen', 'neo-dashboard-examples' ),
            'icon'     => 'bi-hand-thumbs-up',
            'url'      => '/neo-dashboard/examples-welcome',
            'position' => 6,
            'parent'   => 'examples-group',
        ] );

        // Unterpunkt 2: Info
        do_action( 'neo_dashboard_register_sidebar_item', [
            'slug'     => 'examples-info',
            'label'    => __( 'Info', 'neo-dashboard-examples' ),
            'icon'     => 'bi-info-circle',
            'url'      => '/neo-dashboard/examples-info',
            'position' => 7,
            'parent'   => 'examples-group',
        ] );

        // Section „Willkommen“ mit Template
        do_action( 'neo_dashboard_register_section', [
            'slug'          => 'examples-welcome',
            'label'         => __( 'Willkommen', 'neo-dashboard-examples' ),
            'icon'          => 'bi-hand-thumbs-up',
            'template_path' => plugin_dir_path( __FILE__ ) . 'templates/welcome.php',
            'roles'         => null,
        ] );

        // Section „Info“ mit Callback
        do_action( 'neo_dashboard_register_section', [
            'slug'     => 'examples-info',
            'label'    => __( 'Info', 'neo-dashboard-examples' ),
            'icon'     => 'bi-info-circle',
            'callback' => static function() {
                echo '<p>' . esc_html__( 'Dies ist die Info-Sektion des Examples-Plugins.', 'neo-dashboard-examples' ) . '</p>';
            },
            'roles'    => null,
        ] );

        // Widget: Quick Stats (nur für Admin & Editor)
        do_action( 'neo_dashboard_register_widget', [
            'id'     => 'examples-quick-stats',
            'label'    => __( 'Quick Stats', 'neo-dashboard-examples' ),
            'icon'     => 'bi-speedometer2',
            'priority' => 5,
            'callback' => static function() {
                $count = wp_count_posts()->publish;
                printf(
                    '<p>%s: %d</p>',
                    esc_html__( 'Veröffentlichte Beiträge', 'neo-dashboard-examples' ),
                    intval( $count )
                );
            },
            'roles'    => [ 'administrator', 'editor' ],
        ] );

        // Widget: Aktuelle Uhrzeit
        do_action( 'neo_dashboard_register_widget', [
            'id'     => 'examples-timestamp',
            'label'    => __( 'Aktuelle Uhrzeit', 'neo-dashboard-examples' ),
            'icon'     => 'bi-clock',
            'priority' => 10,
            'callback' => static function() {
                echo '<p>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</p>';
            },
            'roles'    => null,
        ] );

        // Notification: Willkommen
        do_action( 'neo_dashboard_register_notification', [
            'id'        => 'examples-welcome-notice',
            'message'     => '<strong>' . esc_html__( 'Willkommen im Neo Dashboard Examples Plugin!', 'neo-dashboard-examples' ) . '</strong>',
            'priority'    => 1,
            'dismissible' => true,
            'roles'       => null,
        ] );

        // Notification: Wochenende (nur Samstag & Sonntag)
        $dow = intval( current_time( 'w' ) ); // 0 = Sonntag, 6 = Samstag
        if ( in_array( $dow, [0, 6], true ) ) {
            do_action( 'neo_dashboard_register_notification', [
                'id'        => 'examples-weekend-alert',
                'message'     => '<strong>' . esc_html__( 'Happy Weekend! Schau dir die neuen Beispiele an.', 'neo-dashboard-examples' ) . '</strong>',
                'priority'    => 2,
                'dismissible' => true,
                'roles'       => null,
            ] );
        }
    }, 120 );
} );
