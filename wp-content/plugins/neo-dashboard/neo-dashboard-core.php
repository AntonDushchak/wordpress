<?php
declare(strict_types=1);
/**
 * Plugin Name:     Neo Dashboard Core
 * Description:     Modernes Dashboard‑Framework mit PSR‑4‑Autoloader und Manager‑Architektur.
 * Version:         3.0.2
 * Author:          Code Copilot
 * Text Domain:     neo-dashboard-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ------------------------------------------------------------------------- *
 * Versionskonstante
 * ------------------------------------------------------------------------- */
if ( ! defined( 'NEO_DASHBOARD_VERSION' ) ) {
    define( 'NEO_DASHBOARD_VERSION', '3.0.2' );
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
 * Helper Funktionen
 * ------------------------------------------------------------------------- */
require_once __DIR__ . '/src/functions.php';


/* ------------------------------------------------------------------------- *
 * Bootstrap: Hook registration and initialization
 * ------------------------------------------------------------------------- */
\NeoDashboard\Core\Bootstrap::registerHooks();

/* ------------------------------------------------------------------------- *
 * Custom Roles Management
 * ------------------------------------------------------------------------- */

// Создание ролей при активации плагина
function neo_dashboard_add_roles() {
    add_role(
        'neo_editor',
        'Neo Editor',
        [
            'read'            => true,
        ]
    );

    add_role(
        'neo_mitarbeiter',
        'Neo Mitarbeiter',
        [
            'read'            => true,
        ]
    );
}

// Создание ролей при инициализации
add_action('init', 'neo_dashboard_add_roles');

// Удаление стандартных ролей WordPress
add_action('init', function () {
    if ( ! function_exists('remove_role') ) {
        return;
    }

    $roles_to_remove = [
        'subscriber',
        'contributor',
        'author',
        'editor',
        'vermittler',
    ];

    foreach ($roles_to_remove as $role) {
        remove_role($role);
    }
});

// Удаление ролей при деактивации плагина
function neo_dashboard_remove_roles() {
    remove_role('neo_admin');
    remove_role('neo_editor');
    remove_role('neo_mitarbeiter');
}


