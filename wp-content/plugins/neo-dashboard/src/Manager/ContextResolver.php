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
        $pagename = get_query_var('pagename', '');
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        error_log("ContextResolver: Raw data - URI: '{$uri}', pagename: '{$pagename}', section: '{$section}'");
        
        $clean_uri = strtok($uri, '?');
        if ($clean_uri === '/neo-dashboard/' || $clean_uri === '/neo-dashboard') {
            error_log("ContextResolver: Detected main dashboard page, setting context to 'dashboard-home'");
            return 'dashboard-home';
        }
        
        if (str_starts_with($clean_uri, '/neo-dashboard/')) {
            $path_parts = explode('/', trim($clean_uri, '/'));
            error_log("ContextResolver: Path parts: " . print_r($path_parts, true));
            
            if (count($path_parts) >= 2 && $path_parts[0] === 'neo-dashboard') {
                $context = implode('/', array_slice($path_parts, 1));
                error_log("ContextResolver: Final context from URI: '{$context}'");
                return $context;
            }
        }
        
        if (!empty($section)) {
            error_log("ContextResolver: Using section from query var: '{$section}'");
            return $section;
        }
        
        error_log("ContextResolver: Using default context: 'dashboard-home'");
        return 'dashboard-home';
    }
}
