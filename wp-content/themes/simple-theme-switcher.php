<?php

function simple_theme_switcher_assets() {
    ?>
    <style>
    :root {
        --theme-bg: #ffffff;
        --theme-text: #212529; 
        --theme-primary: #007cba;
        --theme-secondary-bg: #f8f9fa;
        --theme-border: #dee2e6;
        --bs-white-rgb: 33, 37, 41;
        --bs-navbar-color: #212529;
        --bs-navbar-brand-color: #212529;
        --bs-nav-link-color: #212529;
    }
    
    html[data-theme="dark"] {
        --theme-bg: #1a1a1a !important;
        --theme-text: #e0e0e0 !important;
        --theme-primary: #4dabf7 !important;
        --theme-secondary-bg: #2d2d2d !important;
        --theme-border: #404040 !important;
        --bs-white-rgb: 224, 224, 224 !important;
        --bs-navbar-color: #e0e0e0 !important;
        --bs-navbar-brand-color: #e0e0e0 !important;
        --bs-nav-link-color: #e0e0e0 !important;
        --bs-body-bg: #1a1a1a !important;
        --bs-body-color: #e0e0e0 !important;
    }
    
    html[data-theme="dark"] body,
    html[data-theme="dark"] .container,
    html[data-theme="dark"] .container-fluid,
    html[data-theme="dark"] main,
    html[data-theme="dark"] .desktop-sidebar,
    html[data-theme="dark"] .offcanvas,
    html[data-theme="dark"] .navbar {
        background-color: #1a1a1a !important;
        color: #e0e0e0 !important;
    }
    
    html[data-theme="dark"] .navbar.bg-light {
        background-color: #2d2d2d !important;
    }
    
    html[data-theme="dark"] .sidebar-nav-link,
    html[data-theme="dark"] .navbar-nav .nav-link,
    html[data-theme="dark"] .navbar-brand,
    html[data-theme="dark"] a,
    html[data-theme="dark"] .dropdown-toggle {
        color: #e0e0e0 !important;
    }
    
    body {
        background-color: var(--theme-bg) !important;
        color: var(--theme-text) !important;
        transition: all 0.3s ease;
    }
    
    .container, .container-fluid {
        background-color: var(--theme-bg) !important;
    }
    
    h1, h2, h3, h4, h5, h6, p, span, div, section, article {
        color: var(--theme-text) !important;
    }
    
    :root {
        --bs-body-bg: var(--theme-bg);
        --bs-body-color: var(--theme-text);
        --bs-navbar-brand-color: var(--theme-text);
        --bs-nav-link-color: var(--theme-text);
        --bs-link-color: var(--theme-primary);
    }
    .navbar,
    .navbar.navbar-light,
    .navbar.navbar-dark,
    .navbar.bg-light,
    .navbar.bg-dark {
        background-color: var(--theme-secondary-bg) !important;
        border-bottom: 1px solid var(--theme-border) !important;
    }
    
    [data-theme="dark"] .navbar,
    [data-theme="dark"] .navbar.navbar-light,
    [data-theme="dark"] .navbar.bg-light {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text) !important;
    }
    
    .navbar {
        --bs-navbar-color: var(--theme-text) !important;
        --bs-navbar-hover-color: var(--theme-primary) !important;
        --bs-navbar-brand-color: var(--theme-text) !important;
        --bs-navbar-brand-hover-color: var(--theme-primary) !important;
        --bs-nav-link-color: var(--theme-text) !important;
        --bs-nav-link-hover-color: var(--theme-primary) !important;
        --bs-white-rgb: var(--theme-text) !important;
        --bs-text-opacity: 1 !important;
    }
    
    .navbar .navbar-brand,
    .navbar .navbar-nav .nav-link,
    .navbar.navbar-light .navbar-brand,
    .navbar.navbar-light .navbar-nav .nav-link,
    .navbar.navbar-dark .navbar-brand,
    .navbar.navbar-dark .navbar-nav .nav-link,
    .navbar.bg-dark .navbar-brand,
    .navbar.bg-dark .navbar-nav .nav-link,
    #neo-navbar .navbar-brand,
    #neo-navbar .navbar-nav .nav-link,
    #neo-navbar a,
    #neo-navbar .dropdown-toggle {
        color: var(--theme-text) !important;
    }
    
    [data-theme="dark"] .navbar .navbar-brand,
    [data-theme="dark"] .navbar .navbar-nav .nav-link,
    [data-theme="dark"] .navbar a,
    [data-theme="dark"] #neo-navbar a,
    [data-theme="dark"] #neo-navbar .dropdown-toggle {
        color: var(--theme-text) !important;
    }
    
    .navbar *[style*="rgba(var(--bs-white-rgb)"],
    .navbar a,
    .navbar .nav-link {
        color: var(--theme-text) !important;
    }
    
    .navbar .navbar-nav .nav-link:hover,
    .navbar .navbar-nav .nav-link:focus,
    .navbar.navbar-light .navbar-nav .nav-link:hover,
    .navbar.navbar-light .navbar-nav .nav-link:focus,
    .navbar.navbar-dark .navbar-nav .nav-link:hover,
    .navbar.navbar-dark .navbar-nav .nav-link:focus,
    .navbar.bg-dark .navbar-nav .nav-link:hover,
    .navbar.bg-dark .navbar-nav .nav-link:focus {
        color: var(--theme-primary) !important;
    }
    
    .navbar-toggler {
        border-color: var(--theme-border) !important;
    }
    
    .navbar-toggler-icon {
        background-image: none !important;
        color: var(--theme-text) !important;
    }
    
    .navbar-toggler:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 186, 0.25) !important;
    }
    
    .bg-light {
        background-color: var(--theme-secondary-bg) !important;
    }
    
    .bg-dark {
        background-color: var(--theme-bg) !important;
    }
    
    .text-white,
    .text-light,
    .text-dark,
    .navbar .dropdown .dropdown-toggle,
    .navbar .text-white,
    .dropdown-toggle {
        color: var(--theme-text) !important;
    }
    
    html[data-theme="dark"] {
        background-color: var(--theme-bg) !important;
    }
    
    html[data-theme="dark"] body,
    html[data-theme="dark"] .container,
    html[data-theme="dark"] .container-fluid,
    html[data-theme="dark"] main {
        background-color: var(--theme-bg) !important;
        color: var(--theme-text) !important;
    }
    
    .card {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text) !important;
        border-color: var(--theme-border) !important;
    }
    
    .card-header {
        background-color: var(--theme-bg) !important;
        border-bottom-color: var(--theme-border) !important;
    }
    
    a {
        color: var(--theme-primary) !important;
    }
    
    a:hover, a:focus {
        color: var(--theme-primary) !important;
        filter: brightness(1.2);
    }
    
    .btn-primary {
        background-color: var(--theme-primary) !important;
        border-color: var(--theme-primary) !important;
        color: #ffffff !important;
    }
    
    .btn-outline-secondary {
        color: var(--theme-text) !important;
        border-color: var(--theme-border) !important;
    }
    
    .btn-outline-secondary:hover {
        background-color: var(--theme-secondary-bg) !important;
        color: var(--theme-text) !important;
    }
    
    .theme-switcher {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999 !important;
    }
    
    #theme-toggle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        border: 2px solid var(--theme-border) !important;
        background: var(--theme-secondary-bg) !important;
        color: var(--theme-text) !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1) !important;
        outline: none;
    }
    
    #theme-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    #theme-toggle-navbar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 1px solid var(--theme-border) !important;
        background: var(--theme-secondary-bg) !important;
        color: var(--theme-text) !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    #theme-toggle-navbar:hover {
        transform: scale(1.05);
        background: var(--theme-primary) !important;
        color: white !important;
    }
    
    @media (max-width: 768px) {
        .theme-switcher {
            top: 15px;
            right: 15px;
        }
        
        #theme-toggle {
            width: 40px;
            height: 40px;
            font-size: 18px;
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
            font-size: 16px;
        }
    }
    </style>
    
    <script>
    function initThemeSwitcher() {
        console.log('Initializing theme switcher');
        
        const toggle = document.getElementById('theme-toggle-navbar') || document.getElementById('theme-toggle');
        const html = document.documentElement;
        const navbar = document.getElementById('neo-navbar') || document.querySelector('.navbar');
        
        console.log('Toggle button found:', !!toggle);
        console.log('Navbar found:', !!navbar);
        
        const saved = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', saved);
        updateNavbar(saved);
        updateIcon(saved);
        
        if (toggle) {
            console.log('Setting up click handler for toggle button');
            toggle.addEventListener('click', function() {
                console.log('Theme toggle clicked');
                const current = html.getAttribute('data-theme') || 'light';
                const newTheme = current === 'light' ? 'dark' : 'light';
                console.log('Switching from', current, 'to', newTheme);
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateNavbar(newTheme);
                updateIcon(newTheme);
                
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        } else {
            console.error('Toggle button not found');
        }
        
        function updateNavbar(theme) {
            if (navbar) {
                if (theme === 'dark') {
                    navbar.className = navbar.className.replace('navbar-light bg-light', 'navbar-dark bg-dark');
                } else {
                    navbar.className = navbar.className.replace('navbar-dark bg-dark', 'navbar-light bg-light');
                }
            }
        }
        
        function updateIcon(theme) {
            if (toggle) {
                toggle.textContent = theme === 'light' ? '🌙' : '☀️';
                toggle.title = theme === 'light' ? 'Переключить на темную тему' : 'Переключить на светлую тему';
            }
        }
        
        if (window.matchMedia && !localStorage.getItem('theme')) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const systemTheme = mediaQuery.matches ? 'dark' : 'light';
            html.setAttribute('data-theme', systemTheme);
            localStorage.setItem('theme', systemTheme);
            updateNavbar(systemTheme);
            updateIcon(systemTheme);
        }
    }
    
    document.addEventListener('DOMContentLoaded', initThemeSwitcher);
    
    setTimeout(initThemeSwitcher, 500);
    setTimeout(initThemeSwitcher, 1000);
    </script>
    <?php
    }
    

add_action('wp_head', 'simple_theme_switcher_assets');
add_action('admin_head', 'simple_theme_switcher_assets');

function add_theme_body_class($classes) {
    $classes[] = 'theme-ready';
    return $classes;
}
add_filter('body_class', 'add_theme_body_class');

function disable_conflicting_responsive_hooks() {
    remove_action('neo_dashboard_init', 'add_theme_switcher_to_neo_dashboard');
    remove_action('wp_head', 'add_neo_dashboard_theme_assets');
    remove_action('wp_head', 'fix_bootstrap_theme_conflicts', 5);
}
add_action('init', 'disable_conflicting_responsive_hooks', 5);
?>