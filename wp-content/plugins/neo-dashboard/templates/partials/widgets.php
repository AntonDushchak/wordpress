<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="row g-4">
    <?php foreach ( $widgets as $widget ) :
        $icon  = ! empty( $widget['icon'] )  ? esc_attr( $widget['icon'] )  : 'bi-grid';
        $label = ! empty( $widget['label'] ) ? esc_html( $widget['label'] ) : '';
        $cb    = $widget['callback'] ?? null;
    ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header fw-semibold d-flex align-items-center gap-2">
                    <i class="<?php echo $icon; ?>"></i>
                    <?php echo $label; ?>
                </div>
                <div class="card-body">
                    <?php
                    if ( is_callable( $cb ) ) {
                        call_user_func( $cb );
                    } else {
                        echo '<em>No callback defined.</em>';
                    }
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
