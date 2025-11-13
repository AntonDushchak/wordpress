<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Router;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Registry;

final class AssetManager
{
    private const BOOTSTRAP_VERSION = '5.3.2';
    private const ICONS_VERSION     = '1.10.5';

    private static array $registered_contexts = [];
    private array $plugin_assets = [];

    private ContextResolver $context;
    private FaviconManager $favicon;

    public function __construct()
    {
        $this->context = new ContextResolver();
        $this->favicon = new FaviconManager();
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets'], 5);

        add_action('neo_dashboard_head', fn() => $this->printAssets('css'), 5);
        add_action('neo_dashboard_footer', fn() => $this->printAssets('js'), 5);

        add_action('wp_head', [$this->favicon, 'addFavicon'], 1);
        add_filter('show_admin_bar', [$this, 'maybeHideAdminBar']);

        add_action('neo_dashboard_register_plugin_assets', [$this, 'registerPluginAssets'], 10, 2);
        add_action('neo_dashboard_register_page_assets', [$this, 'registerPageAssets'], 10, 3);
    }

    public function enqueueAssets(): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }

        $context = $this->context->current();
        error_log("AssetManager: enqueueAssets called, context: '{$context}', URI: " . ($_SERVER['REQUEST_URI'] ?? 'undefined'));

        if (isset(self::$registered_contexts[$context])) {
            return;
        }

        Logger::info('Loading core assets', ['context' => $context]);
        $this->enqueueCoreAssets();

        self::$registered_contexts[$context] = true;
    }

    private function enqueueCoreAssets(): void
    {
        $this->enqueueCDNAssets();
        $this->enqueueLocalAsset('assets/dashboard.css', 'style', 'neo-dashboard-core', ['neo-dashboard-bootstrap']);
        $this->enqueueLocalAsset('assets/js/dashboard.js', 'script', 'neo-dashboard-core', ['neo-dashboard-bootstrap']);
        $this->enqueueLocalAsset('assets/js/notifications.js', 'script', 'neo-dashboard-notifications', ['neo-dashboard-core']);
        $this->enqueueLocalAsset('assets/js/confirm.js', 'script', 'neo-dashboard-confirm', ['neo-dashboard-bootstrap']);
        $this->enqueueLocalAsset('assets/js/toast.js', 'script', 'neo-dashboard-toast', ['neo-dashboard-bootstrap']);
    }

    private function enqueueCDNAssets(): void
    {
        wp_enqueue_style('neo-dashboard-bootstrap',
            "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/css/bootstrap.min.css",
            [],
            self::BOOTSTRAP_VERSION
        );

        wp_enqueue_style('neo-dashboard-bootstrap-icons',
            "https://cdn.jsdelivr.net/npm/bootstrap-icons@" . self::ICONS_VERSION . "/font/bootstrap-icons.css",
            [],
            self::ICONS_VERSION
        );

        wp_enqueue_script('neo-dashboard-bootstrap',
            "https://cdn.jsdelivr.net/npm/bootstrap@" . self::BOOTSTRAP_VERSION . "/dist/js/bootstrap.bundle.min.js",
            ['jquery'],
            self::BOOTSTRAP_VERSION,
            true
        );
    }

    private function enqueueLocalAsset(string $path, string $type, string $handle, array $deps = []): void
    {
        if (!$this->coreFileExists($path)) {
            return;
        }

        $src = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE) . $path;

        if ($type === 'style') {
            wp_enqueue_style($handle, $src, $deps, NEO_DASHBOARD_VERSION);
        } else {
            wp_enqueue_script($handle, $src, $deps, NEO_DASHBOARD_VERSION, true);
            wp_localize_script('neo-dashboard-core', 'NeoDash', [
                'restUrl' => rest_url('neo-dashboard/v1'),
                'nonce'   => wp_create_nonce('wp_rest'),
                'context' => $this->context->current(),
                'section' => get_query_var(Router::QUERY_VAR_SECTION, ''),
            ]);
        }
    }

    public function printAssets(string $type): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }

        $context = $this->context->current();
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');

        if ($type === 'css') {
            $this->favicon->printLinks();
        }

        Logger::info("Loading {$type} assets", compact('context', 'section'));

        $this->triggerPluginAssetHooks($context, $type); // Используем $context вместо $section
        do_action("neo_dashboard_enqueue_page_{$type}", $context, $section);

        $fn = $type === 'css' ? 'wp_print_styles' : 'wp_print_scripts';
        if (function_exists($fn)) {
            $fn();
        }
    }

    public function registerPluginAssets(string $plugin_id, array $assets): void
    {
        $this->plugin_assets[$plugin_id] = $assets;

        Logger::info('Registered plugin assets', [
            'plugin_id' => $plugin_id,
            'css' => count($assets['css'] ?? []),
            'js' => count($assets['js'] ?? []),
        ]);
    }

    public function registerPageAssets(string $page_slug, string $type, array $config): void
    {
        $handle = $config['handle'] ?? '';
        if (!$handle) {
            Logger::error('Missing handle in page asset config', ['page' => $page_slug]);
            return;
        }

        $this->plugin_assets['page_specific'][$type][$handle] = array_merge($config, [
            'contexts' => [$page_slug]
        ]);
    }

    private function triggerPluginAssetHooks(string $context, string $type): void
    {
        error_log("AssetManager: triggerPluginAssetHooks called - context: '{$context}', type: {$type}");
        
        $prefix = $this->getPluginPrefixFromSection($context);
        if ($prefix) {
            do_action("neo_dashboard_enqueue_{$prefix}_assets_{$type}", $context);
        }

        $this->loadPluginAssets($context, $type);
    }

    private function loadPluginAssets(string $context, string $type): void
    {
        error_log("AssetManager: Loading plugin assets for context: {$context}, type: {$type}");
        error_log("AssetManager: Available plugins: " . print_r(array_keys($this->plugin_assets), true));
        
        foreach ($this->plugin_assets as $plugin => $assets) {
            error_log("AssetManager: Processing plugin: {$plugin}");
            foreach ($assets[$type] ?? [] as $handle => $config) {
                $contexts = $config['contexts'] ?? ['*'];
                error_log("AssetManager: Asset {$handle} contexts: " . print_r($contexts, true) . ", current: {$context}");
                
                if (!in_array('*', $contexts) && !in_array($context, $contexts)) {
                    Logger::debug("Skipping asset {$handle} for context {$context}", [
                        'required_contexts' => $contexts,
                        'current_context' => $context
                    ]);
                    error_log("AssetManager: Skipping asset {$handle} for context {$context}");
                    continue;
                }

                $src = $config['src'] ?? '';
                if (!$src) {
                    Logger::warning("Missing src for asset {$handle}");
                    continue;
                }

                $deps = $config['deps'] ?? [];
                $ver = $config['version'] ?? '1.0.0';

                Logger::info("Loading {$type} asset: {$handle}", [
                    'src' => $src,
                    'context' => $context,
                    'plugin' => $plugin
                ]);

                if ($type === 'css') {
                    wp_enqueue_style($handle, $src, $deps, $ver);
                    error_log("AssetManager: Successfully enqueued CSS: {$handle} from {$src} for context {$context}");
                } else {
                    wp_enqueue_script($handle, $src, $deps, $ver, $config['in_footer'] ?? true);
                    
                    if (isset($config['localize']) && is_array($config['localize'])) {
                        $localize_config = $config['localize'];
                        $object_name = $localize_config['object_name'] ?? '';
                        $data = $localize_config['data'] ?? [];
                        
                        if ($object_name && !empty($data)) {
                            wp_localize_script($handle, $object_name, $data);
                            Logger::info("Localized script {$handle}", [
                                'object_name' => $object_name,
                                'data_keys' => array_keys($data)
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function getPluginPrefixFromSection(string $section): ?string
    {
        $parts = explode('/', $section);
        $prefix = $parts[0];

        if (in_array($prefix, ['neo-dashboard', 'dashboard', 'admin', 'home'])) {
            return null;
        }

        $sections = Registry::instance()->getSections();
        return isset($sections[$section]) || isset($sections[$prefix]) ? $prefix : null;
    }

    public function maybeHideAdminBar(bool $show): bool
    {
        return $this->context->isDashboard() ? false : $show;
    }

    private function coreFileExists(string $path): bool
    {
        return file_exists(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . $path);
    }
}
