<?php
/**
 * Template Name: Dashboard (Standalone) v1
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
    wp_head();
    
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
    wp_footer();
    
    do_action( 'neo_dashboard_footer' );
    ?>
</body>
</html>
