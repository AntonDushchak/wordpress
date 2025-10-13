<?php
/**
 * Адаптивные стили для WordPress
 * Подключение CSS файлов для всех устройств
 * 
 * @package WordPress
 * @subpackage Responsive
 * @version 1.0.0
 */

// Предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Подключение адаптивных стилей
 */
function enqueue_responsive_styles() {
    // Получаем версию для кеширования
    $version = '1.0.0';
    
    // Основные адаптивные стили
    wp_enqueue_style(
        'global-responsive',
        get_template_directory_uri() . '/global-responsive.css',
        array(),
        $version,
        'all'
    );
    
    // Стили дашборда Neo
    if (is_plugin_active('neo-dashboard/neo-dashboard-core.php')) {
        wp_enqueue_style(
            'neo-dashboard-responsive',
            WP_PLUGIN_URL . '/neo-dashboard/assets/dashboard.css',
            array('global-responsive'),
            $version,
            'all'
        );
    }
    
    // Стили календаря Neo
    if (is_plugin_active('neo-calendar/neo-calendar.php')) {
        wp_enqueue_style(
            'neo-calendar-responsive',
            WP_PLUGIN_URL . '/neo-calendar/assets/css/neo-calendar.css',
            array('global-responsive'),
            $version,
            'all'
        );
    }
    
    // Стили опросов Neo
    if (is_plugin_active('neo-umfrage/neo-umfrage.php')) {
        wp_enqueue_style(
            'neo-umfrage-responsive',
            WP_PLUGIN_URL . '/neo-umfrage/assets/css/neo-umfrage.css',
            array('global-responsive'),
            $version,
            'all'
        );
    }
    
    // Стили работ Neo
    if (is_plugin_active('job-board-integration/job-board-integration.php')) {
        wp_enqueue_style(
            'neo-job-board-responsive',
            WP_PLUGIN_URL . '/job-board-integration/assets/css/neo-job-board.css',
            array('global-responsive'),
            $version,
            'all'
        );
        
        wp_enqueue_style(
            'neo-profession-autocomplete-responsive',
            WP_PLUGIN_URL . '/job-board-integration/assets/css/neo-profession-autocomplete.css',
            array('neo-job-board-responsive'),
            $version,
            'all'
        );
    }
}

/**
 * Добавление viewport meta тега для адаптивности
 */
function add_responsive_viewport() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
}

/**
 * Добавление дополнительных meta тегов для улучшения адаптивности
 */
function add_responsive_meta_tags() {
    // Viewport
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' . "\n";
    
    // Поддержка темной темы
    echo '<meta name="color-scheme" content="light dark">' . "\n";
    
    // Отключение автоматического детектирования номеров телефонов на iOS
    echo '<meta name="format-detection" content="telephone=no">' . "\n";
    
    // Улучшение рендеринга на мобильных устройствах
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    
    // Предзагрузка критических ресурсов
    echo '<link rel="preload" href="' . get_template_directory_uri() . '/global-responsive.css" as="style">' . "\n";
}

/**
 * Добавление JavaScript для мобильного меню
 */
function add_mobile_menu_script() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Мобильное меню
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('show');
                this.classList.toggle('active');
            });
        }
        
        // Подменю для мобильных
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
        
        // Обработчик изменения размера окна
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                // Сброс мобильных стилей на больших экранах
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
        
        // Обработка touch событий для улучшения UX на мобильных
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
            
            // Можно добавить обработку свайпов для закрытия меню
            if (Math.abs(diff) > swipeThreshold) {
                // Логика для свайпов
            }
        }
    });
    </script>
    <?php
}

