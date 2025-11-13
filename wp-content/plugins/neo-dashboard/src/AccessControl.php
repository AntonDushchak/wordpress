<?php
declare(strict_types=1);
namespace NeoDashboard\Core;
class AccessControl
{
    public static function init(): void
    {
        add_action('template_redirect', [self::class, 'enforceAuthentication'], 1);
        add_action('init', [self::class, 'checkAuthentication'], 1);
        add_filter('login_redirect', [self::class, 'loginRedirect'], 10, 3);
        add_action('admin_init', [self::class, 'restrictAdminAccess']);
        add_action('after_setup_theme', [self::class, 'hideAdminBar']);
        add_action('admin_init', [self::class, 'blockAdminAreaForNeoRoles']);
    }

    public static function enforceAuthentication(): void
    {
        if (self::isLoginPage() || 
            self::isRegisterPage() || 
            self::isAjaxRequest() || 
            self::isAdminPage()) {
            return;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        $current_uri = $_SERVER['REQUEST_URI'] ?? '';
        $allowed_paths = [
            '/wp-cron.php',
            '/wp-includes/',
            '/wp-content/uploads/',
            '/wp-content/themes/',
            '/wp-content/plugins/',
            '/xmlrpc.php',
            '/robots.txt',
            '/favicon.ico'
        ];
        foreach ($allowed_paths as $allowed_path) {
            if (strpos($current_uri, $allowed_path) !== false) {
                return;
            }
        }
        if (!is_user_logged_in()) {
            $redirect_url = home_url($current_uri);
            $login_url = wp_login_url($redirect_url);
            wp_redirect($login_url);
            exit;
        }
        self::checkNeoPageAccess();
    }

    public static function checkAuthentication(): void
    {
        if ((defined('WP_CLI') && WP_CLI) || !did_action('wp_loaded')) {
            return;
        }
        if (self::isLoginPage() || self::isAjaxRequest() || self::isAdminPage()) {
            return;
        }
        if (!is_user_logged_in()) {
            self::redirectToLogin();
            return;
        }
        self::checkNeoPageAccess();
    }

    private static function checkNeoPageAccess(): void
    {
        global $wp;
        $current_path = '';
        if (isset($wp->request)) {
            $current_url = home_url($wp->request);
            $current_path = parse_url($current_url, PHP_URL_PATH) ?: '';
        }
        if (empty($current_path) && isset($_SERVER['REQUEST_URI'])) {
            $current_path = $_SERVER['REQUEST_URI'];
        }
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        if (in_array('administrator', $user_roles)) {
            return;
        }
        if (in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) {
            if (!empty($current_path) && !self::isNeoPage($current_path)) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    private static function isNeoPage(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        $path = trim($path, '/');
        return str_starts_with($path, 'neo-dashboard');
    }

    public static function loginRedirect($redirect_to, $request, $user): string
    {
        if (isset($user->errors)) {
            return $redirect_to;
        }
        $user_roles = $user->roles ?? [];
        if (in_array('administrator', $user_roles)) {
            return admin_url();
        }
        if (in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) {
            return home_url('/neo-dashboard');
        }
        return home_url();
    }

    public static function restrictAdminAccess(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            if (!wp_doing_ajax() && !self::isAllowedAdminPage()) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    private static function isAllowedAdminPage(): bool
    {
        global $pagenow;
        $allowed_pages = [
            'admin-ajax.php',
            'admin-post.php',
        ];
        return in_array($pagenow, $allowed_pages);
    }

    public static function hideAdminBar(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            show_admin_bar(false);
        }
    }

    public static function blockAdminAreaForNeoRoles(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            if (!wp_doing_ajax() && !self::isAllowedAdminPage()) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    private static function isLoginPage(): bool
    {
        global $pagenow;
        $login_pages = [
            'wp-login.php',
            'wp-register.php',
        ];
        return in_array($pagenow, $login_pages) || 
               (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false);
    }

    private static function isAjaxRequest(): bool
    {
        return wp_doing_ajax() || 
               (defined('DOING_AJAX') && DOING_AJAX) ||
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    private static function isRegisterPage(): bool
    {
        global $pagenow;
        return $pagenow === 'wp-register.php' || 
               (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-register.php') !== false);
    }

    private static function isAdminPage(): bool
    {
        return is_admin() || 
               (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false);
    }

    private static function redirectToLogin(): void
    {
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $login_url = wp_login_url($current_url);
        wp_redirect($login_url);
        exit;
    }

    public static function addNeoCapabilities(): void
    {
        $neo_editor = get_role('neo_editor');
        if ($neo_editor) {
            $neo_editor->add_cap('read');
            $neo_editor->add_cap('neo_dashboard_access');
        }
        $neo_mitarbeiter = get_role('neo_mitarbeiter');
        if ($neo_mitarbeiter) {
            $neo_mitarbeiter->add_cap('read');
            $neo_mitarbeiter->add_cap('neo_dashboard_access');
        }
    }

    public static function canAccessNeoDashboard(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        return in_array('administrator', $user_roles) || 
               in_array('neo_editor', $user_roles) || 
               in_array('neo_mitarbeiter', $user_roles);
    }

    private static function logUnauthorizedAccess(string $attempted_url): void
    {
        $user = wp_get_current_user();
        $user_info = $user->ID ? "User ID: {$user->ID}, Role: " . implode(', ', $user->roles) : 'Not logged in';
        error_log("Neo Dashboard - Unauthorized access attempt: URL: {$attempted_url}, User: {$user_info}");
    }

    public static function addAccessDeniedNotice(): void
    {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Zugang verweigert:</strong> Sie haben keine Berechtigung für diese Seite.</p>';
            echo '</div>';
        });
    }

    public static function showAccessDeniedPage(): void
    {
        status_header(403);
        $template_path = NEO_DASHBOARD_TEMPLATE_PATH . 'access-denied.php';
        if (file_exists($template_path)) {
            include $template_path;
            exit;
        }
        wp_die(
            '<h1>Zugang verweigert</h1>
             <p>Sie haben keine Berechtigung, diese Seite aufzurufen.</p>
             <p><a href="' . home_url('/neo-dashboard') . '">Zurück zum Dashboard</a></p>',
            'Zugang verweigert',
            ['response' => 403]
        );
    }

    public static function getCurrentUserInfo(): array
    {
        if (!is_user_logged_in()) {
            return [
                'logged_in' => false,
                'user_id' => 0,
                'roles' => [],
                'display_name' => 'Гость'
            ];
        }
        $user = wp_get_current_user();
        return [
            'logged_in' => true,
            'user_id' => $user->ID,
            'roles' => $user->roles ?? [],
            'display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email
        ];
    }
}
