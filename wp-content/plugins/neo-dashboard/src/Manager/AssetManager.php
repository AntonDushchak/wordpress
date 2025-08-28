<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Router;
use NeoDashboard\Core\Logger;

/**
 * AssetManager v3.2.0
 * -------------------------------------------------
 *  • Keine Verwendung von wp_print_styles()/scripts
 *  • Lädt nur dedizierte Plugin-Assets (Bootstrap,
 *    Icons, Dashboard-Core, Notifications)
 *  • REST-Config inline (NeoDash-Objekt)
 *  • Hooks für Plugin-Zusatz-Assets bleiben erhalten
 */
class AssetManager
{
    private const BOOTSTRAP_VERSION = '5.3.2';
    private const ICONS_VERSION     = '1.10.5';

    /**
     * Initiale Hook-Registrierung.
     */
    public function register(): void
    {
        Logger::info('AssetManager:register – lean mode');

        add_action('neo_dashboard_head',   [ $this, 'printHeadAssets' ],   5);
        add_action('neo_dashboard_footer', [ $this, 'printFooterAssets' ], 5);
        add_filter('show_admin_bar',       [ $this, 'maybeHideAdminBar' ]);
    }

    /* ------------------------------------------------------------------ *
     * <head> – Styles
     * ------------------------------------------------------------------ */
    public function printHeadAssets(): void
    {
        if ( ! $this->isDashboardPage() ) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);

        // Bootstrap CSS
        printf(
            '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@%s/dist/css/bootstrap.min.css" />' . PHP_EOL,
            esc_attr(self::BOOTSTRAP_VERSION)
        );

        // Bootstrap Icons
        printf(
            '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@%s/font/bootstrap-icons.css" />' . PHP_EOL,
            esc_attr(self::ICONS_VERSION)
        );

        // Dashboard-Core CSS
        printf(
            '<link rel="stylesheet" href="%1$sassets/dashboard.css?v=%2$s" />' . PHP_EOL,
            esc_url($base),
            esc_attr(NEO_DASHBOARD_VERSION)
        );

        // Plugin-Spezifische Styles
        do_action(
            'neo_dashboard_enqueue_plugin_assets_css',
            get_query_var(Router::QUERY_VAR_SECTION, '')
        );
    }

    /* ------------------------------------------------------------------ *
     * </body> – Scripts
     * ------------------------------------------------------------------ */
    public function printFooterAssets(): void
    {
        if ( ! $this->isDashboardPage() ) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);

        // -------------------------------------------------------------
        // Inline-Konfig (REST-URL + Nonce) – ersetzt wp_localize_script
        // -------------------------------------------------------------
        $config = [
            'restUrl' => rest_url('neo-dashboard/v1'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ];
        printf(
            '<script>var NeoDash = %s;</script>' . PHP_EOL,
            wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
        );

        // Bootstrap Bundle JS
        printf(
            '<script src="https://cdn.jsdelivr.net/npm/bootstrap@%s/dist/js/bootstrap.bundle.min.js"></script>' . PHP_EOL,
            esc_attr(self::BOOTSTRAP_VERSION)
        );

        // Dashboard-Core JS
        printf(
            '<script src="%1$sassets/js/dashboard.js?v=%2$s"></script>' . PHP_EOL,
            esc_url($base),
            esc_attr(NEO_DASHBOARD_VERSION)
        );

        // Notifications-Modul
        printf(
            '<script src="%1$sassets/js/notifications.js?v=%2$s"></script>' . PHP_EOL,
            esc_url($base),
            esc_attr(NEO_DASHBOARD_VERSION)
        );

        // Plugin-Spezifische Scripts
        do_action(
            'neo_dashboard_enqueue_plugin_assets_js',
            get_query_var(Router::QUERY_VAR_SECTION, '')
        );
    }

    /* ------------------------------------------------------------------ *
     * Utilities
     * ------------------------------------------------------------------ */
    private function isDashboardPage(): bool
    {
        return is_page('neo-dashboard')
            || '' !== get_query_var(Router::QUERY_VAR_SECTION, '');
    }

    public function maybeHideAdminBar(bool $show): bool
    {
        return $this->isDashboardPage() ? false : $show;
    }
}