/**
 * Добавление CSS переменных для динамического изменения стилей
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
        
        /* Брейкпоинты */
        --breakpoint-xs: 480px;
        --breakpoint-sm: 576px;
        --breakpoint-md: 768px;
        --breakpoint-lg: 992px;
        --breakpoint-xl: 1200px;
        
        /* Отступы */
        --spacing-xs: 0.25rem;
        --spacing-sm: 0.5rem;
        --spacing-md: 1rem;
        --spacing-lg: 1.5rem;
        --spacing-xl: 3rem;
    }
    
    /* Темная тема */
    @media (prefers-color-scheme: dark) {
        :root {
            --text-color: #e0e0e0;
            --bg-color: #121212;
            --secondary-color: #1e1e1e;
            --border-color: #333333;
        }
    }
    
    /* Адаптивные размеры шрифтов */
    html {
        font-size: clamp(14px, 2.5vw, 16px);
    }
    
    /* Адаптивные отступы */
    .container, 
    .container-fluid {
        padding-left: clamp(8px, 2vw, 15px);
        padding-right: clamp(8px, 2vw, 15px);
    }
    </style>
    <?php
}

/**
 * Функция для проверки мобильного устройства
 */
function is_mobile_device() {
    return wp_is_mobile();
}

/**
 * Функция для получения размера экрана через JavaScript
 */
function add_screen_size_detection() {
    ?>
    <script>
    // Определение размера экрана
    function getScreenSize() {
        const width = window.innerWidth;
        
        if (width <= 480) return 'xs';
        if (width <= 767) return 'sm';
        if (width <= 991) return 'md';
        if (width <= 1199) return 'lg';
        return 'xl';
    }
    
    // Добавление класса размера экрана к body
    function updateScreenSizeClass() {
        const body = document.body;
        const currentSize = getScreenSize();
        
        // Удаляем все классы размеров экрана
        body.classList.remove('screen-xs', 'screen-sm', 'screen-md', 'screen-lg', 'screen-xl');
        
        // Добавляем текущий класс
        body.classList.add('screen-' + currentSize);
        
        // Добавляем класс для мобильных устройств
        if (currentSize === 'xs' || currentSize === 'sm') {
            body.classList.add('is-mobile');
        } else {
            body.classList.remove('is-mobile');
        }
    }
    
    // Обновляем при загрузке и изменении размера
    document.addEventListener('DOMContentLoaded', updateScreenSizeClass);
    window.addEventListener('resize', updateScreenSizeClass);
    </script>
    <?php
}

// Хуки WordPress
add_action('wp_enqueue_scripts', 'enqueue_responsive_styles');
add_action('wp_head', 'add_responsive_meta_tags', 1);
add_action('wp_footer', 'add_mobile_menu_script');
add_action('wp_head', 'add_css_custom_properties');
add_action('wp_footer', 'add_screen_size_detection');

/**
 * Добавление кнопки мобильного меню в навигацию
 */
function add_mobile_menu_button($items, $args) {
    if ($args->theme_location == 'primary') {
        $mobile_button = '<li class="menu-item menu-toggle d-md-none">';
        $mobile_button .= '<a href="#" class="menu-toggle-link">☰ Меню</a>';
        $mobile_button .= '</li>';
        
        $items = $mobile_button . $items;
    }
    
    return $items;
}
add_filter('wp_nav_menu_items', 'add_mobile_menu_button', 10, 2);

/**
 * Добавление поддержки адаптивных изображений
 */
function add_responsive_image_sizes() {
    // Добавляем размеры изображений для разных экранов
    add_image_size('mobile-small', 480, 320, true);
    add_image_size('mobile-large', 767, 511, true);
    add_image_size('tablet', 1024, 683, true);
    add_image_size('desktop', 1200, 800, true);
}
add_action('after_setup_theme', 'add_responsive_image_sizes');

/**
 * Функция для вывода адаптивного изображения
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
 * Оптимизация для производительности
 */
function optimize_for_mobile() {
    if (is_mobile_device()) {
        // Отключаем некоторые скрипты на мобильных
        wp_dequeue_script('jquery-ui-core');
        wp_dequeue_script('jquery-ui-widget');
        
        // Добавляем критический CSS inline
        add_action('wp_head', function() {
            echo '<style id="critical-css">';
            echo 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}';
            echo '.container{max-width:100%;padding:0 15px}';
            echo 'img{max-width:100%;height:auto}';
            echo '</style>';
        }, 1);
    }
}
add_action('wp_enqueue_scripts', 'optimize_for_mobile', 100);
?>