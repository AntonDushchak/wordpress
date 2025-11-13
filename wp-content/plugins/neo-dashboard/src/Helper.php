<?php

declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;
use WP_User;

/**
 * Dashboard-Router für Rewrite, Section-Routing und Rendering
 */
class Helper
{
    /**
     * Prüft, ob der angegebene WP_User basierend auf einer Rollen‑Liste Zugriff hat.
     *
     * @param WP_User       $user  Aktueller User
     * @param string[]|null $roles Erlaubte Rollen oder null für alle
     * @return bool
     */
    public static function user_has_access(WP_User $user, ?array $roles): bool
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
    public static function get_active_section(array $sections, string $slug): ?array {
        Logger::info('Start Check section', [
            'sections' => $sections,
            'slug' => $slug
        ]);
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
     * Rendert das User-Menu (Avatar oder Login-Link).
     *
     * @param WP_User $user
     * @return string HTML-Markup für die Anzeige in der Navbar
     */
    public static function render_nav_user_menu(WP_User $user): string
    {
        if ( is_user_logged_in() ) {
            $avatar     = get_avatar( $user->ID, 24, '', $user->display_name, [ 'class' => 'rounded-circle me-2' ] );
            $logout_url = wp_logout_url( home_url( '/neo-dashboard/' ) );

            return sprintf(
                '<div class="dropdown text-end">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">%s<span>%s</span></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="%s">Abmelden</a></li>
                    </ul>
                </div>',
                $avatar,
                esc_html( $user->display_name ),
                esc_url( $logout_url )
            );
        }

        $login_url = wp_login_url( home_url( '/neo-dashboard/' ) );
        return '<a class="btn btn-outline-light" href="' . esc_url( $login_url ) . '">Anmelden</a>';
    }

}