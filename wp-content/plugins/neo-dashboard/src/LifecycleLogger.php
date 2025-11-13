<?php

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;

/**
 * Class LifecycleLogger
 *
 * Logs key WordPress lifecycle hooks for debugging and performance analysis.
 */
class LifecycleLogger {
    /**
     * @var string[] List of core hooks to track
     */
    protected array $hooks = [
        'muplugins_loaded',
        'plugins_loaded',
        'after_setup_theme',
        'init',
        'wp_loaded',
        'template_redirect',
        'template_include',
        'wp_footer',
        'shutdown',
    ];

    public function __construct() {
        // Register specific lifecycle hooks
        foreach ($this->hooks as $hook) {
            add_action($hook, function() use ($hook) {
                Logger::info('WP Lifecycle Hook fired', [
                    'hook'      => $hook,
                    'timestamp' => microtime(true),
                ]);
            }, 0);
        }

        // Optional: log every fired hook (comment out in production)
        add_filter('all', [$this, 'logAllHooks'], 0, 2);
    }

    /**
     * Logs every hook or filter fired. Use with caution: very verbose.
     *
     * @param mixed  $value     Value passed through the hook or filter.
     * @param string $hook_name Name of the hook being fired.
     * @return mixed
     */
        /**
     * Logs every hook or filter fired. Use with caution: very verbose.
     *
     * @param mixed       $value     Value passed through the hook or filter.
     * @param string|null $hook_name Name of the hook being fired, or null.
     * @return mixed
     */
    public function logAllHooks() {
        // Retrieve all arguments passed to this 'all' hook
        $args = func_get_args();
        $hook_name = $args[0] ?? null;

        // Only log once per hook firing and if we have a valid hook name
        if ( is_string( $hook_name ) && did_action( $hook_name ) === 1 ) {
            Logger::info( 'WP All-Hook fired', [
                'hook'      => $hook_name,
                'timestamp' => microtime( true ),
            ] );
        }

        // Return the original value (first argument) to not interfere with filters
        return $args[0] ?? null;
    }

}