<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-shield-exclamation text-danger" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h1 class="h3 mb-3 text-danger">Zugang verweigert</h1>
                    
                    <p class="text-muted mb-4">
                        Sie haben keine Berechtigung, diese Seite aufzurufen.
                        <?php if (is_user_logged_in()): ?>
                            Ihre aktuelle Rolle erlaubt nur den Zugriff auf das Neo Dashboard.
                        <?php else: ?>
                            Bitte melden Sie sich an, um fortzufahren.
                        <?php endif; ?>
                    </p>
                    
                    <div class="d-grid gap-2">
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/neo-dashboard'); ?>" class="btn btn-primary">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Zum Dashboard
                            </a>
                            
                            <?php if (\NeoDashboard\Core\is_neo_admin()): ?>
                                <a href="<?php echo admin_url(); ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-gear me-2"></i>
                                    Zur Verwaltung
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Abmelden
                            </a>
                        <?php else: ?>
                            <a href="<?php echo wp_login_url(home_url('/neo-dashboard')); ?>" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Anmelden
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo home_url(); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>
                            Zur Startseite
                        </a>
                    </div>
                    
                    <?php if (is_user_logged_in()): ?>
                        <hr class="my-4">
                        <div class="text-start">
                            <h6 class="text-muted">Ihre Berechtigungen:</h6>
                            <ul class="list-unstyled small text-muted">
                                <?php
                                $user = wp_get_current_user();
                                $current_role = \NeoDashboard\Core\get_current_neo_role();
                                
                                switch($current_role) {
                                    case 'administrator':
                                        echo '<li><i class="bi bi-check-circle text-success me-1"></i> Vollzugriff auf alle Bereiche</li>';
                                        break;
                                    case 'neo_editor':
                                        echo '<li><i class="bi bi-check-circle text-success me-1"></i> Zugriff auf Neo Dashboard</li>';
                                        echo '<li><i class="bi bi-check-circle text-success me-1"></i> Editor-Funktionen</li>';
                                        break;
                                    case 'neo_mitarbeiter':
                                        echo '<li><i class="bi bi-check-circle text-success me-1"></i> Zugriff auf Neo Dashboard</li>';
                                        echo '<li><i class="bi bi-check-circle text-success me-1"></i> Mitarbeiter-Funktionen</li>';
                                        break;
                                    default:
                                        echo '<li><i class="bi bi-x-circle text-danger me-1"></i> Keine besonderen Berechtigungen</li>';
                                }
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (WP_DEBUG): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Debug-Informationen</h6>
                        <small class="text-muted">
                            <strong>Aktuelle URL:</strong> <?php echo esc_html($_SERVER['REQUEST_URI'] ?? 'Unbekannt'); ?><br>
                            <strong>Benutzer-ID:</strong> <?php echo is_user_logged_in() ? get_current_user_id() : 'Nicht angemeldet'; ?><br>
                            <strong>Rolle:</strong> <?php echo is_user_logged_in() ? \NeoDashboard\Core\get_current_neo_role() : 'Gast'; ?><br>
                            <strong>Zeitstempel:</strong> <?php echo date('d.m.Y H:i:s'); ?>
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>