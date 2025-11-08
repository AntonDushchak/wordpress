<?php
/**
 * Plugin Name: Neo Domain Changer
 * Description: Plugin zum Ändern der Domain
 * Version: 1.0.0
 * Author: Neo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Neo_Domain_Changer {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_post_change_domain', [$this, 'handle_domain_change']);
    }
    
    public function add_admin_page() {
        add_menu_page(
            'Domain ändern',
            'Domain ändern',
            'manage_options',
            'neo-domain-changer',
            [$this, 'render_admin_page'],
            'dashicons-admin-site',
            100
        );
    }
    
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung');
        }
        
        $current_domain = parse_url(home_url(), PHP_URL_HOST);
        ?>
        <div class="wrap">
            <h1>Domain ändern</h1>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Domain wurde erfolgreich geändert!</p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p>
                        <?php 
                        $error = $_GET['error'];
                        if ($error === 'empty') {
                            echo 'Fehler: Domain darf nicht leer sein';
                        } elseif ($error === 'bad_domain') {
                            echo 'Fehler: Ungültiges Domain-Format. Beispiel: example.com oder subdomain.example.com';
                        } else {
                            echo 'Fehler: ' . esc_html($error);
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <div style="padding: 20px;">
                    <p><strong>Aktuelle Domain:</strong> <?php echo esc_html($current_domain); ?></p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="neo-domain-changer-form">
                        <input type="hidden" name="action" value="change_domain">
                        <?php wp_nonce_field('change_domain_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="new_domain">Neu Domain</label>
                                </th>
                                <td>
                                    <input 
                                        type="text" 
                                        id="new_domain" 
                                        name="new_domain" 
                                        class="regular-text" 
                                        placeholder="example.com"
                                        required
                                    >
                                    <p class="description">Geben Sie die neue Domain ohne http:// oder https:// ein</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="neo-domain-changer-submit">
                                Domain ändern
                            </button>
                        </p>
                    </form>
                    <script>
                    (function() {
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.getElementById('neo-domain-changer-form');
                            const submitBtn = document.getElementById('neo-domain-changer-submit');
                            
                            if (!form || !submitBtn) return;
                            
                            form.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                if (window.NeoDash && window.NeoDash.confirm) {
                                    NeoDash.confirm('Sind Sie sicher, dass Sie die Domain ändern möchten?', {
                                        type: 'warning',
                                        title: 'Bestätigung der Änderung der Domain',
                                        confirmText: 'Ändern',
                                        cancelText: 'Abbrechen'
                                    }).then((confirmed) => {
                                        if (confirmed) {
                                            form.submit();
                                        }
                                    });
                                } else {
                                    if (confirm('Sind Sie sicher, dass Sie die Domain ändern möchten?')) {
                                        form.submit();
                                    }
                                }
                            });
                        });
                    })();
                    </script>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function handle_domain_change() {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung');
        }
        
        check_admin_referer('change_domain_nonce');
        
        $new_domain = sanitize_text_field($_POST['new_domain']);
        
        if (empty($new_domain)) {
            wp_redirect(admin_url('admin.php?page=neo-domain-changer&error=empty'));
            exit;
        }
        
        $new_domain = preg_replace('#^https?://#', '', $new_domain);
        $new_domain = rtrim($new_domain, '/');
        
        $allowed = '/^[a-z0-9-]+(\.[a-z0-9-]+)*$/i';

        if (!preg_match($allowed, $new_domain)) {
            wp_redirect(admin_url('admin.php?page=neo-domain-changer&error=bad_domain'));
            exit;
        }
        
        $script = '/usr/local/bin/set_domain.sh';
        $command = escapeshellarg($script) . ' ' . escapeshellarg($new_domain);

        if (!is_executable($script)) {
            $command = 'sh ' . $command;
        }

        error_log("Domain change command: " . $command);

        exec($command . " 2>&1", $output, $return_code);
        error_log("Output: " . implode(" ", $output));
        error_log("Return: " . $return_code);
        

        
        if ($return_code === 0) {
            wp_redirect(admin_url('admin.php?page=neo-domain-changer&success=1'));
        } else {
            $error_message = implode(' ', $output);
            wp_redirect(admin_url('admin.php?page=neo-domain-changer&error=' . urlencode($error_message)));
        }
        exit;
    }
}

new Neo_Domain_Changer();

