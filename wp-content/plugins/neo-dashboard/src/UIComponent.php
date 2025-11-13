<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

class UIComponent {
    public static function render(string $component_path, array $args = []): string {
        $component_file = NEO_DASHBOARD_TEMPLATE_PATH . 'components/' . $component_path . '.php';
        
        if (!file_exists($component_file)) {
            error_log('UIComponent: Component not found - ' . $component_path);
            return '<!-- Component not found: ' . esc_html($component_path) . ' -->';
        }
        
        ob_start();
        include $component_file;
        return ob_get_clean();
    }
    
    public static function output(string $component_path, array $args = []): void {
        echo self::render($component_path, $args);
    }
    
    public static function modal(array $args): string {
        return self::render('layout/modal', $args);
    }
    
    public static function card(array $args): string {
        return self::render('layout/card', $args);
    }
    
    public static function button(array $args): string {
        return self::render('buttons/button', $args);
    }
    
    public static function table(array $args): string {
        return self::render('data/table', $args);
    }
    
    public static function form(array $args): string {
        return self::render('forms/form', $args);
    }
}