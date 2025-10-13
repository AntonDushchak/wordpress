<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

/**
 * Управление доступом и редиректами для Neo Dashboard
 * Контролирует авторизацию и ограничивает доступ по ролям
 */
class AccessControl
{
    /**
     * Инициализация хуков для контроля доступа
     */
    public static function init(): void
    {
        // ГЛАВНАЯ проверка - блокирует ВСЕ страницы для неавторизованных
        add_action('template_redirect', [self::class, 'enforceAuthentication'], 1);
        
        // Дополнительная проверка через init
        add_action('init', [self::class, 'checkAuthentication'], 1);
        
        // Редирект после логина
        add_filter('login_redirect', [self::class, 'loginRedirect'], 10, 3);
        
        // Ограничиваем доступ к админке для ролей Neo
        add_action('admin_init', [self::class, 'restrictAdminAccess']);
        
        // Скрываем админ-бар для ролей Neo (кроме администратора)
        add_action('after_setup_theme', [self::class, 'hideAdminBar']);
        
        // Блокируем доступ к wp-admin для Neo ролей
        add_action('admin_init', [self::class, 'blockAdminAreaForNeoRoles']);
    }

    /**
     * СТРОГАЯ проверка аутентификации - блокирует ВСЕ страницы
     */
    public static function enforceAuthentication(): void
    {
        // Не проверяем на системных страницах
        if (self::isLoginPage() || 
            self::isRegisterPage() || 
            self::isAjaxRequest() || 
            self::isAdminPage()) {
            return;
        }

        // Не проверяем для REST API запросов
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Исключения для системных файлов WordPress
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

        // КРИТИЧЕСКАЯ ПРОВЕРКА: Если пользователь НЕ авторизован - БЛОКИРУЕМ
        if (!is_user_logged_in()) {
            // Сохраняем текущий URL для возврата после логина  
            $redirect_url = home_url($current_uri);
            $login_url = wp_login_url($redirect_url);
            
            // Принудительный редирект
            wp_redirect($login_url);
            exit; // КРИТИЧЕСКИ ВАЖНО - останавливаем выполнение
        }

        // Если авторизован - проверяем ролевой доступ
        self::checkNeoPageAccess();
    }

    /**
     * Проверяет авторизацию пользователя и перенаправляет на логин
     */
    public static function checkAuthentication(): void
    {
        // Не проверяем в CLI или если WordPress еще не загружен
        if ((defined('WP_CLI') && WP_CLI) || !did_action('wp_loaded')) {
            return;
        }
        
        // Не проверяем на страницах логина и AJAX запросах
        if (self::isLoginPage() || self::isAjaxRequest() || self::isAdminPage()) {
            return;
        }

        // Если пользователь не авторизован - редирект на логин
        if (!is_user_logged_in()) {
            self::redirectToLogin();
            return;
        }

        // Проверяем доступ к Neo Dashboard страницам
        self::checkNeoPageAccess();
    }

    /**
     * Проверяет доступ к страницам Neo Dashboard по ролям
     */
    private static function checkNeoPageAccess(): void
    {
        global $wp;
        
        // Получаем текущий путь безопасно
        $current_path = '';
        if (isset($wp->request)) {
            $current_url = home_url($wp->request);
            $current_path = parse_url($current_url, PHP_URL_PATH) ?: '';
        }
        
        // Если путь пустой, используем REQUEST_URI как запасной вариант
        if (empty($current_path) && isset($_SERVER['REQUEST_URI'])) {
            $current_path = $_SERVER['REQUEST_URI'];
        }
        
        // Получаем роль текущего пользователя
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];
        
        // Администратор имеет доступ ко всему
        if (in_array('administrator', $user_roles)) {
            return;
        }
        
