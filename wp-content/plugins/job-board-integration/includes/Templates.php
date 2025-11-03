<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

class Templates {
    
    public static function render_page() {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h2><i class="bi bi-file-earmark-text"></i> Vorlagen</h2>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Vorlagen werden automatisch mit der externen API synchronisiert
                                </small>
                            </div>
                            <button class="btn btn-primary" onclick="openAddTemplateModal()">
                                <i class="bi bi-plus-circle"></i> Vorlage erstellen
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="templates-list">Laden...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
        if (typeof neoJobBoardAjax === 'undefined') {
            window.neoJobBoardAjax = {
                ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('neo_job_board_nonce'); ?>',
                pluginUrl: '<?php echo plugin_dir_url(dirname(__FILE__)); ?>'
            };
        }
        

        
        function waitForjQuery() {
            if (typeof jQuery !== 'undefined') {
                initTemplatesPage();
            } else {
                setTimeout(waitForjQuery, 100);
            }
        }
        
        function initTemplatesPage() {
            jQuery(document).ready(function($) {
            setTimeout(function() {
                
                if (window.NeoTemplatesCore && window.NeoTemplatesCore.init) {
                    window.NeoTemplatesCore.init();
                    
                    setTimeout(function() {
                        if (window.reloadTemplatesList) {
                            window.reloadTemplatesList();
                        }
                    }, 1000);
                    
                    window.reloadTemplatesList = function() {
                        
                        $.ajax({
                            url: neoJobBoardAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'neo_job_board_check_templates_sync',
                                nonce: neoJobBoardAjax.nonce
                            },
                            success: function(syncResponse) {
                                console.log('Sync check response:', syncResponse);
                                
                                $.ajax({
                                    url: neoJobBoardAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'neo_job_board_get_templates',
                                        nonce: neoJobBoardAjax.nonce
                                    },
                                    success: function(response) {
                                        
                                        if (response.success) {
                                            let templates = response.data;
                                    
                                            
                                            if (syncResponse.success) {
                                                
                                                templates.forEach(function(template) {
                                                    const syncInfo = syncResponse.data[template.id];
                                                    if (syncInfo) {
                                                        template.sync_status = syncInfo.status;
                                                        template.exists_on_site = syncInfo.exists_on_site;
                                                        console.log('Template sync info added', {
                                                            id: template.id, 
                                                            name: template.name, 
                                                            sync_status: template.sync_status,
                                                            exists_on_site: template.exists_on_site
                                                        });
                                                    }
                                                });
                                            } else {
                                                console.log('Sync check failed, no sync info added');
                                            }
                                            
                                            if (window.NeoTemplatesCore && window.NeoTemplatesCore.renderTemplatesList) {
                                                
                                                window.NeoTemplatesCore.renderTemplatesList(templates);
                                            } else {
                                               
                                                $('#templates-list').html('<div class="alert alert-info">Шаблонов загружено: ' + templates.length + '</div>');
                                            }
                                        } else {
                                            console.error('reloadTemplatesList: Error response:', response.data);
                                            $('#templates-list').html('<div class="alert alert-danger">Ошибка загрузки: ' + response.data + '</div>');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('reloadTemplatesList: AJAX error:', error);
                                        $('#templates-list').html('<div class="alert alert-danger">AJAX ошибка: ' + error + '</div>');
                                    }
                                });
                            },
                            error: function(xhr, status, error) {
                                console.error('Sync check error:', error);
                                $.ajax({
                                    url: neoJobBoardAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'neo_job_board_get_templates',
                                        nonce: neoJobBoardAjax.nonce
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            let templates = response.data;
                                            if (window.NeoTemplatesCore && window.NeoTemplatesCore.renderTemplatesList) {
                                                window.NeoTemplatesCore.renderTemplatesList(templates);
                                            } else {
                                                $('#templates-list').html('<div class="alert alert-info">Шаблонов загружено: ' + templates.length + '</div>');
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    };
                } else {
                    console.log('NeoTemplatesCore not found, using fallback template loading');
                    loadTemplatesWithSyncCheck();
                }
                
                function loadTemplatesWithSyncCheck() {
                    $.ajax({
                        url: neoJobBoardAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'neo_job_board_check_templates_sync',
                            nonce: neoJobBoardAjax.nonce
                        },
                        success: function(syncResponse) {
                            console.log('Fallback sync check response:', syncResponse);
                            
                            $.ajax({
                                url: neoJobBoardAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'neo_job_board_get_templates',
                                    nonce: neoJobBoardAjax.nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        let templates = response.data;
                                        if (response.data.templates) {
                                            templates = response.data.templates;
                                        }
                                        
                                        renderTemplatesWithSyncInfo(templates, syncResponse.success ? syncResponse.data : {});
                                    } else {
                                        console.error('Fallback: Error response:', response.data);
                                        $('#templates-list').html('<div class="alert alert-danger">Ошибка загрузки: ' + response.data + '</div>');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Fallback: AJAX error:', error);
                                    $('#templates-list').html('<div class="alert alert-danger">AJAX ошибка: ' + error + '</div>');
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Fallback sync check error:', error);
                            $.ajax({
                                url: neoJobBoardAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'neo_job_board_get_templates',
                                    nonce: neoJobBoardAjax.nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        let templates = response.data;
                                        if (response.data.templates) {
                                            templates = response.data.templates;
                                        }
                                        renderTemplatesWithSyncInfo(templates, {});
                                    }
                                }
                            });
                        }
                    });
                }
                
                function renderTemplatesWithSyncInfo(templates, syncData) {
                    let html = '<div class="table-responsive"><table class="table table-striped">';
                    html += '<thead><tr><th>ID</th><th>Название</th><th>Статус синхронизации</th><th>Действия</th></tr></thead>';
                    html += '<tbody>';
                    
                    templates.forEach(function(template) {
                        const syncInfo = syncData[template.id];
                        let syncStatus = 'Неизвестно';
                        let syncClass = 'badge-secondary';
                        
                        if (syncInfo) {
                            if (syncInfo.exists_on_site) {
                                syncStatus = 'Синхронизирован';
                                syncClass = 'badge-success';
                            } else {
                                syncStatus = 'Не синхронизирован';
                                syncClass = 'badge-warning';
                            }
                        }
                        
                        html += '<tr>';
                        html += '<td>' + template.id + '</td>';
                        html += '<td>' + (template.name || 'Без названия') + '</td>';
                        html += '<td><span class="badge ' + syncClass + '">' + syncStatus + '</span></td>';
                        html += '<td>';
                        html += '<button class="btn btn-sm btn-primary me-1" onclick="editTemplate(' + template.id + ')">Редактировать</button>';
                        html += '<button class="btn btn-sm btn-danger" onclick="deleteTemplate(' + template.id + ')">Удалить</button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table></div>';
                    $('#templates-list').html(html);
                }
                
                if (window.NeoTemplatesModal && window.NeoTemplatesModal.init) {
                    window.NeoTemplatesModal.init();
                }
                
                if (window.NeoTemplatesFields && window.NeoTemplatesFields.init) {
                    window.NeoTemplatesFields.init();
                }
            }, 500);
            });
        }
        
        waitForjQuery();
        </script>

        <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="templateModalLabel">Vorlage erstellen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="template-form" onsubmit="return false;">
                            <div class="mb-3">
                                <label for="template-name" class="form-label">Name der Vorlage</label>
                                <input type="text" class="form-control" id="template-name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="template-description" class="form-label">Beschreibung</label>
                                <textarea class="form-control" id="template-description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Felder</label>
                                <div id="template-fields">
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-field-btn">
                                    <i class="bi bi-plus"></i> Feld hinzufügen
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info" id="test-btn" onclick="alert('Test button works!'); return false;">Test</button>
                        <button type="button" class="btn btn-secondary" id="cancel-template-btn" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-primary" id="save-template-btn">Speichern</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    public static function get_templates() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $templates = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC"
        );
        
        return $templates;
    }

    public static function get_template($template_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $template = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $template_id)
        );
        
        return $template;
    }

    public static function save_template($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $insert_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'fields' => $data['fields'],
            'is_active' => (int) ($data['is_active'] ?? 1),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Ошибка сохранения в базу данных');
        }
        
        $template_id = $wpdb->insert_id;
        
        $api_client = new \NeoJobBoard\APIClientV2();
        $api_client->send_template($insert_data, $template_id, false);
        
        return $template_id;
    }

    public static function update_template($template_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $update_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'fields' => $data['fields'],
            'is_active' => (int) ($data['is_active'] ?? 1),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => (int) $template_id]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Ошибка обновления в базе данных');
        }
        
        $api_client = new \NeoJobBoard\APIClientV2();
        $api_client->send_template($update_data, $template_id, true);
        
        return true;
    }

