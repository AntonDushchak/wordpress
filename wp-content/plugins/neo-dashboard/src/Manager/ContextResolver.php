<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Router;

final class ContextResolver
{
    public function isDashboard(): bool
    {
        if (is_admin()) {
            if (($_GET['page'] ?? '') === 'neo-dashboard') {
                return true;
            }

            $uri = $_SERVER['REQUEST_URI'] ?? '';
            return strpos($uri, 'wp-admin') !== false && strpos($uri, 'neo-dashboard') !== false;
        }

        $section = get_query_var(Router::QUERY_VAR_SECTION, '');
        $page = get_query_var('pagename', '');
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return $section !== '' || $page === 'neo-dashboard' || str_starts_with($uri, '/neo-dashboard');
    }

    public function current(): string
    {
        $section = get_query_var(Router::QUERY_VAR_SECTION, '');
        return $section ?: 'dashboard-home';
    }
}
