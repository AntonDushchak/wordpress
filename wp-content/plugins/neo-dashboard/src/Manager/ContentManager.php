<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use WP_User;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Helper;

class ContentManager
{

    /**
     * Aufruf in Template dasboard_blanc.php
     * Registriert die Hooks für unser eigenständiges Blank‑Template:
     * - neo_dashboard_head   → CSS
     * - neo_dashboard_footer → JS
     * - show_admin_bar       → Admin‑Bar ausblenden auf Dashboard‑Seiten
     */
    public function registerDefault(): void
    {
        add_action( 'neo_dashboard_body_content',   [ $this, 'render' ] );
    }

    
    /**
     * Haupt-Renderer für das Dashboard. Gibt Sidebar, Sections, Notifications und Widgets aus.
     */
    public function render(): void
    {
        Logger::info('ContentManager:render start');

        $user = wp_get_current_user();
        $section = get_query_var(NEO_DASHBOARD_QUERY_VAR_SECTION, '');

        Logger::info('ContentManager:render params', [
            'user' => $user instanceof WP_User ? $user->user_login : '(anon)',
            'neo_section' => $section,
            'sections' => Registry::instance()->getSections()
        ]);

        // Sidebar
        $rawSidebar = Registry::instance()->getSidebarTree();
        $sidebar = array_filter($rawSidebar, static fn($i) => Helper::user_has_access($user, $i['roles'] ?? null));

        // Notifications
        $notifications = array_filter(
            Registry::instance()->getNotifications(),
            static fn($n) => Helper::user_has_access($user, $n['roles'] ?? null)
        );

        // Sections
        $sections = array_filter(
            Registry::instance()->getSections(),
            static fn($s) => Helper::user_has_access($user, $s['roles'] ?? null)
        );

        // Widgets
        $widgets = array_filter(
            Registry::instance()->getWidgets(),
            static fn($w) => Helper::user_has_access($user, $w['roles'] ?? null)
        );

        // Aktuelle Section (z.B. neo-worker-is/view-contacts)
        $current_section = $section;
        $active_section = Helper::get_active_section($sections, $current_section);

        Logger::info('ContentManager:render Template laden ...', [
            'template' => NEO_DASHBOARD_TEMPLATE_PATH . 'dashboard-layout.php',
            'active_section' => $active_section
        ]);

        ob_start();
        include NEO_DASHBOARD_TEMPLATE_PATH . 'dashboard-layout.php';
        echo ob_get_clean();
    }

}
