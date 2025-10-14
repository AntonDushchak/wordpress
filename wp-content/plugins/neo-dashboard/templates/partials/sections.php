<?php
/**
 * Partial: Active Section
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$section = \NeoDashboard\Core\Helper::get_active_section( $sections, $current_section );
if ( $section ) : ?>
    <section id="<?php echo esc_attr( $section['slug'] ); ?>">
        <header class="mb-3"><h2><?php echo esc_html( $section['label'] ); ?></h2></header>
        <?php
        $callback = $section['callback'] ?? null;
        if ( is_callable( $callback ) ) {
            call_user_func( $callback );
        } elseif ( ! empty( $section['template_path'] ) ) {
            include $section['template_path'];
        }
        ?>
    </section>
<?php endif; ?>
