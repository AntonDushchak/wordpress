<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use WP_User;
use NeoDashboard\Core\Logger;

/**
 * Prüft, ob der angegebene WP_User basierend auf einer Rollen‑Liste Zugriff hat.
 *
 * @param WP_User       $user  Aktueller User
 * @param string[]|null $roles Erlaubte Rollen oder null für alle
 * @return bool
 */
function user_has_access(WP_User $user, ?array $roles): bool
{
    if ( empty( $roles ) ) {
        return true;
    }
    foreach ( $roles as $role ) {
        if ( in_array( $role, $user->roles, true ) ) {
            return true;
        }
    }
    return false;
}

/**
 * Gibt die aktive Section anhand des Query-Var zurück.
 *
 * @param array<string,array> $sections Alle registrierten Sections
 * @param string              $slug     Gesuchter Section-Slug
 * @return array<string,mixed>|null
 */
function get_active_section(array $sections, string $slug): ?array {
    foreach ($sections as $section) {
        Logger::info('Check section', [
            'expected' => $slug,
            'candidate' => $section['slug'] ?? '(none)'
        ]);

        if (isset($section['slug']) && $section['slug'] === $slug) {
            Logger::info('Matched active section', ['slug' => $slug]);
            return $section;
        }
    }
    return null;
}


/**
 * Проверяет, может ли пользователь получить доступ к Neo Dashboard
 *
 * @return bool
 */
function can_access_neo_dashboard(): bool
{
    return AccessControl::canAccessNeoDashboard();
}

/**
 * Проверяет, является ли пользователь администратором
 *
 * @return bool
 */
function is_neo_admin(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user = wp_get_current_user();
    return in_array('administrator', $user->roles ?? []);
}

/**
 * Проверяет, имеет ли пользователь определенную Neo роль
 *
 * @param string $role Роль для проверки (neo_editor, neo_mitarbeiter)
 * @return bool
 */
function has_neo_role(string $role): bool
{
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user = wp_get_current_user();
    return in_array($role, $user->roles ?? []);
}

/**
 * Получает текущую роль пользователя (первую найденную Neo роль или admin)
 *
 * @return string
 */
function get_current_neo_role(): string
{
    if (!is_user_logged_in()) {
        return 'guest';
    }
    
    $user = wp_get_current_user();
    $roles = $user->roles ?? [];
    
    if (in_array('administrator', $roles)) {
        return 'administrator';
    }
    
    if (in_array('neo_editor', $roles)) {
        return 'neo_editor';
    }
    
    if (in_array('neo_mitarbeiter', $roles)) {
        return 'neo_mitarbeiter';
    }
    
    return 'unknown';
}

/**
 * Rendert das User-Menu (Avatar oder Login-Link).
 *
 * @param WP_User $user
 * @return string HTML-Markup für die Anzeige in der Navbar
 */
function render_nav_user_menu(WP_User $user): string
{
    if ( is_user_logged_in() ) {
        $avatar     = get_avatar( $user->ID, 24, '', $user->display_name, [ 'class' => 'rounded-circle me-2' ] );
        $logout_url = wp_logout_url( home_url( '/neo-dashboard/' ) );
        $current_role = get_current_neo_role();
        
        // Отображаем роль пользователя
        $role_display = '';
        switch($current_role) {
            case 'administrator':
                $role_display = '<small class="text-muted">Administrator</small>';
                break;
            case 'neo_editor':
                $role_display = '<small class="text-muted">Editor</small>';
                break;
            case 'neo_mitarbeiter':
                $role_display = '<small class="text-muted">Mitarbeiter</small>';
                break;
        }

        return sprintf(
            '<div class="dropdown text-end">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    %s
                    <div class="d-flex flex-column">
                        <span>%s</span>
                        %s
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="%s">Abmelden</a></li>
                </ul>
            </div>',
            $avatar,
            esc_html( $user->display_name ),
            $role_display,
            esc_url( $logout_url )
        );
    }

    $login_url = wp_login_url( home_url( '/neo-dashboard/' ) );
    return '<a class="btn btn-outline-light" href="' . esc_url( $login_url ) . '">Anmelden</a>';
}
