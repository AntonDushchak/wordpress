<?php

declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Manager\ContentManager;


/**
 * Dashboard-Router für Rewrite, Section-Routing und Rendering
 */
class Router
{
    public const QUERY_VAR_SECTION = 'neo_section';

    /** Registriert alle Hooks für das Dashboard */
    public function registerHooks(): void
    {
        $this->registerRoutes();
        add_filter('query_vars', [ $this, 'addQueryVars' ]);
        add_filter('template_include', [ $this, 'maybe_load_dashboard_template' ], 99 );
        add_shortcode('neo-dashboard', [ ContentManager::class, 'render' ]);
        add_shortcode('dashboard',     [ ContentManager::class, 'render' ]);


    }

    /**
     * Registriert die URL-Struktur für /neo-dashboard/{section} und erlaubt Slash-separierte Subpfade
     */
    public function registerRoutes(): void
    {
        add_rewrite_tag(
            '%' . self::QUERY_VAR_SECTION . '%',
            '(.+)' // Mehrteilige Pfade wie neo-worker-is/view-contacts
        );

        add_rewrite_rule(
            '^neo-dashboard/?$',
            'index.php?pagename=neo-dashboard',
            'top'
        );

        add_rewrite_rule(
            '^neo-dashboard/(.+)?$',
            'index.php?pagename=neo-dashboard&' . self::QUERY_VAR_SECTION . '=$matches[1]',
            'top'
        );
    }

    /**
     * Ermöglicht Zugriff auf die Query-Variable "neo_section"
     */
    public function addQueryVars(array $vars): array
    {
        $vars[] = self::QUERY_VAR_SECTION;
        return $vars;
    }

    /**
     * Ersetzt das Template bei /neo-dashboard Seiten mit einem Blank-Layout
     */
    public function maybe_load_dashboard_template(?string $template): string
    {
        $template = $template ?? '';

        $section = get_query_var(self::QUERY_VAR_SECTION);

        Logger::info('NeoDashboard Template-Weiche', [
            'neo_section' => $section
        ]);

        if (is_page('neo-dashboard') || $section) {
            $blank = plugin_dir_path(__DIR__) . 'templates/dashboard-blank.php';
            if (file_exists($blank)) {
                Logger::info('NeoDashboard Template gefunden', [
                    'blank Path' => $blank
                ]);
                return $blank;
            }
        }

        return $template;
    }

}
