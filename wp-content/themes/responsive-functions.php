<?php
/**
 * Responsive Stile für WordPress
 * Einbindung von CSS-Dateien für alle Geräte
 * 
 * @package WordPress
 * @subpackage Responsive
 * @version 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

function enqueue_responsive_styles() {
    if (!is_plugin_active('neo-dashboard/neo-dashboard-core.php')) {
        $version = '1.2.0';
        wp_enqueue_style(
            'global-responsive',
            get_template_directory_uri() . '/global-responsive.css',
            array(),
            $version,
            'all'
        );
    }
}

/**
 * Hinzufügung von viewport meta tag für Responsivität
 */
function add_responsive_viewport() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
}

/**
 * Hinzufügung zusätzlicher meta tags zur Verbesserung der Responsivität
 */
function add_responsive_meta_tags() {
    // Viewport
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
    
    // Unterstützung für dunkles Design
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    
    // Deaktivierung der automatischen Erkennung von Telefonnummern auf iOS
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
    
    // Verbesserung des Renderings auf mobilen Geräten
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    
    // Vorladung kritischer Ressourcen
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/global-responsive.css" as="style">' . "\n";
}

/**
 * Prüfung ob spezifische Stile/Skripte benötigt werden
 */
function needs_plugin_assets($plugin_type) {
    global $post;
    
    switch ($plugin_type) {
        case 'calendar':
            return (is_page('calendar') || 
                   (isset($post->post_content) && has_shortcode($post->post_content, 'neo_calendar')) ||
                   strpos($_SERVER['REQUEST_URI'], 'calendar') !== false);
                   
        case 'dashboard':
            return (is_page('dashboard') || 
                   strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ||
                   current_user_can('manage_options'));
                   
        case 'umfrage':
            return (is_page('umfrage') || 
                   (isset($post->post_content) && has_shortcode($post->post_content, 'neo_umfrage')) ||
                   strpos($_SERVER['REQUEST_URI'], 'umfrage') !== false);
                   
        case 'jobs':
            return (is_page(array('jobs', 'career', 'vacancies')) || 
                   (isset($post->post_content) && has_shortcode($post->post_content, 'job_board')) ||
                   is_post_type_archive('job') || is_singular('job') ||
                   strpos($_SERVER['REQUEST_URI'], 'job') !== false);
    }
    
    return false;
}

/**
 * Hinzufügung von JavaScript für mobiles Menü (bedingt)
 */
function add_mobile_menu_script() {
    // Basis-Menü-Skript überall einbinden wo Navigation vorhanden ist
    if (has_nav_menu('primary') || has_nav_menu('mobile')) {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobiles Menü
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('show');
                this.classList.toggle('active');
            });
        }
        
        // Untermenü für Mobile
        const menuItemsWithChildren = document.querySelectorAll('.menu-item-has-children > a');
        
        menuItemsWithChildren.forEach(function(menuItem) {
            menuItem.addEventListener('click', function(e) {
                if (window.innerWidth <= 767) {
                    e.preventDefault();
                    const parentLi = this.parentNode;
                    const subMenu = parentLi.querySelector('.sub-menu');
                    
                    parentLi.classList.toggle('open');
                    
                    if (subMenu) {
                        subMenu.style.display = parentLi.classList.contains('open') ? 'block' : 'none';
                    }
                }
            });
        });
        
        // Handler für Fenstergrößenänderung
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                // Reset mobiler Stile auf größeren Bildschirmen
                const openItems = document.querySelectorAll('.menu-item-has-children.open');
                openItems.forEach(function(item) {
                    item.classList.remove('open');
                    const subMenu = item.querySelector('.sub-menu');
                    if (subMenu) {
                        subMenu.style.display = '';
                    }
                });
                
                if (navMenu) {
                    navMenu.classList.remove('show');
                }
                
                if (menuToggle) {
                    menuToggle.classList.remove('active');
                }
            }
        });
        
        // Behandlung von Touch-Events zur Verbesserung der UX auf Mobilen
        let touchStartY = 0;
        let touchEndY = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartY = e.changedTouches[0].screenY;
        });
        
        document.addEventListener('touchend', function(e) {
            touchEndY = e.changedTouches[0].screenY;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeThreshold = 100;
            const diff = touchStartY - touchEndY;
            
            // Kann Swipe-Behandlung zum Schließen des Menüs hinzufügen
            if (Math.abs(diff) > swipeThreshold) {
                // Logik für Swipes
            }
        }
    });
    </script>
    <?php
    } // Ende der Navigations-Prüfung
}

/**
 * Hinzufügung von CSS-Variablen für dynamische Stiländerungen
 */
