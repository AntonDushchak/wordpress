<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Router;
use NeoDashboard\Core\Logger;

/**
 * AssetManager v3.4.0
 * -------------------------------------------------
 *  • Использует стандартные WordPress хуки wp_enqueue_script/style
 *  • Автоматическое определение зависимостей
 *  • Hooks для плагинов остаются сохранены
 *  • REST-Config через wp_localize_script
 *  • Исправлены проблемы с timing и определением страниц
 */
class AssetManager
{
    private const BOOTSTRAP_VERSION = '5.3.2';
    private const ICONS_VERSION     = '1.10.5';

    private static array $assets_registered = [];

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets'], 5);
        add_action('neo_dashboard_head', [$this, 'printHeadAssets'], 5);
        add_action('wp_head', [$this, 'addFavicon'], 1);
        add_action('neo_dashboard_footer', [$this, 'printFooterAssets'], 5);
        add_filter('show_admin_bar', [$this, 'maybeHideAdminBar']);
    }

    public function enqueueAssets(): void
    {
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');

        if (isset(self::$assets_registered[$section])) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);

        // Bootstrap CSS
        wp_enqueue_style(
            'neo-dashboard-bootstrap',
            "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/css/bootstrap.min.css",
            [],
            self::BOOTSTRAP_VERSION
        );

        // Bootstrap Icons
        wp_enqueue_style(
            'neo-dashboard-bootstrap-icons',
            "https://cdn.jsdelivr.net/npm/bootstrap-icons@" . self::ICONS_VERSION . "/font/bootstrap-icons.css",
            [],
            self::ICONS_VERSION
        );

        // Core CSS
        if ($this->fileExists('assets/dashboard.css')) {
            wp_enqueue_style(
                'neo-dashboard-core',
                $base . 'assets/dashboard.css',
                ['neo-dashboard-bootstrap'],
                NEO_DASHBOARD_VERSION
            );
        }
        
        // Theme Switcher CSS
        if ($this->fileExists('assets/theme-switcher.css')) {
            wp_enqueue_style(
                'neo-dashboard-theme-switcher',
                $base . 'assets/theme-switcher.css',
                ['neo-dashboard-core'],
                NEO_DASHBOARD_VERSION
            );
        }

        // Bootstrap JS
        wp_enqueue_script(
            'neo-dashboard-bootstrap',
            "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/js/bootstrap.bundle.min.js",
            ['jquery'],
            self::BOOTSTRAP_VERSION,
            true
        );

        // Core JS
        if ($this->fileExists('assets/js/dashboard.js')) {
            wp_enqueue_script(
                'neo-dashboard-core',
                $base . 'assets/js/dashboard.js',
                ['neo-dashboard-bootstrap', 'jquery'],
                NEO_DASHBOARD_VERSION,
                true
            );
        }

        // Notifications
        if ($this->fileExists('assets/js/notifications.js')) {
            wp_enqueue_script(
                'neo-dashboard-notifications',
                $base . 'assets/js/notifications.js',
                ['neo-dashboard-core'],
                NEO_DASHBOARD_VERSION,
                true
            );
        }

        wp_localize_script('neo-dashboard-core', 'NeoDash', [
            'restUrl' => rest_url('neo-dashboard/v1'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);

        self::$assets_registered[$section] = true;
    }

    public function printHeadAssets(): void
    {
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');
        $this->enqueueAssets();

        // Add favicon links
        $this->printFaviconLinks();

        if ($section !== '') {
            $plugin_prefix = $this->getPluginPrefixFromSection($section);

            if ($plugin_prefix) {
                $hook_name = "neo_dashboard_enqueue_{$plugin_prefix}_assets_css";
                do_action($hook_name, $section);
            }
        } else if ($section === '') {
            do_action('neo_dashboard_enqueue_widget_assets_css');
        }

        if (function_exists('wp_print_styles')) {
            wp_print_styles();
        }
    }

    public function printFooterAssets(): void
    {
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');
        $this->enqueueAssets();

        if ($section !== '') {
            $plugin_prefix = $this->getPluginPrefixFromSection($section);

            if ($plugin_prefix) {
                $hook_name = "neo_dashboard_enqueue_{$plugin_prefix}_assets_js";
                do_action($hook_name, $section);
            }
        } else if ($section === '') {
            do_action('neo_dashboard_enqueue_widget_assets_js');
        }

        if (function_exists('wp_print_scripts')) {
            wp_print_scripts();
        }
    }

    private function fileExists(string $relative_path): bool
    {
        return file_exists(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . $relative_path);
    }

    public function maybeHideAdminBar(bool $show): bool
    {
        return $this->isDashboardPage() ? false : $show;
    }

    private function isDashboardPage(): bool
    {
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');
        $pagename = get_query_var('pagename', '');

        return $section !== '' || $pagename === 'neo-dashboard' || strpos($_SERVER['REQUEST_URI'] ?? '', '/neo-dashboard') === 0;
    }

    private function getPluginPrefixFromSection(string $section): ?string
    {
        $parts = explode('/', $section);
        $prefix = $parts[0];

        if (in_array($prefix, ['neo-dashboard', 'dashboard', 'admin'])) {
            return null;
        }

        $registry = \NeoDashboard\Core\Registry::instance();
        $sections = $registry->getSections();
        
        $section_exists = false;
        
        if (isset($sections[$section])) {
            $section_exists = true;
        }
        
        if (!$section_exists && strpos($section, '/') !== false) {
            $section_without_slash = str_replace('/', '', $section);
            if (isset($sections[$section_without_slash])) {
                $section_exists = true;
            }
        }
        
        if (!$section_exists) {
            if (isset($sections[$prefix])) {
                $section_exists = true;
            }
        }

        if ($section_exists) {
            return $prefix;
        } else {
            return null;
        }
    }

    public function addFavicon(): void
    {
        if ($this->isDashboardPage()) {
            $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
            
            if ($this->fileExists('assets/images/favicon.ico')) {
                echo '<link rel="icon" href="' . $base . 'assets/images/favicon.ico" type="image/x-icon">' . "\n";
            }
            if ($this->fileExists('assets/images/favicon-32x32.png')) {
                echo '<link rel="icon" type="image/png" sizes="32x32" href="' . $base . 'assets/images/favicon-32x32.png">' . "\n";
            }
            if ($this->fileExists('assets/images/favicon-16x16.png')) {
                echo '<link rel="icon" type="image/png" sizes="16x16" href="' . $base . 'assets/images/favicon-16x16.png">' . "\n";
            }
        }
    }

    private function printFaviconLinks(): void
    {
        if (!$this->isDashboardPage()) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
        $favicon_path = 'assets/images/favicon/';

        // Standard favicon
        if ($this->fileExists($favicon_path . 'favicon.ico')) {
            echo '<link rel="icon" href="' . $base . $favicon_path . 'favicon.ico" type="image/x-icon">' . "\n";
        }

        // PNG favicons
        if ($this->fileExists($favicon_path . 'favicon-16x16.png')) {
            echo '<link rel="icon" type="image/png" sizes="16x16" href="' . $base . $favicon_path . 'favicon-16x16.png">' . "\n";
        }
        if ($this->fileExists($favicon_path . 'favicon-32x32.png')) {
            echo '<link rel="icon" type="image/png" sizes="32x32" href="' . $base . $favicon_path . 'favicon-32x32.png">' . "\n";
        }

        // Apple Touch Icon
        if ($this->fileExists($favicon_path . 'apple-touch-icon.png')) {
            echo '<link rel="apple-touch-icon" href="' . $base . $favicon_path . 'apple-touch-icon.png">' . "\n";
        }

        // Android Chrome icons
        if ($this->fileExists($favicon_path . 'android-chrome-192x192.png')) {
            echo '<link rel="icon" type="image/png" sizes="192x192" href="' . $base . $favicon_path . 'android-chrome-192x192.png">' . "\n";
        }
        if ($this->fileExists($favicon_path . 'android-chrome-512x512.png')) {
            echo '<link rel="icon" type="image/png" sizes="512x512" href="' . $base . $favicon_path . 'android-chrome-512x512.png">' . "\n";
        }

        // Web App Manifest
        if ($this->fileExists($favicon_path . 'site.webmanifest')) {
            echo '<link rel="manifest" href="' . $base . $favicon_path . 'site.webmanifest">' . "\n";
        }

        // PWA meta tags
        echo '<meta name="theme-color" content="#ffffff">' . "\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        echo '<meta name="apple-mobile-web-app-title" content="Neo Dashboard">' . "\n";
    }
}
