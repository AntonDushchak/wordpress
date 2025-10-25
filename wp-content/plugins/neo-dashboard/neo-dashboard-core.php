<?php
declare(strict_types=1);
/**
 * Plugin Name:     Neo Dashboard Core
 * Description:     Modernes Dashboard‑Framework mit PSR‑4‑Autoloader und Manager‑Architektur.
 * Version:         3.0.3
 * Author:          Neo
 * Text Domain:     neo-dashboard-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ------------------------------------------------------------------------- *
 * Versionskonstante
 * ------------------------------------------------------------------------- */
if ( ! defined( 'NEO_DASHBOARD_VERSION' ) ) {
    define( 'NEO_DASHBOARD_VERSION', '3.0.3' );
}

/* ------------------------------------------------------------------------- *
 * Plugin file constant for activation/deactivation hooks
 * ------------------------------------------------------------------------- */
if ( ! defined( 'NEO_DASHBOARD_PLUGIN_FILE' ) ) {
    define( 'NEO_DASHBOARD_PLUGIN_FILE', __FILE__ );
}

/* ------------------------------------------------------------------------- *
 * QUERY VAR SECTION
 * ------------------------------------------------------------------------- */
if ( ! defined( 'NEO_DASHBOARD_QUERY_VAR_SECTION' ) ) {
    define( 'NEO_DASHBOARD_QUERY_VAR_SECTION', 'neo_section' );
}

/* ------------------------------------------------------------------------- *
 * Template Path
 * ------------------------------------------------------------------------- */
if ( ! defined( 'NEO_DASHBOARD_TEMPLATE_PATH' ) ) {
    define( 'NEO_DASHBOARD_TEMPLATE_PATH', plugin_dir_path( __FILE__ ) . 'templates/' );
}


/* ------------------------------------------------------------------------- *
 * PSR‑4 Autoloader
 * ------------------------------------------------------------------------- */
spl_autoload_register(static function(string $class): void {
    $prefix = 'NeoDashboard\\Core\\';
    if ( str_starts_with( $class, $prefix ) ) {
        $rel_class = substr( $class, strlen( $prefix ) );
        $file      = __DIR__ . '/src/' . str_replace('\\', '/', $rel_class ) . '.php';
        if ( is_file( $file ) ) {
            require $file;
        }
    }
});

// Bootstrap LifecycleLogger early in the plugin load sequence
add_action('plugins_loaded', function() {
    // Instantiate to hook into WordPress events
    new \NeoDashboard\Core\LifecycleLogger();
}, 1);

/* ------------------------------------------------------------------------- *
 * Logger System
 * ------------------------------------------------------------------------- */
require_once __DIR__ . '/src/Logger.php';
require_once __DIR__ . '/src/LifecycleLogger.php';

/* ------------------------------------------------------------------------- *
 * Helper Funktionen
 * ------------------------------------------------------------------------- */
require_once __DIR__ . '/src/functions.php';

/* ------------------------------------------------------------------------- *
 * UI Component System
 * ------------------------------------------------------------------------- */
require_once __DIR__ . '/src/UIComponent.php';

/* ------------------------------------------------------------------------- *
 * Theme Switcher
 * ------------------------------------------------------------------------- */
require_once __DIR__ . '/src/Manager/ThemeSwitcher.php';

/* ------------------------------------------------------------------------- *
 * Bootstrap: Hook registration and initialization
 * ------------------------------------------------------------------------- */
\NeoDashboard\Core\Bootstrap::registerHooks();