function add_css_custom_properties() {
    ?>
    <style>
    :root {
        --primary-color: #007cba;
        --secondary-color: #f8f9fa;
        --text-color: #212529;
        --bg-color: #ffffff;
        --border-color: #dee2e6;
        --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --border-radius: 0.375rem;
        --transition: all 0.2s ease-in-out;
        
        /* Breakpoints */
        --breakpoint-xs: 480px;
        --breakpoint-sm: 576px;
        --breakpoint-md: 768px;
        --breakpoint-lg: 992px;
        --breakpoint-xl: 1200px;
        
        /* Abstände */
        --spacing-xs: 0.25rem;
        --spacing-sm: 0.5rem;
        --spacing-md: 1rem;
        --spacing-lg: 1.5rem;
        --spacing-xl: 3rem;
    }
    
    /* Dunkles Design */
    @media (prefers-color-scheme: dark) {
        :root {
            --text-color: #e0e0e0;
            --bg-color: #121212;
            --secondary-color: #1e1e1e;
            --border-color: #333333;
        }
    }
    
    /* Responsive Schriftgrößen */
    html {
        font-size: clamp(14px, 2.5vw, 16px);
    }
    
    /* Responsive Abstände */
    .container, 
    .container-fluid {
        padding-left: clamp(8px, 2vw, 15px);
        padding-right: clamp(8px, 2vw, 15px);
    }
    </style>
    <?php
}

/**
 * Funktion zur Prüfung mobiler Geräte
 */
function is_mobile_device() {
    return wp_is_mobile();
}

/**
 * Funktion zur Ermittlung der Bildschirmgröße über JavaScript
 */
function add_screen_size_detection() {
    ?>
    <script>
    // Bestimmung der Bildschirmgröße
    function getScreenSize() {
        const width = window.innerWidth;
        
        if (width <= 480) return 'xs';
        if (width <= 767) return 'sm';
        if (width <= 991) return 'md';
        if (width <= 1199) return 'lg';
        return 'xl';
    }
    
    // Hinzufügung der Bildschirmgrößen-Klasse zu body
    function updateScreenSizeClass() {
        const body = document.body;
        const currentSize = getScreenSize();
        
        // Alle Bildschirmgrößen-Klassen entfernen
        body.classList.remove('screen-xs', 'screen-sm', 'screen-md', 'screen-lg', 'screen-xl');
        
        // Aktuelle Klasse hinzufügen
        body.classList.add('screen-' + currentSize);
        
        // Klasse für mobile Geräte hinzufügen
        if (currentSize === 'xs' || currentSize === 'sm') {
            body.classList.add('is-mobile');
        } else {
            body.classList.remove('is-mobile');
        }
    }
    
    // Aktualisierung beim Laden und bei Größenänderung
    document.addEventListener('DOMContentLoaded', updateScreenSizeClass);
    window.addEventListener('resize', updateScreenSizeClass);
    </script>
    <?php
}

// WordPress Hooks
add_action('wp_enqueue_scripts', 'enqueue_responsive_styles');
add_action('wp_head', 'add_responsive_meta_tags', 1);
add_action('wp_footer', 'add_mobile_menu_script');
add_action('wp_head', 'add_css_custom_properties');
add_action('wp_footer', 'add_screen_size_detection');

/**
 * Hinzufügung des mobilen Menü-Buttons zur Navigation
 */
function add_mobile_menu_button($items, $args) {
    if ($args->theme_location == 'primary') {
        $mobile_button = '<li class="menu-item menu-toggle d-md-none">';
        $mobile_button .= '<a href="#" class="menu-toggle-link">☰ Menü</a>';
        $mobile_button .= '</li>';
        
        $items = $mobile_button . $items;
    }
    
    return $items;
}
add_filter('wp_nav_menu_items', 'add_mobile_menu_button', 10, 2);

/**
 * Hinzufügung der Unterstützung für responsive Bilder
 */
function add_responsive_image_sizes() {
    // Bildgrößen für verschiedene Bildschirme hinzufügen
    add_image_size('mobile-small', 480, 320, true);
    add_image_size('mobile-large', 767, 511, true);
    add_image_size('tablet', 1024, 683, true);
    add_image_size('desktop', 1200, 800, true);
}
add_action('after_setup_theme', 'add_responsive_image_sizes');

/**
 * Funktion zur Ausgabe responsiver Bilder
 */
