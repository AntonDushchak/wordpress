<?php
/**
 * Интеграция адаптивных стилей в WordPress
 * Добавьте этот код в functions.php вашей активной темы
 * 
 * @package WordPress
 * @subpackage Responsive
 */

// Подключаем файл с адаптивными функциями
require_once get_template_directory() . '/responsive-functions.php';

/**
 * Настройка темы для адаптивности
 */
function setup_responsive_theme() {
    // Поддержка HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Поддержка пользовательского логотипа
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    
    // Поддержка миниатюр записей
    add_theme_support('post-thumbnails');
    
    // Поддержка адаптивных встроенных элементов
    add_theme_support('responsive-embeds');
    
    // Поддержка широких блоков Gutenberg
    add_theme_support('align-wide');
    
    // Регистрация меню
    register_nav_menus(array(
        'primary' => 'Основное меню',
        'mobile'  => 'Мобильное меню',
    ));
    
    // Регистрация областей виджетов
    register_sidebar(array(
        'name'          => 'Основная боковая панель',
        'id'            => 'sidebar-1',
        'description'   => 'Основная область виджетов',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => 'Подвал',
        'id'            => 'footer-1',
        'description'   => 'Область виджетов в подвале',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('after_setup_theme', 'setup_responsive_theme');

/**
 * Добавление классов для адаптивности в body
 */
function add_responsive_body_classes($classes) {
    // Определяем устройство
    if (wp_is_mobile()) {
        $classes[] = 'is-mobile';
    } else {
        $classes[] = 'is-desktop';
    }
    
    // Добавляем класс для админ-панели
    if (is_admin_bar_showing()) {
        $classes[] = 'has-admin-bar';
    }
    
    return $classes;
}
add_filter('body_class', 'add_responsive_body_classes');

/**
 * Оптимизация контента для мобильных устройств
 */
function optimize_content_for_mobile($content) {
    if (wp_is_mobile()) {
        // Добавляем класс table-responsive ко всем таблицам
        $content = preg_replace('/<table(.*?)>/', '<div class="table-responsive"><table$1>', $content);
        $content = str_replace('</table>', '</table></div>', $content);
        
        // Добавляем loading="lazy" к изображениям
        $content = preg_replace('/<img(.*?)>/', '<img$1 loading="lazy">', $content);
    }
    
    return $content;
}
add_filter('the_content', 'optimize_content_for_mobile');

/**
 * Настройки для улучшения производительности на мобильных
 */
function mobile_performance_optimizations() {
    if (wp_is_mobile()) {
        // Отключаем эмодзи для экономии ресурсов
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        
        // Отключаем XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Отключаем REST API для гостей (опционально)
        add_filter('rest_authentication_errors', function($result) {
            if (!empty($result)) {
                return $result;
            }
            
            if (!is_user_logged_in()) {
                return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array('status' => 401));
            }
            
            return $result;
        });
    }
}
add_action('init', 'mobile_performance_optimizations');

/**
 * Пример адаптивного шорткода для изображений
 */
function responsive_image_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'alt' => '',
        'class' => '',
    ), $atts);
    
    return get_responsive_image($atts['id'], $atts['alt'], $atts['class']);
}
add_shortcode('responsive_img', 'responsive_image_shortcode');

/**
 * Добавление structured data для лучшего SEO на мобильных
 */
function add_mobile_structured_data() {
    if (is_single() || is_page()) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => get_the_title(),
            'description' => get_the_excerpt(),
            'url' => get_permalink(),
            'mainEntity' => array(
                '@type' => 'Article',
                'headline' => get_the_title(),
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
                'author' => array(
                    '@type' => 'Person',
                    'name' => get_the_author()
                )
            )
        );
        
        echo '<script type="application/ld+json">' . json_encode($schema) . '</script>';
    }
}
add_action('wp_head', 'add_mobile_structured_data');
?>