<?php
/**
 * Template: Dashboard Layout (Bootstrap 5.3)
 * Wird von neo_dashboard_render() eingebunden.
 * Variablen: $sidebar, $widgets, $notifications, $sections, $user
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<?php include __DIR__ . '/partials/navbar.php'; ?>
<?php include __DIR__ . '/partials/offcanvas-sidebar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/partials/desktop-sidebar.php'; ?>

        <main class="col-12 col-md-9 col-lg-9 px-md-4 py-4 main-content-tablet">
            <?php // Notifications jetzt immer anzeigen ?>
            <?php include __DIR__ . '/partials/notifications.php'; ?>

            <?php if ( $active_section ) : ?>
                <?php include __DIR__ . '/partials/sections.php'; ?>
            <?php else : ?>
                <?php include __DIR__ . '/partials/widgets.php'; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
