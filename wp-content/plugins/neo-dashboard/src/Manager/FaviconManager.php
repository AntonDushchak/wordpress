<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

final class FaviconManager
{
    private ContextResolver $context;

    public function __construct()
    {
        $this->context = new ContextResolver();
    }

    public function addFavicon(): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
        if ($this->exists('assets/images/favicon.ico')) {
            echo '<link rel="icon" href="' . esc_url($base . 'assets/images/favicon.ico') . '" type="image/x-icon">' . "\n";
        }
    }

    public function printLinks(): void
    {
        if (!$this->context->isDashboard()) {
            return;
        }

        $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
        $paths = ['assets/images/favicon/', 'assets/images/'];
        $files = [
            'favicon.ico'                => 'image/x-icon',
            'favicon-16x16.png'          => 'image/png',
            'favicon-32x32.png'          => 'image/png',
            'apple-touch-icon.png'       => 'image/png',
            'android-chrome-192x192.png' => 'image/png',
            'android-chrome-512x512.png' => 'image/png',
            'site.webmanifest'           => '',
        ];

        foreach ($paths as $p) {
            foreach ($files as $file => $type) {
                if ($this->exists($p . $file)) {
                    $typeAttr = $type ? ' type="' . esc_attr($type) . '"' : '';
                    echo '<link rel="icon"' . $typeAttr . ' href="' . esc_url($base . $p . $file) . '">' . "\n";
                }
            }
        }

        echo <<<HTML
<meta name="theme-color" content="#ffffff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Neo Dashboard">
<meta name="application-name" content="Neo Dashboard">
<meta name="msapplication-TileColor" content="#ffffff">
HTML;
    }

    private function exists(string $path): bool
    {
        return file_exists(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . $path);
    }
}
