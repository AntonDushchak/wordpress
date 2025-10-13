<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

/**
 * Дополнительные меры безопасности для Neo Dashboard
 */
class SecurityEnforcer
{
    /**
     * Инициализация дополнительной защиты
     */
    public static function init(): void
    {
        // Защита от прямого доступа к файлам плагинов
        add_action('init', [self::class, 'blockDirectFileAccess']);
        
        // Логирование попыток несанкционированного доступа
        add_action('wp_login_failed', [self::class, 'logFailedLogin']);
        
        // Дополнительная защита админки
        add_action('admin_init', [self::class, 'enforceAdminSecurity'], 1);
        
        // Блокировка определенных URL для Neo ролей
        add_action('parse_request', [self::class, 'blockRestrictedUrls']);
        
        // Создание .htaccess защиты при инициализации
        add_action('wp_loaded', [self::class, 'ensureHtaccessProtection'], 1);
    }

    /**
     * Блокирует прямой доступ к файлам плагинов
     */
    public static function blockDirectFileAccess(): void
    {
        // Проверяем, не идет ли прямое обращение к PHP файлам плагина
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-content/plugins/neo-dashboard/') !== false) {
            if (strpos($_SERVER['REQUEST_URI'], '.php') !== false) {
                wp_die('Direkter Zugriff nicht erlaubt', 'Zugang verweigert', ['response' => 403]);
            }
        }
    }

    /**
     * Логирует неудачные попытки входа
     */
    public static function logFailedLogin(string $username): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        error_log("Neo Dashboard - Failed login attempt: Username: {$username}, IP: {$ip}");
    }

    /**
     * Применяет дополнительную защиту админки
     */
    public static function enforceAdminSecurity(): void
    {
        global $pagenow;
        
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        // Блокируем доступ к критичным страницам для Neo ролей
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            
            $blocked_pages = [
                'users.php',
                'user-new.php',
                'user-edit.php',
                'tools.php',
                'options-general.php',
                'options-writing.php',
                'options-reading.php',
                'options-discussion.php',
                'options-media.php',
                'options-permalink.php',
                'plugins.php',
                'plugin-install.php',
                'plugin-editor.php',
                'themes.php',
                'theme-install.php',
                'theme-editor.php',
                'update-core.php',
            ];

            if (in_array($pagenow, $blocked_pages)) {
                wp_redirect(home_url('/neo-dashboard'));
                exit;
            }
        }
    }

    /**
     * Блокирует доступ к определенным URL для Neo ролей
     */
    public static function blockRestrictedUrls($wp): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        // Только для Neo ролей (не администраторы)
        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            
            $current_path = trim($_SERVER['REQUEST_URI'] ?? '', '/');
            
            // Блокируем доступ к стандартным WordPress страницам
            $blocked_paths = [
                'wp-admin',
                'wp-login.php?action=logout', // разрешаем выход
                'xmlrpc.php',
                'readme.html',
                'license.txt',
            ];

            // Проверяем, не пытается ли пользователь получить доступ к заблокированным путям
            foreach ($blocked_paths as $blocked_path) {
                if ($blocked_path !== 'wp-login.php?action=logout' && 
                    strpos($current_path, $blocked_path) === 0) {
                    // Перенаправляем на dashboard вместо блокировки
                    wp_redirect(home_url('/neo-dashboard'));
                    exit;
                }
            }
        }
    }

    /**
     * Проверяет и создает .htaccess защиту если необходимо
     */
    public static function ensureHtaccessProtection(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        
        // Проверяем только если файл не содержит наших правил
        if (file_exists($htaccess_path)) {
            $current_content = file_get_contents($htaccess_path);
            if (strpos($current_content, 'Neo Dashboard Security Rules') === false) {
                self::createHtaccessProtection();
            }
        } else {
            // Создаем файл если его нет
            self::createHtaccessProtection();
        }
    }

    /**
     * Создает файл .htaccess для дополнительной защиты
     */
    public static function createHtaccessProtection(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        $protection_rules = "
# Neo Dashboard Security Rules
<Files wp-config.php>
    Order allow,deny
    Deny from all
</Files>

<Files readme.html>
    Order allow,deny
    Deny from all
</Files>

<Files license.txt>
    Order allow,deny
    Deny from all
</Files>

# Protect plugin files
<FilesMatch \"^(.*\.php)$\">
    <If \"%{REQUEST_URI} =~ m#/wp-content/plugins/neo-dashboard/.*\.php$#\">
        Order allow,deny
        Deny from all
    </If>
</FilesMatch>
";

        // Добавляем правила только если их еще нет
        if (file_exists($htaccess_path)) {
            $current_content = file_get_contents($htaccess_path);
            if (strpos($current_content, 'Neo Dashboard Security Rules') === false) {
                file_put_contents($htaccess_path, $protection_rules . "\n" . $current_content);
            }
        }
    }

    /**
     * Удаляет правила защиты из .htaccess
     */
    public static function removeHtaccessProtection(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        
        if (file_exists($htaccess_path)) {
            $content = file_get_contents($htaccess_path);
            
            // Удаляем секцию Neo Dashboard Security Rules
            $pattern = '/# Neo Dashboard Security Rules.*?(?=\n# |$)/s';
            $content = preg_replace($pattern, '', $content);
            
            file_put_contents($htaccess_path, $content);
        }
    }
}