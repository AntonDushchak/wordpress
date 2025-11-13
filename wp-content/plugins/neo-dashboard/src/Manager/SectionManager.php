<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Logger;

class SectionManager
{
    /**
     * Hook für das Registrieren von Sections.
     */
    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_section', [ $this, 'register' ]);
    }

    /**
     * Registriert eine neue Content‑Section in der Registry.
     *
     * @param array{
     *   slug: string,
     *   title?: string,
     *   callback?: callable|null,
     *   template_path?: string|null,
     *   roles?: array|null
     * } $args
     * @return string Der generierte Handle (Slug)
     */
    public function register(array $args): string
    {
        Logger::info('SectionManager:register', [
            'args' => $args['slug']
        ]);

        $slug = sanitize_key((string) $args['slug']);
        Registry::instance()->addSection($slug, $args);
        return $slug;
    }

    /**
     * Gibt alle registrierten Sections über die Registry zurück.
     *
     * @return array<string, array>
     */
    public function getSections(): array
    {
        return Registry::instance()->getSections();
    }
}
