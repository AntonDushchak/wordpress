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

        if ($section !== '') {
            // Определяем, к какому плагину относится секция
            $plugin_prefix = $this->getPluginPrefixFromSection($section);

            if ($plugin_prefix) {
                // Вызываем хук только для соответствующего плагина
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
            // Определяем, к какому плагину относится секция
            $plugin_prefix = $this->getPluginPrefixFromSection($section);

            if ($plugin_prefix) {
                // Вызываем хук только для соответствующего плагина
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

    /**
     * Определяет префикс плагина из секции
     * Например: 'neo-umfrage/surveys' -> 'neo-umfrage'
     */
    private function getPluginPrefixFromSection(string $section): ?string
    {
        // Извлекаем префикс плагина (часть до первого слеша)
        $parts = explode('/', $section);
        $prefix = $parts[0];

        // Проверяем, что это не системная секция
        if (in_array($prefix, ['neo-dashboard', 'dashboard', 'admin'])) {
            return null;
        }

        // Проверяем, зарегистрирована ли секция
        $registry = \NeoDashboard\Core\Registry::instance();
        $sections = $registry->getSections();
        
        // Проверяем, существует ли секция в Registry
        $section_exists = false;
        
        // Сначала проверяем точное совпадение
        if (isset($sections[$section])) {
            $section_exists = true;
        }
        
        // Если не найдена, ищем без слешей (neo-umfrage/statistics -> neo-umfragestatistics)
        if (!$section_exists && strpos($section, '/') !== false) {
            $section_without_slash = str_replace('/', '', $section);
            if (isset($sections[$section_without_slash])) {
                $section_exists = true;
            }
        }
        
        // Если не найдена, ищем по префиксу (neo-umfrage/statistics -> neo-umfrage)
        if (!$section_exists) {
            if (isset($sections[$prefix])) {
                $section_exists = true;
            }
        }

        // Возвращаем префикс только если секция существует в Registry
        if ($section_exists) {
            return $prefix;
        } else {
            return null;
        }
    }
}
