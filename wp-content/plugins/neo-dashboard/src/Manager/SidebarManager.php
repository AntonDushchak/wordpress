<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Logger;

class SidebarManager
{
    /**
     * Registriert das Default‑Item „Start“ und hookt das Register‑Event.
     */
    public function registerDefault(): void
    {
        // Default‑Eintrag
        $this->register([
            'slug'     => 'home',
            'label'    => __( 'Start', 'neo-dashboard' ),
            'icon'     => 'bi-house',
            'url'      => '/neo-dashboard/',
            'position' => 0,
            'roles'    => null,
            'is_group' => false,
        ]);

        // Hook für weitere Sidebar‑Items
        add_action('neo_dashboard_register_sidebar_item', [ $this, 'register' ]);
    }

    /**
     * Registriert einen neuen Sidebar‑Eintrag in der zentralen Registry.
     *
     * @param array{
     *   slug: string,
     *   label: string,
     *   icon: string,
     *   url: string,
     *   position?: int,
     *   roles?: array|null,
     *   parent?: string,
     *   is_group?: bool,
     * } $args
     * @return string Der generierte Handle (Slug)
     */
    public function register(array $args): string
    {
        $slug = trim((string) $args['slug']);

        Logger::info('SidebarManager:register', [
            'args' => $args['slug']
        ]);

        // Initialisiere neue Felder mit Default-Werten
        $args['is_group'] = $args['is_group'] ?? false;
        if (isset($args['parent'])) {
            $args['parent'] = sanitize_key((string) $args['parent']);
        }

        Registry::instance()->addSidebarItem($slug, $args);
        return $slug;
    }

    /**
     * Liefert alle Sidebar‑Items, sortiert nach Position und hierarchisch gruppiert.
     *
     * Items mit 'parent' werden als 'children' unter ihrem Parent-Item einsortiert.
     * Parent-Items müssen zuvor mit 'is_group' => true registriert werden.
     *
     * @return array<string, mixed>
     */
    public function getItems(): array
    {
        $items = Registry::instance()->getSidebarItems();

        // Sortiere flache Liste nach Position
        uasort(
            $items,
            static fn($a, $b) => (int)($a['position'] ?? 10) <=> (int)($b['position'] ?? 10)
        );

        $grouped = [];
        foreach ($items as $slug => $item) {
            // Gruppe oder eigenständiges Item?
            if (!empty($item['parent'])) {
                $parent = $item['parent'];
                // Stelle sicher, dass die Gruppe existiert und als Gruppe markiert ist
                if (
                    !isset($grouped[$parent]) &&
                    isset($items[$parent]) &&
                    (!empty($items[$parent]['is_group']))
                ) {
                    $grouped[$parent] = $items[$parent];
                }
                // Füge als Kind zum Parent hinzu
                $grouped[$parent]['children'][$slug] = $item;
            } else {
                // Kein Parent → eigenständiges Item
                $grouped[$slug] = $item;
            }
        }

        return $grouped;
    }
}
