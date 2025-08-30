<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Manager\RestManager;

class Bootstrap
{
    /**
     * Registriert alle Plugin-Hooks: Activation, Deactivation und Init
     */
    public static function registerHooks(): void
    {
        // Activation: Rewrite-Rules für Dashboard registrieren und flushen
        register_activation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            static function(): void {
                $router = new Router();
                $router->registerRoutes();
                flush_rewrite_rules();
            }
        );

        // Activation: Dashboard-Seite anlegen via Installer
        register_activation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            [ \NeoDashboard\Core\Installer::class, 'activate' ]
        );

        // Deactivation: Rewrite-Rules flushen
        register_deactivation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            'flush_rewrite_rules'
        );

        // Bootstrap in Deinem Haupt-Plugin-File (z.B. my-plugin.php)
        add_action('plugins_loaded', function() {
            new RestManager();
        }, 1);

        // Runtime-Init: Router, Dashboard & Assets konfigurieren
        add_action('init', [self::class, 'init']);

        add_action('init', function() {
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
        });
    }

    /**
     * Führe Runtime-Initialisierung aus: Router-Hooks, Dashboard-Run
     */
    public static function init(): void
    {
        // Router-Hooks zentral in der Router-Klasse registrieren
        $router = new Router();
        $router->registerHooks();

        // Dashboard initialisieren (Manager & Assets)
        $dashboard = new Dashboard();
        $dashboard->run();

    }
}