function get_responsive_image($attachment_id, $alt = '', $class = '') {
    if (!$attachment_id) return '';
    
    $mobile_small = wp_get_attachment_image_src($attachment_id, 'mobile-small');
    $mobile_large = wp_get_attachment_image_src($attachment_id, 'mobile-large');
    $tablet = wp_get_attachment_image_src($attachment_id, 'tablet');
    $desktop = wp_get_attachment_image_src($attachment_id, 'desktop');
    $full = wp_get_attachment_image_src($attachment_id, 'full');
    
    $srcset = array();
    
    if ($mobile_small) $srcset[] = $mobile_small[0] . ' 480w';
    if ($mobile_large) $srcset[] = $mobile_large[0] . ' 767w';
    if ($tablet) $srcset[] = $tablet[0] . ' 1024w';
    if ($desktop) $srcset[] = $desktop[0] . ' 1200w';
    if ($full) $srcset[] = $full[0] . ' ' . $full[1] . 'w';
    
    $srcset_attr = implode(', ', $srcset);
    $sizes = '(max-width: 480px) 480px, (max-width: 767px) 767px, (max-width: 1024px) 1024px, 1200px';
    
    $img = '<img src="' . ($desktop ? $desktop[0] : $full[0]) . '"';
    $img .= ' srcset="' . $srcset_attr . '"';
    $img .= ' sizes="' . $sizes . '"';
    $img .= ' alt="' . esc_attr($alt) . '"';
    $img .= ' class="img-responsive ' . esc_attr($class) . '"';
    $img .= ' loading="lazy"';
    $img .= '>';
    
    return $img;
}

/**
 * Optimierung für Leistung (bedingt)
 */
function optimize_for_mobile() {
    if (is_mobile_device()) {
        // jQuery UI nur deaktivieren wenn Kalender oder Dashboard nicht verwendet wird
        if (!needs_plugin_assets('calendar') && !needs_plugin_assets('dashboard')) {
            wp_dequeue_script('jquery-ui-core');
            wp_dequeue_script('jquery-ui-widget');
            wp_dequeue_script('jquery-ui-datepicker');
        }
        
        // Überflüssige Skripte auf statischen Seiten deaktivieren
        if (is_page() && !needs_plugin_assets('jobs') && !needs_plugin_assets('umfrage')) {
            wp_dequeue_script('wp-embed');
            wp_dequeue_script('comment-reply');
        }
        
        // Kritisches CSS inline nur auf Startseite und statischen Seiten hinzufügen
        if (is_front_page() || is_page()) {
            add_action('wp_head', function() {
                echo '<style id="critical-css">';
                echo 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}';
                echo '.container{max-width:100%;padding:0 15px}';
                echo 'img{max-width:100%;height:auto}';
                echo '</style>';
            }, 1);
        }
    }
}
add_action('wp_enqueue_scripts', 'optimize_for_mobile', 100);


/**
 * Theme switcher styles and script for Neo Dashboard integration
 */
