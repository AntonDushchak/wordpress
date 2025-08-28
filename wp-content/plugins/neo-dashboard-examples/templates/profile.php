<?php
/**
 * Profile‑Template
 * Wird eingebunden, wenn in der Sidebar „Profil“ geklickt wird.
 */
$user = wp_get_current_user();
?>
<div class="p-3">
    <h3><?php echo esc_html__( 'Benutzerprofil', 'neo-dashboard' ); ?></h3>
    <p><strong><?php echo esc_html__( 'Name:', 'neo-dashboard' ); ?></strong> <?php echo esc_html( $user->display_name ); ?></p>
    <p><strong><?php echo esc_html__( 'E‑Mail:', 'neo-dashboard' ); ?></strong> <?php echo esc_html( $user->user_email ); ?></p>
    <p><strong><?php echo esc_html__( 'Rollen:', 'neo-dashboard' ); ?></strong> <?php echo esc_html( implode( ', ', $user->roles ) ); ?></p>
</div>
