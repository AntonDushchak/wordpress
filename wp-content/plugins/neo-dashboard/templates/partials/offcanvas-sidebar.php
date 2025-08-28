<?php
/**
 * Partial: Offcanvas Sidebar (Mobile)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="offcanvas offcanvas-start bg-light d-md-none" id="sidebarOffcanvas" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Neo Dashboard</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <ul class="nav nav-pills flex-column mb-0">
            <?php foreach ( $sidebar as $slug => $item ) :
                $is_active = ( $slug === $current_section ) ? ' active' : '';
                // Prüfen, ob eine Kind-Section gerade aktiv ist
                $has_children = ! empty( $item['children'] );
                $child_active = $has_children && isset( $item['children'][ $current_section ] );
                // Soll Collapse geöffnet sein?
                $show = $child_active ? ' show' : '';
            ?>
                <?php if ( ! empty( $item['is_group'] ) ) : ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $is_active; ?> d-flex align-items-center justify-content-between"
                           data-bs-toggle="collapse"
                           href="#group-mobile-<?php echo esc_attr( $slug ); ?>"
                           aria-expanded="<?php echo $child_active ? 'true' : 'false'; ?>">
                            <span class="d-flex align-items-center gap-2">
                                <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                                <?php echo esc_html( $item['label'] ); ?>
                            </span>
                            <i class="bi bi-chevron-<?php echo $child_active ? 'down' : 'right'; ?> small"></i>
                        </a>
                        <ul class="collapse ps-3 list-unstyled<?php echo $show; ?>"
                            id="group-mobile-<?php echo esc_attr( $slug ); ?>">
                            <?php foreach ( $item['children'] as $child_slug => $child ) :
                                $child_cls = ( $child_slug === $current_section ) ? ' active' : '';
                            ?>
                                <li class="nav-item">
                                    <a href="<?php echo esc_url( home_url( $child['url'] ) ); ?>"
                                       class="nav-link<?php echo $child_cls; ?> d-flex align-items-center gap-2">
                                        <i class="<?php echo esc_attr( $child['icon'] ); ?>"></i>
                                        <?php echo esc_html( $child['label'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a href="<?php echo esc_url( home_url( $item['url'] ) ); ?>"
                           class="nav-link<?php echo $is_active; ?> d-flex align-items-center gap-2">
                            <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                            <?php echo esc_html( $item['label'] ); ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="border-top p-3">
            <?php echo \NeoDashboard\Core\Helper::render_nav_user_menu( $user ); ?>
        </div>
    </div>
</div>