function add_neo_dashboard_theme_assets() {
    ?>
    <style>
    /* Theme variables */
    :root {
        --theme-bg-color: #ffffff;
        --theme-text-color: #212529;
        --theme-primary-color: #007cba;
        --theme-border-color: #dee2e6;
        --theme-secondary-bg: #f8f9fa;
    }
    
    [data-theme="dark"] {
        --theme-bg-color: #1a1a1a;
        --theme-text-color: #e0e0e0;
        --theme-primary-color: #4dabf7;
        --theme-border-color: #404040;
        --theme-secondary-bg: #2d2d2d;
    }
    
    /* Theme switcher button */
    .theme-switcher {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1060;
    }
    
    #theme-toggle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        transition: all 0.3s ease;
        background: var(--theme-secondary-bg);
        color: var(--theme-text-color);
        border: 1px solid var(--theme-border-color);
    }
    
    #theme-toggle:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    /* Apply theme to body and all elements */
    body {
        background-color: var(--theme-bg-color) !important;
        color: var(--theme-text-color) !important;
    }
    
    /* Override Bootstrap and WordPress default text colors */
    .navbar-brand,
    .nav-link,
    .text-white,
    .text-light,
    .navbar-nav .nav-link {
        color: var(--theme-text-color) !important;
    }
    
    .navbar,
    .navbar.navbar-light,
    .navbar.bg-light,
    .bg-light {
        background-color: var(--theme-secondary-bg) !important;
        border-bottom: 1px solid var(--theme-border-color) !important;
    }
    
    .navbar .navbar-brand,
    .navbar .navbar-nav .nav-link,
    .navbar.navbar-light .navbar-brand,
    .navbar.navbar-light .navbar-nav .nav-link {
        color: var(--theme-text-color) !important;
    }
    
    .navbar .navbar-nav .nav-link:hover,
    .navbar .navbar-nav .nav-link:focus,
    .navbar.navbar-light .navbar-nav .nav-link:hover,
    .navbar.navbar-light .navbar-nav .nav-link:focus {
        color: var(--theme-primary-color) !important;
    }
    
    [data-theme="dark"] .navbar.navbar-light,
    [data-theme="dark"] .navbar.bg-light,
    [data-theme="dark"] .bg-light {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text-color) !important;
    }
    
    [data-theme="dark"] .navbar.navbar-light .navbar-brand,
    [data-theme="dark"] .navbar.navbar-light .navbar-nav .nav-link {
        color: var(--theme-text-color) !important;
    }
    
    /* Fix Bootstrap button colors */
    .btn-primary {
        background-color: var(--theme-primary-color) !important;
        border-color: var(--theme-primary-color) !important;
    }
    
    .btn-outline-secondary {
        color: var(--theme-text-color) !important;
        border-color: var(--theme-border-color) !important;
    }
    
    .btn-outline-secondary:hover {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text-color) !important;
    }
    
    /* Ensure all text elements use theme colors */
    h1, h2, h3, h4, h5, h6, p, span, div, section, article {
        color: var(--theme-text-color) !important;
    }
    
    /* WordPress content areas */
    .wp-site-blocks,
    .wp-block-group,
    .entry-content,
    .site-content {
        background-color: var(--theme-bg-color) !important;
        color: var(--theme-text-color) !important;
    }
    
    /* Links */
    a {
        color: var(--theme-primary-color) !important;
    }
    
    a:hover, a:focus {
        color: color-mix(in srgb, var(--theme-primary-color) 80%, white 20%) !important;
    }
    
    /* Forms 
    input, textarea, select {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text-color) !important;
        border-color: var(--theme-border-color) !important;
    }*/
    
    /* Responsive design for theme switcher */
    @media (max-width: 768px) {
        .theme-switcher {
            top: 15px;
            right: 15px;
        }
        
        #theme-toggle {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
    }
    
    @media (max-width: 480px) {
        .theme-switcher {
            top: 10px;
            right: 10px;
        }
        
        #theme-toggle {
            width: 35px;
            height: 35px;
            font-size: 14px;
        }
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-theme') || 'light';
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
                
                // Button animation
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        }
        
        function updateThemeIcon(theme) {
            if (themeToggle) {
                if (theme === 'light') {
                    themeToggle.innerHTML = '<i class="bi bi-moon-fill"></i>';
                    themeToggle.className = 'btn btn-outline-secondary';
                } else {
                    themeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
                    themeToggle.className = 'btn btn-outline-warning';
                }
            }
        }
        
        // System preference detection
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (!localStorage.getItem('theme')) {
            const systemTheme = mediaQuery.matches ? 'dark' : 'light';
            html.setAttribute('data-theme', systemTheme);
            updateThemeIcon(systemTheme);
        }
    });
    </script>
    <?php
}
add_action('wp_head', 'add_neo_dashboard_theme_assets');

/**
 * Fix Bootstrap CSS conflicts with theme system
 */
function fix_bootstrap_theme_conflicts() {
    if (is_plugin_active('neo-dashboard/neo-dashboard-core.php')) {
        echo '<style>
        /* Override Bootstrap CSS variables for theme compatibility */
        :root {
            --bs-body-bg: var(--theme-bg-color);
            --bs-body-color: var(--theme-text-color);
            --bs-navbar-brand-color: var(--theme-text-color);
            --bs-nav-link-color: var(--theme-text-color);
            --bs-link-color: var(--theme-primary-color);
        }
        
        /* Ensure Bootstrap components use theme colors */
        .navbar {
            background-color: var(--theme-secondary-bg) !important;
            border-bottom: 1px solid var(--theme-border-color) !important;
        }
        
        .navbar-light .navbar-brand,
        .navbar-light .navbar-nav .nav-link,
        .navbar-light .navbar-toggler-icon {
            color: var(--theme-text-color) !important;
        }
        
        .navbar-light .navbar-nav .nav-link:hover,
        .navbar-light .navbar-nav .nav-link:focus {
            color: var(--theme-primary-color) !important;
        }
        
        .bg-light {
            background-color: var(--theme-secondary-bg) !important;
        }
        
        .text-dark {
            color: var(--theme-text-color) !important;
        }
        
        .card {
            background-color: var(--theme-secondary-bg) !important;
            color: var(--theme-text-color) !important;
        }
        
        .table {
            --bs-table-bg: var(--theme-bg-color);
            --bs-table-color: var(--theme-text-color);
        }
        </style>';
    }
}
add_action('wp_head', 'fix_bootstrap_theme_conflicts', 5);
?>