    public static function toggle_status($template_id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $result = $wpdb->update(
            $table_name,
            [
                'is_active' => (int) $status,
                'updated_at' => current_time('mysql')
            ],
            ['id' => (int) $template_id]
        );
        
        if ($result !== false) {
            $api_client = new \NeoJobBoard\APIClientV2();
            $status_data = [
                'name' => $wpdb->get_var($wpdb->prepare("SELECT name FROM $table_name WHERE id = %d", $template_id)),
                'description' => $wpdb->get_var($wpdb->prepare("SELECT description FROM $table_name WHERE id = %d", $template_id)),
                'fields' => $wpdb->get_var($wpdb->prepare("SELECT fields FROM $table_name WHERE id = %d", $template_id)),
                'is_active' => (int) $status
            ];
            $api_client->send_template($status_data, $template_id, true);
        }
        
        return $result !== false;
    }

    public static function delete_template($template_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        $usage_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $applications_table WHERE template_id = %d",
                $template_id
            )
        );

        if ($usage_count > 0) {
            $wpdb->delete($applications_table, ['template_id' => (int)$template_id]);
        }

        $result = $wpdb->delete(
            $table_name,
            ['id' => (int) $template_id]
        );

        if ($result !== false) {
            $api_client = new \NeoJobBoard\APIClientV2();
            $api_client->delete_template($template_id);
        }

        return $result !== false;
    }

    public static function get_active_templates() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'neo_job_board_templates';
        
        $templates = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY name ASC"
        );
        
        return $templates;
    }
}