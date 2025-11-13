<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class RestManager
 *
 * Zentralisiert die Registrierung und das Handling von REST-API-Endpunkten für NeoDashboard.
 */
class RestManager {
    /**
     * Namespace und Versionierung der API
     * @var string
     */
    protected string $namespace = 'neo-dashboard/v1';

    /**
     * Gesammelte Routen, registriert via registerRoute()
     * @var array<string, array>
     */
    protected array $routes = [];

    /**
     * Initialisierung: Hängt an rest_api_init
     */
    public function __construct() {
        // Priority 9, damit Add-ons auf 10 noch Routen anmelden können
        add_action('rest_api_init', [$this, 'init'], 9);
    }

    /**
     * Fordert Add-ons auf, eigene Routen zu registrieren, und registriert alle gesammelten Routen.
     */
    public function init(): void {
        /**
         * Hook für Extensions
         * Plugins, die NeoDashboard nutzen, können hier eigene Routen anmelden:
         * add_action('neo_dashboard_register_rest_routes', fn(RestManager $rest) => ... );
         */
        do_action('neo_dashboard_register_rest_routes', $this);

        // Tatsächliche Registrierung bei WP
        $this->registerRoutes();
    }

    /**
     * Fügt eine neue Route hinzu, wird beim init-Hook gemappt.
     *
     * @param string                 $route        Pfad relativ zum Namespace, z.B. '/items/(?P<id>\d+)'
     * @param callable               $callback     Callback übernimmt WP_REST_Request und liefert Daten zurück
     * @param string|string[]        $methods      HTTP-Methoden (READABLE, CREATABLE, ALLMETHODS etc.)
     * @param array<string, array>   $args         Argument-Definitionen für Parameter-Validierung
     * @param string                 $capability   Capability für permission_callback (default: read)
     */
    public function registerRoute(
        string $route,
        callable $callback,
        string|array $methods = WP_REST_Server::CREATABLE,
        array $args = [],
        string $capability = 'read'
    ): void {
        // Umschließen der Callback-Logik mit Fehler-Handling
        $wrapped_callback = function(WP_REST_Request $request) use ($callback) {
            try {
                $result = call_user_func($callback, $request);
                return rest_ensure_response([
                    'success' => true,
                    'data'    => $result,
                ]);
            } catch (\Exception $e) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        };

        // Einheitliche Permission-Logik
        $permission_callback = function() use ($capability) {
            return current_user_can($capability);
        };

        $this->routes[$route] = [
            'methods'             => $methods,
            'callback'            => $wrapped_callback,
            'permission_callback' => $permission_callback,
            'args'                => $args,
        ];
    }

    /**
     * Registriert alle in \$routes gesammelten Endpunkte bei WordPress.
     */
    protected function registerRoutes(): void {
        foreach ($this->routes as $route => $options) {
            register_rest_route($this->namespace, $route, $options);
        }
    }
}


