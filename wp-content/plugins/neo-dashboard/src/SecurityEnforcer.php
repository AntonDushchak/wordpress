<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

class SecurityEnforcer
{
    public static function init(): void
    {
        add_action('init', [self::class, 'blockDirectFileAccess']);
        
        add_action('wp_login_failed', [self::class, 'logFailedLogin']);
        
        add_action('admin_init', [self::class, 'enforceAdminSecurity'], 1);
        
        add_action('parse_request', [self::class, 'blockRestrictedUrls']);
        
        add_action('wp_loaded', [self::class, 'ensureHtaccessProtection'], 1);
    }

    public static function blockDirectFileAccess(): void
    {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/wp-content/plugins/neo-dashboard/') !== false) {
            if (strpos($_SERVER['REQUEST_URI'], '.php') !== false) {
                wp_die('Direkter Zugriff nicht erlaubt', 'Zugang verweigert', ['response' => 403]);
            }
        }
    }

    public static function logFailedLogin(string $username): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        error_log("Neo Dashboard - Failed login attempt: Username: {$username}, IP: {$ip}");
    }

    public static function enforceAdminSecurity(): void
    {
        global $pagenow;
        
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

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

    public static function blockRestrictedUrls($wp): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = $user->roles ?? [];

        if ((in_array('neo_editor', $user_roles) || in_array('neo_mitarbeiter', $user_roles)) 
            && !in_array('administrator', $user_roles)) {
            
            $current_path = trim($_SERVER['REQUEST_URI'] ?? '', '/');
            
            $blocked_paths = [
                'wp-admin',
                'wp-login.php?action=logout',
                'xmlrpc.php',
                'readme.html',
                'license.txt',
            ];

            foreach ($blocked_paths as $blocked_path) {
                if ($blocked_path !== 'wp-login.php?action=logout' && 
                    strpos($current_path, $blocked_path) === 0) {
                    wp_redirect(home_url('/neo-dashboard'));
                    exit;
                }
            }
        }
    }

    public static function ensureHtaccessProtection(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        
        if (file_exists($htaccess_path)) {
            $current_content = file_get_contents($htaccess_path);
            if (strpos($current_content, 'Neo Dashboard Security Rules') === false) {
                self::createHtaccessProtection();
            }
        } else {
            self::createHtaccessProtection();
        }
    }

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

        if (file_exists($htaccess_path)) {
            $current_content = file_get_contents($htaccess_path);
            if (strpos($current_content, 'Neo Dashboard Security Rules') === false) {
                file_put_contents($htaccess_path, $protection_rules . "\n" . $current_content);
            }
        }
    }

    public static function removeHtaccessProtection(): void
    {
        $htaccess_path = ABSPATH . '.htaccess';
        
        if (file_exists($htaccess_path)) {
            $content = file_get_contents($htaccess_path);
            
            $pattern = '/# Neo Dashboard Security Rules.*?(?=\n# |$)/s';
            $content = preg_replace($pattern, '', $content);
            
            file_put_contents($htaccess_path, $content);
        }
    }
}