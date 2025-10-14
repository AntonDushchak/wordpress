<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<nav class="navbar navbar-light bg-light sticky-top shadow" id="neo-navbar">
    <div class="container-fluid">
        <a class="navbar-brand me-0 d-flex align-items-center" href="<?php echo esc_url( home_url( '/neo-dashboard/' ) ); ?>">
            <?php 
            $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
            $logo_exists = file_exists(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . 'assets/images/logo.png');
            if ($logo_exists): 
            ?>
                <img src="<?php echo $base; ?>assets/images/logo.png" alt="Neo Dashboard" height="32" class="me-2">
            <?php endif; ?>
            <span>Neo Dashboard</span>
        </a>
        <button class="navbar-toggler d-md-none border-0" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="d-flex align-items-center">
            <button id="theme-toggle-navbar" class="btn btn-outline-secondary me-2" title="Switch theme" onclick="toggleTheme()">
                🌙
            </button>
            <div class="d-none d-md-block">
                <?php echo \NeoDashboard\Core\Helper::render_nav_user_menu( $user ); ?>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const html = document.documentElement;
    const button = document.getElementById('theme-toggle-navbar');
    
    function updateTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        if (button) button.textContent = theme === 'light' ? '🌙' : '☀️';
    }
    
    updateTheme(localStorage.getItem('theme') || 'light');
    
    if (button) {
        button.onclick = function() {
            const current = html.getAttribute('data-theme') || 'light';
            updateTheme(current === 'light' ? 'dark' : 'light');
            this.style.transform = 'scale(0.9)';
            setTimeout(() => this.style.transform = 'scale(1)', 150);
        };
    }
    
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(toggle => {
        toggle.onclick = function() {
            const target = this.getAttribute('href').substring(1);
            document.querySelectorAll('.collapse.show').forEach(collapse => {
                if (collapse.id !== target) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                    if (bsCollapse) bsCollapse.hide();
                }
            });
        };
    });
});
</script>
