<?php

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Logger;

final class AjaxManager
{
    /**
     * Registriert alle AJAX-Routen, die Plugins via Hook anmelden
     * Wird in Dashboard::run() automatisch ausgeführt
     */
    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_ajax', [ $this, 'register' ]);
    }

        /**
     * Registriert eine neue Ajax-Route in der Registry.
     *
     * @param array{
     *   slug: string,
     *   callback?: callable|null,
     *   roles?: array|null
     * } $args
     * @return string Der generierte Handle (Slug)
     */
    public function register(array $args): string
    {
        Logger::info('AjaxManager:register', [
            'args' => $args['slug']
        ]);

        $slug = sanitize_key((string) $args['slug']);
        Registry::instance()->addAjaxRoute($slug, $args);
        return $slug;
    }

        /**
     * Gibt alle registrierten AjaxRouten über die Registry zurück.
     *
     * @return array<string, array>
     */
    public function getAjaxRoutes(): array
    {
        return Registry::instance()->getAjaxRoutes();
    }
}
