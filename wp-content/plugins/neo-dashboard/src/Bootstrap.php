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

        // Activation: Custom roles erstellen
        register_activation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            [Roles::class, 'addRoles']
        );

        // Activation: Neo Capabilities hinzufügen
        register_activation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            [AccessControl::class, 'addNeoCapabilities']
        );

        // Deactivation: Rewrite-Rules flushen und Custom roles entfernen
        register_deactivation_hook(
            NEO_DASHBOARD_PLUGIN_FILE,
            function(): void {
                flush_rewrite_rules();
                Roles::removeRoles();
            }
        );

        // Bootstrap in Deinem Haupt-Plugin-File (z.B. my-plugin.php)
        add_action('plugins_loaded', function() {
            new RestManager();
        }, 1);

        // Runtime-Init: Router, Dashboard & Assets konfigurieren
        add_action('init', [self::class, 'init']);

        // Access Control initialisieren после загрузки WordPress
        add_action('wp_loaded', function() {
            AccessControl::init();
        });

        // Security Enforcer initialisieren
        SecurityEnforcer::init();

        add_action('init', function() {
            add_shortcode('neo_auth_test', function() {
                ob_start();
                include NEO_DASHBOARD_TEMPLATE_PATH . 'auth-test.php';
                return ob_get_clean();
            });
        });

        // Standard-WordPress-Rollen entfernen
        add_action('init', [Roles::class, 'removeDefaultRoles']);
      
        add_action('init', function() {
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
        });

        add_action('admin_menu', function() {
            add_menu_page(
                'Neo Dashboard',
                'Neo Dashboard',
                'read',
                'neo-dashboard-link',
                '',
                'dashicons-dashboard',
                3
            );
        });

        add_action('admin_head', function() {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const neoDashboardLink = document.querySelector('a[href*="neo-dashboard-link"]');
                if (neoDashboardLink) {
                    neoDashboardLink.href = '<?php echo home_url("/neo-dashboard"); ?>';
                    neoDashboardLink.target = '_self';
                }
            });
            </script>
            <?php
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
