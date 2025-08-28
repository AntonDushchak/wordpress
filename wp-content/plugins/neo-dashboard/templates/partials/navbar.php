<?php
/**
 * Partial: Navbar
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<nav class="navbar navbar-dark bg-dark sticky-top shadow">
    <div class="container-fluid">
        <a class="navbar-brand me-0" href="<?php echo esc_url( home_url( '/neo-dashboard/' ) ); ?>">
            Neo Dashboard
        </a>
        <button class="navbar-toggler d-md-none border-0" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="d-none d-md-block">
            <?php echo \NeoDashboard\Core\Helper::render_nav_user_menu( $user ); ?>
        </div>
    </div>
</nav>
