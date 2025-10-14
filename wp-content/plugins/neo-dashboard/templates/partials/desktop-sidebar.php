<?php
/**
 * Partial: Static Sidebar (Desktop)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<aside class="col-md-3 col-lg-3 d-none d-md-flex flex-column bg-light">
    <ul class="nav nav-pills flex-column mb-0 p-3">
        <?php foreach ( $sidebar as $slug => $item ) :
            $is_active    = ( $slug === $current_section ) ? ' active' : '';
            $has_children = ! empty( $item['children'] );
            $child_active = $has_children && isset( $item['children'][ $current_section ] );
            $show_class   = $child_active ? ' show' : '';
            $chevron      = $child_active ? 'down' : 'right';
        ?>
            <?php if ( ! empty( $item['is_group'] ) ) : ?>
                <li class="nav-item mb-1">
                    <a class="nav-link<?php echo $is_active; ?> d-flex align-items-center justify-content-between"
                       data-bs-toggle="collapse"
                       href="#group-<?php echo esc_attr( $slug ); ?>"
                       aria-expanded="<?php echo $child_active ? 'true' : 'false'; ?>">
                        <span class="d-flex align-items-center gap-2">
                            <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                            <?php echo esc_html( $item['label'] ); ?>
                        </span>
                        <i class="bi bi-chevron-<?php echo $chevron; ?> small"></i>
                    </a>
                    <ul class="collapse ps-3 list-unstyled<?php echo $show_class; ?>"
                        id="group-<?php echo esc_attr( $slug ); ?>">
                        <?php foreach ( $item['children'] as $child_slug => $child ) :
                            $child_act_cls = ( $child_slug === $current_section ) ? ' active' : '';
                        ?>
                            <li class="nav-item">
                                <a href="<?php echo esc_url( home_url( $child['url'] ) ); ?>"
                                   class="nav-link<?php echo $child_act_cls; ?> d-flex align-items-center gap-2">
                                    <i class="<?php echo esc_attr( $child['icon'] ); ?>"></i>
                                    <?php echo esc_html( $child['label'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php else : ?>
                <li class="nav-item mb-1">
                    <a href="<?php echo esc_url( home_url( $item['url'] ) ); ?>"
                       class="nav-link<?php echo $is_active; ?> d-flex align-items-center gap-2">
                        <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                        <?php echo esc_html( $item['label'] ); ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</aside>
