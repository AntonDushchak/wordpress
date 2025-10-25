<?php

if (!defined('ABSPATH')) {
    exit;
}

class Neo_Dashboard_Theme_Switcher {
    
    public function __construct() {
        add_action('neo_dashboard_head', [$this, 'addThemeStyles'], 15);
        add_action('neo_dashboard_footer', [$this, 'addThemeScript'], 15);
    }
    
    public function addThemeStyles(): void {
        ?>
        <style id="neo-theme-switcher-vars">
        :root {
            --neo-theme-bg: #ffffff;
            --neo-theme-text: #212529;
            --neo-theme-primary: #007cba;
            --neo-theme-secondary-bg: #f8f9fa;
            --neo-theme-border: #dee2e6;
            --neo-theme-accent: #e9ecef;
        }

        html[data-neo-theme="dark"] {
            --neo-theme-bg: #1e1e1e !important;
            --neo-theme-text: #f0f0f0 !important;
            --neo-theme-primary: #0ea5e9 !important;
            --neo-theme-secondary-bg: #2a2a2a !important;
            --neo-theme-border: #404040 !important;
            --neo-theme-accent: #3f3f3f !important;
        }

        .neo-dashboard-standalone,
        .neo-dashboard-standalone body {
            background-color: var(--neo-theme-bg) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .container-fluid,
        .neo-dashboard-standalone main {
            background-color: var(--neo-theme-bg) !important;
        }

        .neo-dashboard-standalone .navbar,
        .neo-dashboard-standalone .navbar.bg-light {
            background-color: var(--neo-theme-secondary-bg) !important;
            border-bottom: 1px solid var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .navbar .navbar-brand,
        .neo-dashboard-standalone .navbar .nav-link,
        .neo-dashboard-standalone .navbar span,
        .neo-dashboard-standalone .navbar .navbar-toggler-icon,
        .neo-dashboard-standalone .sidebar-nav-link {
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .navbar .navbar-toggler {
            border-color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .nav-link:hover,
        .neo-dashboard-standalone .sidebar-nav-link:hover {
            background-color: var(--neo-theme-accent) !important;
            color: var(--neo-theme-primary) !important;
        }

        .neo-dashboard-standalone .card {
            background-color: var(--neo-theme-secondary-bg) !important;
            border-color: var(--neo-theme-border) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .desktop-sidebar,
        .neo-dashboard-standalone .offcanvas {
            background-color: var(--neo-theme-secondary-bg) !important;
            border-color: var(--neo-theme-border) !important;
        }

        /* Sidebar navigation text colors */
        .neo-dashboard-standalone .desktop-sidebar .nav-link,
        .neo-dashboard-standalone .offcanvas .nav-link {
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .desktop-sidebar .nav-link:hover,
        .neo-dashboard-standalone .offcanvas .nav-link:hover {
            background-color: var(--neo-theme-accent) !important;
            color: var(--neo-theme-primary) !important;
        }

        .neo-dashboard-standalone .desktop-sidebar .nav-link.active,
        .neo-dashboard-standalone .offcanvas .nav-link.active {
            background-color: var(--neo-theme-primary) !important;
            color: white !important;
        }

        /* Bootstrap button theming */
        .neo-dashboard-standalone .btn-primary {
            background-color: var(--neo-theme-primary) !important;
            border-color: var(--neo-theme-primary) !important;
        }

        .neo-dashboard-standalone .btn-outline-primary {
            color: var(--neo-theme-primary) !important;
            border-color: var(--neo-theme-primary) !important;
        }

        .neo-dashboard-standalone .btn-outline-primary:hover {
            background-color: var(--neo-theme-primary) !important;
            border-color: var(--neo-theme-primary) !important;
        }

        .neo-dashboard-standalone .btn-secondary {
            background-color: var(--neo-theme-accent) !important;
            border-color: var(--neo-theme-border) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .btn-outline-secondary {
            color: var(--neo-theme-text) !important;
            border-color: var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .btn-outline-secondary:hover {
            background-color: var(--neo-theme-accent) !important;
            border-color: var(--neo-theme-border) !important;
            color: var(--neo-theme-text) !important;
        }

        /* Form controls */
        .neo-dashboard-standalone .form-control,
        .neo-dashboard-standalone .form-select {
            background-color: var(--neo-theme-bg) !important;
            border-color: var(--neo-theme-border) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .form-control:focus,
        .neo-dashboard-standalone .form-select:focus {
            background-color: var(--neo-theme-bg) !important;
            border-color: var(--neo-theme-primary) !important;
            color: var(--neo-theme-text) !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        /* Modal theming */
        .neo-dashboard-standalone .modal-content {
            background-color: var(--neo-theme-bg) !important;
            color: var(--neo-theme-text) !important;
            border-color: var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .modal-header {
            border-bottom-color: var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .modal-footer {
            border-top-color: var(--neo-theme-border) !important;
        }

        /* Close button for dark theme */
        html[data-neo-theme="dark"] .neo-dashboard-standalone .btn-close {
            filter: invert(1);
        }

        /* Alert theming */
        .neo-dashboard-standalone .alert {
            border-color: var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .alert-info {
            background-color: rgba(13, 202, 240, 0.1) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .alert-success {
            background-color: rgba(25, 135, 84, 0.1) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .alert-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .alert-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: var(--neo-theme-text) !important;
        }

        /* General text and heading colors */
        .neo-dashboard-standalone h1,
        .neo-dashboard-standalone h2,
        .neo-dashboard-standalone h3,
        .neo-dashboard-standalone h4,
        .neo-dashboard-standalone h5,
        .neo-dashboard-standalone h6,
        .neo-dashboard-standalone p,
        .neo-dashboard-standalone span,
        .neo-dashboard-standalone div,
        .neo-dashboard-standalone label {
            color: var(--neo-theme-text) !important;
        }

        /* Card headers and footers */
        .neo-dashboard-standalone .card-header,
        .neo-dashboard-standalone .card-footer {
            background-color: var(--neo-theme-accent) !important;
            border-color: var(--neo-theme-border) !important;
            color: var(--neo-theme-text) !important;
        }

        /* Table theming */
        .neo-dashboard-standalone .table {
            color: var(--neo-theme-text) !important;
        }

        .neo-dashboard-standalone .table th,
        .neo-dashboard-standalone .table td {
            border-color: var(--neo-theme-border) !important;
        }

        .neo-dashboard-standalone .table-striped > tbody > tr:nth-of-type(odd) > td,
        .neo-dashboard-standalone .table-striped > tbody > tr:nth-of-type(odd) > th {
            background-color: var(--neo-theme-accent) !important;
        }

        /* Container fluid should have no background */
        .neo-dashboard-standalone .container-fluid {
            background: none !important;
            background-color: transparent !important;
        }

        /* Theme toggle button styles */
        .neo-dashboard-standalone #theme-toggle-navbar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--neo-theme-text);
            background: var(--neo-theme-secondary-bg);
            color: var(--neo-theme-text);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .neo-dashboard-standalone #theme-toggle-navbar:hover {
            transform: scale(1.05);
            background: var(--neo-theme-primary);
            color: white;
        }
        </style>
        <?php
    }
    
    public function addThemeScript(): void {
        ?>
        <script id="neo-theme-switcher-script">
        (function() {
            'use strict';
            
            if (!document.body.classList.contains('neo-dashboard-standalone')) {
                return;
            }
            
            const html = document.documentElement;
            const toggleButton = document.getElementById('theme-toggle-navbar');
            const STORAGE_KEY = 'neo-dashboard-theme';
            
            function updateTheme(theme) {
                html.setAttribute('data-neo-theme', theme);
                localStorage.setItem(STORAGE_KEY, theme);
                
                if (toggleButton) {
                    toggleButton.textContent = theme === 'light' ? '🌙' : '☀️';
                    toggleButton.title = theme === 'light' ? 
                        'Переключить на темную тему' : 
                        'Переключить на светлую тему';
                }
                
                console.log('Neo Dashboard theme updated to:', theme);
            }
            
            function initTheme() {
                const savedTheme = localStorage.getItem(STORAGE_KEY);
                
                if (savedTheme) {
                    updateTheme(savedTheme);
                } else {
                    const systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    updateTheme(systemTheme);
                }
            }
            
            function setupToggleButton() {
                if (!toggleButton) {
                    console.warn('Neo Dashboard theme toggle button not found');
                    return;
                }
                
                toggleButton.addEventListener('click', function() {
                    const currentTheme = html.getAttribute('data-neo-theme') || 'light';
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    updateTheme(newTheme);
                    
                    this.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                initTheme();
                setupToggleButton();
            });
            
            if (document.readyState === 'loading') {
            } else {
                setTimeout(function() {
                    initTheme();
                    setupToggleButton();
                }, 100);
            }
        })();
        </script>
        <?php
    }
}

function init_neo_dashboard_theme_switcher() {
    $is_neo_context = false;
    
    $section = get_query_var('neo_section', '');
    $pagename = get_query_var('pagename', '');
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    if ($section !== '' || $pagename === 'neo-dashboard' || strpos($request_uri, '/neo-dashboard') !== false) {
        $is_neo_context = true;
    }
    
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'neo-dashboard') {
        $is_neo_context = true;
    }
    
    if ($is_neo_context) {
        new Neo_Dashboard_Theme_Switcher();
    }
}

add_action('init', 'init_neo_dashboard_theme_switcher', 20);