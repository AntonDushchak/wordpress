<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

/**
 * Rollenverwaltung für Neo Dashboard
 * Verwaltet die Erstellung und Entfernung von benutzerdefinierten Rollen
 */
class Roles
{
    /**
     * Erstellt alle benutzerdefinierten Rollen
     */
    public static function addRoles(): void
    {
        add_role(
            'neo_editor',
            'Neo Editor',
            [
                'read' => true,
            ]
        );

        add_role(
            'neo_mitarbeiter',
            'Neo Mitarbeiter',
            [
                'read' => true,
            ]
        );
    }

    /**
     * Entfernt alle benutzerdefinierten Rollen
     */
    public static function removeRoles(): void
    {
        remove_role('neo_admin');
        remove_role('neo_editor');
        remove_role('neo_mitarbeiter');
    }

    /**
     * Entfernt Standard-WordPress-Rollen
     */
    public static function removeDefaultRoles(): void
    {
        if (!function_exists('remove_role')) {
            return;
        }

        $roles_to_remove = [
            'subscriber',
            'contributor',
            'author',
            'editor',
            'vermittler',
        ];

        foreach ($roles_to_remove as $role) {
            remove_role($role);
        }
    }
}
