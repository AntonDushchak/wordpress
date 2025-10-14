<?php
/**
 * Integration responsiver Stile in WordPress
 * Fügen Sie diesen Code in die functions.php Ihres aktiven Themes hinzu
 * 
 * @package WordPress
 * @subpackage Responsive
 */

// Datei mit responsiven Funktionen einbinden
if (file_exists(get_template_directory() . '/../responsive-functions.php')) {
    require_once get_template_directory() . '/../responsive-functions.php';
} elseif (file_exists(WP_CONTENT_DIR . '/themes/responsive-functions.php')) {
    require_once WP_CONTENT_DIR . '/themes/responsive-functions.php';
}

/**
 * Theme-Einrichtung für Responsivität
 */
function setup_responsive_theme() {
    // HTML5 Unterstützung
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Unterstützung für benutzerdefiniertes Logo
    add_theme_support('custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    
    // Unterstützung für Beitragsminiaturen
    add_theme_support('post-thumbnails');
    
    // Unterstützung für responsive eingebettete Elemente
    add_theme_support('responsive-embeds');
    
    // Unterstützung für breite Gutenberg-Blöcke
    add_theme_support('align-wide');
    
    // Menü-Registrierung
    register_nav_menus(array(
        'primary' => 'Hauptmenü',
        'mobile'  => 'Mobiles Menü',
    ));
    
    // Registrierung der Widget-Bereiche
    register_sidebar(array(
        'name'          => 'Haupt-Seitenleiste',
        'id'            => 'sidebar-1',
        'description'   => 'Haupt-Widget-Bereich',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    register_sidebar(array(
        'name'          => 'Fußzeile',
        'id'            => 'footer-1',
        'description'   => 'Widget-Bereich in der Fußzeile',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('after_setup_theme', 'setup_responsive_theme');

/**
 * Hinzufügung von Klassen für Responsivität im body
 */
function add_responsive_body_classes($classes) {
    // Gerät bestimmen
    if (wp_is_mobile()) {
        $classes[] = 'is-mobile';
    } else {
        $classes[] = 'is-desktop';
    }
    
    // Klasse für Admin-Panel hinzufügen
    if (is_admin_bar_showing()) {
        $classes[] = 'has-admin-bar';
    }
    
    return $classes;
}
add_filter('body_class', 'add_responsive_body_classes');

/**
 * Optimierung des Inhalts für mobile Geräte (bedingt)
 */
function optimize_content_for_mobile($content) {
    if (wp_is_mobile() && !empty($content)) {
        // table-responsive Klasse zu allen Tabellen hinzufügen nur wenn Tabellen vorhanden sind
        if (strpos($content, '<table') !== false) {
            $content = preg_replace('/<table(.*?)>/', '<div class="table-responsive"><table$1>', $content);
            $content = str_replace('</table>', '</table></div>', $content);
        }
        
        // loading="lazy" zu Bildern hinzufügen nur wenn kein loading-Attribut vorhanden ist
        if (strpos($content, '<img') !== false) {
            $content = preg_replace('/<img(?![^>]*loading=)(.*?)>/', '<img$1 loading="lazy">', $content);
        }
    }
    
    return $content;
}
add_filter('the_content', 'optimize_content_for_mobile');

/**
 * Einstellungen zur Leistungsverbesserung auf Mobilen (bedingt)
 */
function mobile_performance_optimizations() {
    if (wp_is_mobile()) {
        // Emojis zur Ressourceneinsparung deaktivieren (außer auf Seiten mit Kommentaren)
        if (!is_single() && !comments_open()) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
        }
        
        // XML-RPC nur deaktivieren wenn nicht für mobile Anwendungen verwendet
        if (!defined('XMLRPC_REQUEST')) {
            add_filter('xmlrpc_enabled', '__return_false');
        }
        
        // REST API für Gäste nur auf statischen Seiten deaktivieren
        if (is_page() && !function_exists('is_neo_dashboard_page')) {
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
}
add_action('init', 'mobile_performance_optimizations');

/**
 * Beispiel für responsive Bilder-Shortcode
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
 * Hinzufügung strukturierter Daten für besseres SEO auf Mobilen
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