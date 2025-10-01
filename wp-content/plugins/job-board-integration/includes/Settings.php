<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {
    
    public static function render_page() {
        // Einstellungen speichern
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['settings_nonce'], 'neo_job_board_settings')) {
            self::save_settings();
            echo '<div class="alert alert-success">Einstellungen gespeichert!</div>';
        }
        
        $settings = self::get_settings();
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-gear"></i> API-Einstellungen</h2>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <?php wp_nonce_field('neo_job_board_settings', 'settings_nonce'); ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="api_url" class="form-label">API-URL</label>
                                            <input type="url" class="form-control" id="api_url" name="api_url" 
                                                value="<?php echo esc_attr($settings['api_url'] ?? ''); ?>" 
                                                placeholder="https://api.example.com">
                                            <div class="form-text">Basis-URL für die API-Integration</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="api_key" class="form-label">API-Schlüssel</label>
                                            <input type="password" class="form-control" id="api_key" name="api_key" 
                                                value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                                                placeholder="API-Schlüssel eingeben">
                                            <div class="form-text">Geheimer Schlüssel für die Autorisierung</div>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="webhook_url" class="form-label">Webhook-URL</label>
                                            <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                                                value="<?php echo esc_attr($settings['webhook_url'] ?? ''); ?>" 
                                                placeholder="https://webhook.example.com/jobs">
                                            <div class="form-text">URL zum Senden von Benachrichtigungen über neue Bewerbungen</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="webhook_secret" class="form-label">Webhook-Geheimnis</label>
                                            <input type="password" class="form-control" id="webhook_secret" name="webhook_secret" 
                                                value="<?php echo esc_attr($settings['webhook_secret'] ?? ''); ?>" 
                                                placeholder="Webhook-Geheimschlüssel">
                                            <div class="form-text">Geheimnis zum Signieren von Webhook-Anfragen</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h5>Настройки загрузки файлов</h5>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="max_file_size" class="form-label">Максимальный размер файла (MB)</label>
                                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                                value="<?php echo esc_attr($settings['max_file_size'] ?? 5); ?>" 
                                                min="1" max="50">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="allowed_file_types" class="form-label">Разрешенные типы файлов</label>
                                            <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                                value="<?php echo esc_attr($settings['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png'); ?>" 
                                                placeholder="pdf,doc,docx,jpg,jpeg,png">
                                            <div class="form-text">Разделяйте типы файлов запятыми</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h5>Benutzerzugriff verwalten</h5>
                                
                                <!-- Добавление нового пользователя -->
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="new_user_select" class="form-label">Benutzer hinzufügen</label>
                                            <select class="form-select" id="new_user_select">
                                                <option value="">Benutzer auswählen...</option>
                                                <?php
                                                $all_users = get_users(['orderby' => 'display_name']);
                                                $allowed_users = $settings['allowed_users'] ?? [];
                                                
                                                foreach ($all_users as $user) {
                                                    // Пропускаем уже добавленных пользователей
                                                    if (in_array($user->ID, $allowed_users)) {
                                                        continue;
                                                    }
                                                    
                                                    echo sprintf(
                                                        '<option value="%d" data-email="%s" data-display-name="%s">%s (%s)</option>',
                                                        $user->ID,
                                                        esc_attr($user->user_email),
                                                        esc_attr($user->display_name),
                                                        esc_html($user->display_name),
                                                        esc_html($user->user_email)
                                                    );
                                                }
                                                ?>
                                            </select>
                                            <div class="form-text">Wählen Sie einen Benutzer aus, dem Sie Zugriff gewähren möchten</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <div>
                                                <button type="button" class="btn btn-success" onclick="addUserAccess()">
                                                    <i class="bi bi-plus-circle"></i> Zugriff gewähren
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Список пользователей с доступом -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Benutzer mit Zugriff</label>
                                            <div id="users-with-access" class="border rounded p-3" style="min-height: 200px; max-height: 300px; overflow-y: auto;">
                                                <?php
                                                $allowed_users = $settings['allowed_users'] ?? [];
                                                if (empty($allowed_users)) {
                                                    echo '<div class="text-muted text-center py-3">Keine Benutzer mit Zugriff</div>';
                                                } else {
                                                    foreach ($allowed_users as $user_id) {
                                                        $user = get_user_by('id', $user_id);
                                                        if ($user) {
                                                            echo sprintf(
                                                                '<div class="d-flex justify-content-between align-items-center border-bottom py-2" data-user-id="%d">
                                                                    <div>
                                                                        <strong>%s</strong> (%s)
                                                                        <br><small class="text-muted">ID: %d</small>
                                                                    </div>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUserAccess(%d)">
                                                                        <i class="bi bi-trash"></i> Entfernen
                                                                    </button>
                                                                </div>',
                                                                $user->ID,
                                                                esc_html($user->display_name),
                                                                esc_html($user->user_email),
                                                                $user->ID,
                                                                $user->ID
                                                            );
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="form-text">
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="syncUsersToApi()">
                                                    <i class="bi bi-cloud-upload"></i> Benutzer mit API synchronisieren
                                                </button>
                                                <?php if ($settings['user_access_synced'] ?? false): ?>
                                                    <span class="badge bg-success ms-2">Synchronisiert</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning ms-2">Nicht synchronisiert</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Скрытое поле для хранения ID пользователей -->
                                <input type="hidden" id="allowed_users_hidden" name="allowed_users" value="<?php echo esc_attr(implode(',', $settings['allowed_users'] ?? [])); ?>">
                                
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Einstellungen speichern
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="testApiConnection()">
                                    <i class="bi bi-wifi"></i> Verbindung testen
                                </button>
                                
                                <button type="button" class="btn btn-outline-info ms-2" onclick="syncAllTemplates()">
                                    <i class="bi bi-arrow-repeat"></i> Alle Vorlagen synchronisieren
                                </button>
                                
                                <button type="button" class="btn btn-outline-warning ms-2" onclick="autoRegisterAdmins()">
                                    <i class="bi bi-person-plus"></i> Administratoren registrieren
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
        // Создаем neoJobBoardAjax если его нет
        if (typeof neoJobBoardAjax === 'undefined') {
            window.neoJobBoardAjax = {
                ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('neo_job_board_nonce'); ?>',
                pluginUrl: '<?php echo plugin_dir_url(dirname(__FILE__)); ?>'
            };
        }
        
        // Ждем jQuery и инициализируем
        function waitForjQuerySettings() {
            if (typeof jQuery !== 'undefined') {
                initSettingsPage();
            } else {
                setTimeout(waitForjQuerySettings, 100);
            }
        }
        
        function initSettingsPage() {
            jQuery(document).ready(function($) {
            console.log('Settings page JavaScript loaded');
            
            // Показать сообщение
            window.showMessage = function(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
                
                $('.alert').remove();
                $('.card-body').prepend(alertHtml);
                
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 5000);
            }
            
            window.testApiConnection = function() {
                const apiUrl = document.getElementById('api_url').value;
                
                if (!apiUrl) {
                    showMessage('error', 'Введите URL API');
                    return;
                }
                
                const $btn = $('[onclick="testApiConnection()"]');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Тестирование...');
                
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_test_api_connection',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                        } else {
                            showMessage('error', response.data || 'Ошибка тестирования API');
                        }
                    },
                    error: function(xhr, status, error) {
                        showMessage('error', 'Ошибка соединения: ' + error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            };
            
            window.syncAllTemplates = function() {
                if (!confirm('Вы уверены, что хотите синхронизировать все шаблоны с API?')) {
                    return;
                }
                
                const $btn = $('[onclick="syncAllTemplates()"]');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Синхронизация...');
                
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_sync_all_templates',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = response.data.message;
                            if (response.data.errors && response.data.errors.length > 0) {
                                showMessage('error', message);
                            } else {
                                showMessage('success', message);
                            }
                        } else {
                            showMessage('error', response.data || 'Ошибка синхронизации');
                        }
                    },
                    error: function(xhr, status, error) {
                        showMessage('error', 'Ошибка соединения: ' + error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            };
            
            window.addUserAccess = function() {
                console.log('addUserAccess called');
                
                const $select = $('#new_user_select');
                const userId = $select.val();
                const email = $select.find('option:selected').data('email');
                const displayName = $select.find('option:selected').data('display-name');
                
                console.log('Selected user:', {userId, email, displayName});
                
                if (!userId) {
                    showMessage('error', 'Bitte wählen Sie einen Benutzer aus');
                    return;
                }
                
                // Проверяем, есть ли уже такой пользователь
                const existingUser = $(`#users-with-access [data-user-id="${userId}"]`);
                if (existingUser.length > 0) {
                    showMessage('error', 'Dieser Benutzer hat bereits Zugriff');
                    return;
                }
                
                // Добавляем пользователя в список
                const userHtml = `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2" data-user-id="${userId}">
                        <div>
                            <strong>${displayName}</strong>
                            <br><small class="text-muted">E-Mail: ${email}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUserAccess(${userId})">
                            <i class="bi bi-trash"></i> Entfernen
                        </button>
                    </div>
                `;
                
                // Убираем сообщение "Keine Benutzer" если есть
                $('#users-with-access .text-muted').remove();
                
                // Добавляем пользователя
                $('#users-with-access').append(userHtml);
                
                // Удаляем выбранного пользователя из списка
                $select.find('option:selected').remove();
                $select.val('');
                
                // Обновляем скрытое поле
                updateHiddenField();
                
                // Отладочная информация
                console.log('Adding user:', {userId, displayName, email});
                
                // Автоматически синхронизируем с API
                console.log('Calling syncSingleUserToApi...');
                syncSingleUserToApi(userId, displayName, email);
                
                showMessage('success', 'Benutzer hinzugefügt');
            };
            
            window.removeUserAccess = function(userId) {
                if (!confirm('Möchten Sie den Zugriff für diesen Benutzer wirklich entfernen?')) {
                    return;
                }
                
                const $userElement = $(`#users-with-access [data-user-id="${userId}"]`);
                const displayName = $userElement.find('strong').text();
                const email = $userElement.find('small').text().replace('E-Mail: ', '');
                
                // Удаляем пользователя из списка
                $userElement.remove();
                
                // Возвращаем пользователя в выпадающий список
                const $select = $('#new_user_select');
                const optionHtml = `<option value="${userId}" data-email="${email}" data-display-name="${displayName}">${displayName} (${email})</option>`;
                $select.append(optionHtml);
                
                // Если нет пользователей, показываем сообщение
                if ($('#users-with-access .d-flex').length === 0) {
                    $('#users-with-access').html('<div class="text-muted text-center py-3">Keine Benutzer mit Zugriff</div>');
                }
                
                // Обновляем скрытое поле
                updateHiddenField();
                
                showMessage('success', 'Benutzer entfernt');
            };
            
            window.updateHiddenField = function() {
                const userIds = [];
                $('#users-with-access [data-user-id]').each(function() {
                    const userId = $(this).data('user-id');
                    if (userId) {
                        userIds.push(userId);
                    }
                });
                $('#allowed_users_hidden').val(userIds.join(','));
            };
            
            window.syncSingleUserToApi = function(userId, displayName, email) {
                console.log('syncSingleUserToApi called with:', {userId, displayName, email});
                
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_sync_users',
                        nonce: neoJobBoardAjax.nonce,
                        allowed_users: userId.toString()
                    },
                    success: function(response) {
                        console.log('API sync response:', response);
                        if (response.success) {
                            console.log('User synced to API:', response.data);
                        } else {
                            console.error('Failed to sync user to API:', response.data);
                            showMessage('error', 'Ошибка синхронизации пользователя с API');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('API sync error:', error);
                        showMessage('error', 'Ошибка соединения с API');
                    }
                });
            };
            
            window.updateUsersListFromAdminIds = function(adminIds) {
                console.log('Updating users list with admin IDs:', adminIds);
                
                // Получаем данные пользователей через AJAX
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_get_users_data',
                        nonce: neoJobBoardAjax.nonce,
                        user_ids: adminIds.join(',')
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('Users data received:', response.data);
                            // Обновляем список пользователей
                            updateUsersList(response.data);
                        } else {
                            console.error('Failed to get users data:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error getting users data:', error);
                    }
                });
            };
            
            window.updateUsersList = function(users) {
                const $container = $('#users-with-access');
                
                // Очищаем контейнер
                $container.empty();
                
                if (users.length === 0) {
                    $container.html('<div class="text-muted text-center py-3">Keine Benutzer mit Zugriff</div>');
                    return;
                }
                
                // Добавляем пользователей
                users.forEach(function(user) {
                    const userHtml = `
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2" data-user-id="${user.ID}">
                            <div>
                                <strong>${user.display_name}</strong> (${user.user_email})
                                <br><small class="text-muted">ID: ${user.ID}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUserAccess(${user.ID})">
                                <i class="bi bi-trash"></i> Entfernen
                            </button>
                        </div>
                    `;
                    $container.append(userHtml);
                });
                
                // Обновляем скрытое поле
                updateHiddenField();
            };
            
            window.autoRegisterAdmins = function() {
                if (!confirm('Möchten Sie alle Administratoren automatisch in der API registrieren?')) {
                    return;
                }
                
                const $btn = $('[onclick="autoRegisterAdmins()"]');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Registrierung...');
                
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_auto_register_admins',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            let message = data.message;
                            
                            if (data.error_count > 0) {
                                message += ` (${data.error_count} ошибок)`;
                            }
                            
                            showMessage('success', message);
                            
                            // Обновляем список пользователей с доступом
                            if (data.admin_ids && data.admin_ids.length > 0) {
                                console.log('Admin IDs received:', data.admin_ids);
                                // Обновляем список пользователей динамически
                                updateUsersListFromAdminIds(data.admin_ids);
                            }
                        } else {
                            const data = response.data;
                            let message = data.message || 'Ошибка регистрации администраторов';
                            
                            if (data.error_count > 0) {
                                message += ` (${data.error_count} ошибок)`;
                            }
                            
                            showMessage('error', message);
                            
                            // Показываем детали ошибок
                            if (data.results) {
                                console.log('Ошибки регистрации:', data.results);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        showMessage('error', 'Ошибка соединения: ' + error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            };
            
            window.syncUsersToApi = function() {
                if (!confirm('Вы уверены, что хотите синхронизировать выбранных пользователей с API?')) {
                    return;
                }
                
                const $btn = $('[onclick="syncUsersToApi()"]');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Синхронизация...');
                
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_sync_users',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            let message = data.message;
                            
                            if (data.error_count > 0) {
                                message += ` (${data.error_count} ошибок)`;
                            }
                            
                            showMessage('success', message);
                            
                            // Показываем детали если есть ошибки
                            if (data.error_count > 0 && data.results) {
                                console.log('Детали синхронизации:', data.results);
                            }
                            
                            // Обновляем статус синхронизации
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            const data = response.data;
                            let message = data.message || 'Ошибка синхронизации пользователей';
                            
                            if (data.error_count > 0) {
                                message += ` (${data.error_count} ошибок)`;
                            }
                            
                            showMessage('error', message);
                            
                            // Показываем детали ошибок
                            if (data.results) {
                                console.log('Ошибки синхронизации:', data.results);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        showMessage('error', 'Ошибка соединения: ' + error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            };
            });
        }
        
        // Начинаем ждать jQuery
        waitForjQuerySettings();
        </script>
        <?php
    }

    /**
     * Получение настроек
     */
    public static function get_settings() {
        $defaults = [
            'api_url' => 'http://192.168.1.102:3000/api',
            'api_key' => 'wp_admin_key_2025',
            'webhook_url' => '',
            'webhook_enabled' => false,
            'auto_delete_applications' => false,
            'allowed_users' => [],
            'user_access_synced' => false
        ];
        
        return array_merge($defaults, get_option('neo_job_board_settings', []));
    }

    /**
     * Сохранение настроек
     */
    private static function save_settings() {
        $old_settings = self::get_settings();
        $new_api_url = sanitize_url($_POST['api_url'] ?? '');
        $old_api_url = $old_settings['api_url'] ?? '';
        
        $settings = [
            'api_url' => $new_api_url,
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'webhook_url' => sanitize_url($_POST['webhook_url'] ?? ''),
            'webhook_secret' => sanitize_text_field($_POST['webhook_secret'] ?? ''),
            'max_file_size' => (int) ($_POST['max_file_size'] ?? 5),
            'allowed_file_types' => sanitize_text_field($_POST['allowed_file_types'] ?? ''),
            'allowed_users' => array_map('intval', array_filter(explode(',', $_POST['allowed_users'] ?? ''))),
            'user_access_synced' => false
        ];
        
        update_option('neo_job_board_settings', $settings);
        
        // Если URL изменился и есть API ключ, автоматически регистрируем администраторов
        if ($new_api_url !== $old_api_url && !empty($new_api_url) && !empty($settings['api_key'])) {
            self::auto_register_administrators();
        }
    }
    
    /**
     * Автоматическая регистрация администраторов при изменении URL
     */
    private static function auto_register_administrators() {
        // Получаем всех администраторов
        $admins = get_users(['role' => 'administrator']);
        
        if (empty($admins)) {
            return;
        }
        
        // Подготавливаем данные администраторов
        $users_data = [];
        foreach ($admins as $admin) {
            $users_data[] = [
                'id' => $admin->ID,
                'username' => $admin->user_login,
                'email' => $admin->user_email,
                'display_name' => $admin->display_name,
                'role' => 'administrator'
            ];
        }
        
        // Отправляем данные в API
        $api_client = new \NeoJobBoard\APIClientV2();
        $api_response = $api_client->sync_users($users_data);
        
        if ($api_response['success']) {
            // Обновляем настройки - добавляем всех администраторов в allowed_users
            $settings = self::get_settings();
            $admin_ids = array_column($users_data, 'id');
            $settings['allowed_users'] = $admin_ids;
            $settings['user_access_synced'] = true;
            update_option('neo_job_board_settings', $settings);
            
            // Логируем успешную регистрацию
            error_log("Neo Job Board: Автоматически зарегистрированы администраторы: " . implode(', ', $admin_ids));
        } else {
            // Логируем ошибку
            error_log("Neo Job Board: Ошибка автоматической регистрации администраторов: " . $api_response['message']);
        }
    }

    /**
     * Получение конкретной настройки
     */
    public static function get_setting($key, $default = null) {
        $settings = self::get_settings();
        return $settings[$key] ?? $default;
    }
}