        // Для ролей Neo_Editor и Neo_Mitarbeiter
        if (in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) {
            // Проверяем, находится ли пользователь на странице с корнем neo-dashboard
            if (!empty($current_path) && !self::isNeoPage($current_path)) {
                // Если не на Neo странице - редирект на dashboard
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    /**
     * Проверяет, является ли текущая страница Neo Dashboard страницей
     */
    private static function isNeoPage(?string $path): bool
    {
        // Если путь пустой или null, возвращаем false
        if (empty($path)) {
            return false;
        }
        
        // Нормализуем путь
        $path = trim($path, '/');
        
        // Проверяем, начинается ли путь с 'neo-dashboard'
        return str_starts_with($path, 'neo-dashboard');
    }

    /**
     * Редирект после успешного логина
     */
    public static function loginRedirect($redirect_to, $request, $user): string
    {
        // Если произошла ошибка при логине
        if (isset($user->errors)) {
            return $redirect_to;
        }

        // Получаем роли пользователя
        $user_roles = $user->roles ?? [];

        // Администратор идет в админку (по умолчанию)
        if (in_array('administrator', $user_roles)) {
            return admin_url();
        }

        // Neo роли идут на dashboard
        if (in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) {
            return home_url('/neo-dashboard');
        }

        // По умолчанию на главную
        return home_url();
    }

    /**
     * Ограничивает доступ к админке для Neo ролей
     */
    public static function restrictAdminAccess(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        // Если это Neo роль (но не администратор)
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            
            // Разрешаем только AJAX запросы и admin-post.php для форм
            if (!wp_doing_ajax() && !self::isAllowedAdminPage()) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    /**
     * Проверяет, разрешена ли текущая админ страница для Neo ролей
     */
    private static function isAllowedAdminPage(): bool
    {
        global $pagenow;
        
        $allowed_pages = [
            'admin-ajax.php',
            'admin-post.php',
        ];
        
        return in_array($pagenow, $allowed_pages);
    }

    /**
     * Скрывает админ-бар для Neo ролей
     */
    public static function hideAdminBar(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        // Скрываем админ-бар для Neo ролей (кроме администратора)
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            show_admin_bar(false);
        }
    }

    /**
     * Блокирует доступ к wp-admin для Neo ролей
     */
    public static function blockAdminAreaForNeoRoles(): void
    {
        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            
            // Разрешаем только необходимые AJAX запросы
            if (!wp_doing_ajax() && !self::isAllowedAdminPage()) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    /**
     * Проверяет, является ли текущая страница страницей логина
     */
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

    /**
     * Проверяет, является ли запрос AJAX запросом
     */
    private static function isAjaxRequest(): bool
    {
        return wp_doing_ajax() || 
               (defined('DOING_AJAX') && DOING_AJAX) ||
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Проверяет, является ли текущая страница страницей регистрации
     */
    private static function isRegisterPage(): bool
    {
        global $pagenow;
        
        return $pagenow === 'wp-register.php' || 
               (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-register.php') !== false);
    }

    /**
     * Проверяет, является ли текущая страница админ страницей
     */
    private static function isAdminPage(): bool
    {
        return is_admin() || 
               (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false);
    }

    /**
     * Перенаправляет неавторизованного пользователя на страницу логина
     */
    private static function redirectToLogin(): void
    {
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $login_url = wp_login_url($current_url);
        
        wp_redirect($login_url);
        exit;
    }

    /**
     * Добавляет дополнительные права для Neo ролей
     */
    public static function addNeoCapabilities(): void
    {
        // Neo Editor права
        $neo_editor = get_role('neo_editor');
        if ($neo_editor) {
            $neo_editor->add_cap('read');
            $neo_editor->add_cap('neo_dashboard_access');
        }

        // Neo Mitarbeiter права
        $neo_mitarbeiter = get_role('neo_mitarbeiter');
        if ($neo_mitarbeiter) {
            $neo_mitarbeiter->add_cap('read');
            $neo_mitarbeiter->add_cap('neo_dashboard_access');
        }
    }

    /**
     * Проверяет, имеет ли пользователь доступ к Neo Dashboard
     */
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

    /**
     * Логирует попытки несанкционированного доступа
     */
    private static function logUnauthorizedAccess(string $attempted_url): void
    {
        $user = wp_get_current_user();
        $user_info = $user->ID ? "User ID: {$user->ID}, Role: " . implode(', ', $user->roles) : 'Not logged in';
        
        error_log("Neo Dashboard - Unauthorized access attempt: URL: {$attempted_url}, User: {$user_info}");
    }

    /**
     * Добавляет уведомление о ограниченном доступе
     */
    public static function addAccessDeniedNotice(): void
    {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Zugang verweigert:</strong> Sie haben keine Berechtigung für diese Seite.</p>';
            echo '</div>';
        });
    }

    /**
     * Создает страницу "Доступ запрещен" для отображения вместо редиректа
     */
    public static function showAccessDeniedPage(): void
    {
        status_header(403);
        
        // Пытаемся загрузить наш кастомный шаблон
        $template_path = NEO_DASHBOARD_TEMPLATE_PATH . 'access-denied.php';
        
        if (file_exists($template_path)) {
            include $template_path;
            exit;
        }
        
        // Fallback на стандартную wp_die страницу
        wp_die(
            '<h1>Zugang verweigert</h1>
             <p>Sie haben keine Berechtigung, diese Seite aufzurufen.</p>
             <p><a href="' . home_url('/neo-dashboard') . '">Zurück zum Dashboard</a></p>',
            'Zugang verweigert',
            ['response' => 403]
        );
    }

    /**
     * Получает информацию о текущем пользователе для отладки
     */
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