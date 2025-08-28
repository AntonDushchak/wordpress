<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

/**
 * Registry for storing dashboard components: sidebar items, widgets, notifications, sections.
 */
class Registry
{
    /** @var array<string, array> */
    private array $sidebarItems = [];
    /** @var array<string, array> */
    private array $widgets = [];
    /** @var array<string, array> */
    private array $notifications = [];
    /** @var array<string, array> */
    private array $sections = [];
    /** @var array<string, array> */
    private array $ajaxRoutes = [];

    // Singleton instance
    private static ?self $instance = null;

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    // Sidebar
    public function addSidebarItem(string $slug, array $args): void
    {
        $this->sidebarItems[$slug] = $args;
    }

    public function getSidebarItems(): array
    {
        return $this->sidebarItems;
    }

    /**
     * Liefert die Sidebar-Items hierarchisch gruppiert.
     * Parent-Items müssen mit 'is_group' => true registriert sein.
     *
     * @return array<string, array>
     */
    public function getSidebarTree(): array
    {
        $items = $this->sidebarItems;
        // Sortiere nach Position
        uasort(
            $items,
            static fn($a, $b) => (int)($a['position'] ?? 10) <=> (int)($b['position'] ?? 10)
        );

        $tree = [];
        foreach ($items as $slug => $item) {
            if (!empty($item['parent'])) {
                $parent = (string) $item['parent'];
                // Falls Parent-Gruppe noch nicht im Baum, hinzufügen
                if (
                    !isset($tree[$parent]) &&
                    isset($items[$parent]) &&
                    !empty($items[$parent]['is_group'])
                ) {
                    $tree[$parent] = $items[$parent];
                }
                // Kind-Element einfügen
                $tree[$parent]['children'][$slug] = $item;
            } else {
                // Eigenständiges Item ohne Parent
                $tree[$slug] = $item;
            }
        }

        return $tree;
    }

    // Widgets
    public function addWidget(string $slug, array $args): void
    {
        $this->widgets[$slug] = $args;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    // Notifications
    public function addNotification(string $slug, array $args): void
    {
        $this->notifications[$slug] = $args;
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    // Sections
    public function addSection(string $slug, array $args): void
    {
        $this->sections[$slug] = $args;
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    // Ajax Routen
    public function addAjaxRoute(string $slug, array $args): void
    {
        $this->ajaxRoutes[$slug] = $args;
    }

    public function getAjaxRoutes(): array
    {
        return $this->ajaxRoutes;
    }

}
