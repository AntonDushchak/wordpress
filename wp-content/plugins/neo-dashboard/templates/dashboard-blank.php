<?php

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
    do_action( 'neo_dashboard_body_start' );
    ?>

    <?php
    do_action( 'neo_dashboard_body_content' );
    ?>

    <?php
    do_action( 'neo_dashboard_body_end' );
    ?>

    <?php
    wp_footer();
    
    do_action( 'neo_dashboard_footer' );
    ?>
</body>
</html>
