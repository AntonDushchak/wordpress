<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Logger;

class WidgetManager
{
    /**
     * Hook für das Registrieren von Widgets.
     */
    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_widget', [ $this, 'register' ]);
    }

    /**
     * Registriert ein neues Dashboard‑Widget in der zentralen Registry.
     *
     * @param array{
     *   id: string,
     *   title?: string,
     *   callback?: callable,
     *   priority?: int,
     *   roles?: array|null
     * } $args
     * @return string Der generierte Handle (Widget‑ID)
     */
    public function register(array $args): string
    {
        Logger::info('WidgetManager:register', [
            'args' => $args['id']
        ]);
        $id = sanitize_key((string) $args['id']);
        Registry::instance()->addWidget($id, $args);
        return $id;
    }

    /**
     * Gibt alle registrierten Widgets zurück, sortiert nach Priority.
     *
     * @return array<string, array>
     */
    public function getWidgets(): array
    {
        $widgets = Registry::instance()->getWidgets();
        uasort(
            $widgets,
            static fn($a, $b) => (int)($a['priority'] ?? 10) <=> (int)($b['priority'] ?? 10)
        );
        return $widgets;
    }
}
