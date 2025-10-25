<?php
declare(strict_types=1);

namespace NeoDashboard\Core\Manager;

use NeoDashboard\Core\Registry;
use NeoDashboard\Core\Logger;
use NeoDashboard\Core\Manager\RestManager; // NEW
use WP_REST_Server;                       // NEW
use WP_REST_Request;                      // NEW
use WP_User;                              // NEW

/**
 * Verwaltung der Dashboard-Benachrichtigungen (Banner, Toasts, Modals).
 *
 * Erweiterungen v3.1.0 – 20 May 2025:
 *  • Rollen-Targeting & Ablauf-Logik
 *  • Dismiss-Persistenz pro User
 *  • REST-Routen  GET /notifications  +  POST /notifications/{id}/dismiss
 */
class NotificationManager
{
    private const USER_META_KEY = 'neo_dismissed_notifications'; // NEW

    /**
     * Registriert Standard-Hooks.
     */
    public function registerDefault(): void
    {
        add_action('neo_dashboard_register_notification', [ $this, 'register' ]);

        // REST-Integration über zentralen RestManager
        add_action('neo_dashboard_register_rest_routes', [ $this, 'registerRestRoutes' ]); // NEW
    }

    /* --------------------------------------------------------------------- *
     * Daten-API
     * --------------------------------------------------------------------- */

    /**
     * Legt eine Notification in der Registry an.
     *
     * @param array{
     *   id: string,
     *   message?: string,
     *   type?: string,           // info|success|warning|error
     *   dismissible?: bool,
     *   priority?: int,
     *   roles?: array<string>|null,
     *   expires?: int|null       // Unix-Timestamp
     * } $args
     *
     * @return string Sanitized ID
     */
    public function register(array $args): string
    {
        $defaults = [
            'message'     => '',
            'type'        => 'info',
            'dismissible' => true,
            'priority'    => 10,
            'roles'       => null,
            'expires'     => null,
        ];
        $args = array_merge($defaults, $args);

        $id = sanitize_key((string) ($args['id'] ?? ''));
        if ($id === '') {
            Logger::warn('NotificationManager:register – missing ID', $args);
            return '';
        }

        $args['id'] = $id; // sicherstellen, dass die Registry denselben Key kennt

        Registry::instance()->addNotification($id, $args);
        Logger::info('NotificationManager:register', [ 'id' => $id ]);

        return $id;
    }

    /**
     * Gibt aktive, nicht dismissed-Notifications des aktuellen Nutzers zurück.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveNotifications(): array // NEW
    {
        $all         = Registry::instance()->getNotifications();
        $now         = time();
        $user        = wp_get_current_user();
        $dismissed   = get_user_meta($user->ID, self::USER_META_KEY, true);
        $dismissed   = is_array($dismissed) ? $dismissed : [];

        $filtered = array_filter(
            $all,
            static function (array $note) use ($user, $dismissed, $now): bool {

                // Ablauf-Datum
                if (!empty($note['expires']) && $now > (int) $note['expires']) {
                    return false;
                }

                // Rollen-Targeting
                if (!empty($note['roles']) && is_array($note['roles'])) {
                    if (empty(array_intersect($note['roles'], $user->roles))) {
                        return false;
                    }
                }

                // Bereits dismissed?
                return !in_array($note['id'], $dismissed, true);
            }
        );

        // Nach Priority sortieren
        uasort(
            $filtered,
            static fn($a, $b) => (int)($a['priority'] ?? 10) <=> (int)($b['priority'] ?? 10)
        );

        return array_values($filtered);
    }

    /**
     * Markiert eine Notification für den aktuellen User als dismissed.
     */
    public function dismissNotification(string $id, int $userId): void // NEW
    {
        $dismissed = get_user_meta($userId, self::USER_META_KEY, true);
        $dismissed = is_array($dismissed) ? $dismissed : [];

        if (!in_array($id, $dismissed, true)) {
            $dismissed[] = $id;
            update_user_meta($userId, self::USER_META_KEY, $dismissed);
            Logger::info('NotificationManager:dismiss', [ 'id' => $id, 'user' => $userId ]);
        }
    }

    /* --------------------------------------------------------------------- *
     * REST-Layer
     * --------------------------------------------------------------------- */

    /**
     * Meldet die Notification-Routen beim zentralen RestManager an.
     */
    public function registerRestRoutes(RestManager $rest): void // NEW
    {
        // GET /notifications
        $rest->registerRoute(
            '/notifications',
            fn (WP_REST_Request $req) => $this->getActiveNotifications(),
            WP_REST_Server::READABLE,
            [],
            'read'
        );

        // POST /notifications/{id}/dismiss
        $rest->registerRoute(
            '/notifications/(?P<id>[a-zA-Z0-9_-]+)/dismiss',
            function (WP_REST_Request $req) {
                $id = sanitize_key($req['id']);
                $this->dismissNotification($id, get_current_user_id());
                return [ 'dismissed' => $id ];
            },
            WP_REST_Server::CREATABLE,
            [
                'id' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
            'read'
        );
    }
}
