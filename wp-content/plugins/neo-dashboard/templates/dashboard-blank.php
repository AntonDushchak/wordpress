<?php
/**
 * Template Name: Dashboard (Standalone)
 * Description: Vollständig eigenständige Dashboard‑Ansicht ohne Theme‑Header/Footer.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use NeoDashboard\Core\Router;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo esc_html( get_bloginfo( 'name' ) . ' – Dashboard' ); ?></title>

    <?php
    /**
     * Hier werden alle CSS-Dateien eingebunden.
     * z. B. über add_action('neo_dashboard_head', [ AssetManager, 'enqueueAssets' ]);
     */
    do_action( 'neo_dashboard_head' );
    ?>
</head>
<body <?php body_class( 'neo-dashboard-standalone' ); ?>>

    <?php
    /**
     * Plugins können hier HTML oder Skripte einsetzen,
     * bevor das Dashboard gerendert wird.
     */
    do_action( 'neo_dashboard_body_start' );
    ?>

    <?php
    // Rendere das modularisierte Dashboard-Layout
    do_action( 'neo_dashboard_body_content' );
    ?>

    <?php
    /**
     * Plugins können hier nach dem Dashboard-Inhalt eingreifen.
     */
    do_action( 'neo_dashboard_body_end' );
    ?>

    <?php
    /**
     * Hier werden alle JS-Dateien eingebunden.
     * z. B. über add_action('neo_dashboard_footer', [ AssetManager, 'enqueueAssets' ]);
     */
    do_action( 'neo_dashboard_footer' );
    ?>
</body>
</html>
