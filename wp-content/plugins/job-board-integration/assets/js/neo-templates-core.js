window.NeoTemplatesCore = (function($) {
    'use strict';
        
    return {
        init: function() {
            if (typeof neoJobBoardAjax === 'undefined') {
                return;
            }
            this.loadTemplates();
        },

        loadTemplates: function() {
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_job_board_get_templates',
                    nonce: neoJobBoardAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoTemplatesCore.renderTemplatesList(response.data);
                    }
                },
                error: function(xhr, status, error) {
                }
            });
        },
        
        renderTemplatesList: function(templates) {
            
            const $container = $('#templates-list');
            
            if (templates.length === 0) {
                $container.html('<div class="alert alert-info">Keine Vorlagen gefunden. Erstellen Sie die erste Vorlage.</div>');
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Beschreibung</th>
                                <th>Felder</th>
                                <th>Status</th>
                                <th>API Sync</th>
                                <th>Erstellt</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (!Array.isArray(templates)) {
                console.error('Templates is not an array:', templates);
                templates = [];
            }
            
            templates.forEach(function(template) {
                
                let fields = [];
                try {
                    fields = template.fields ? JSON.parse(template.fields) : [];
                } catch (e) {
                    fields = [];
                }
                const statusBadge = template.is_active == 1 
                    ? '<span class="badge bg-success">Aktiv</span>' 
                    : '<span class="badge bg-secondary">Inaktiv</span>';
                
                let syncBadge = '<span class="badge bg-secondary"><i class="bi bi-question-circle"></i> Неизвестно</span>';
                if (template.sync_status) {
                    if (template.sync_status === 'synchronized' && template.exists_on_site) {
                        syncBadge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Synchronisiert</span>';
                    } else if (template.sync_status === 'not_synchronized' && !template.exists_on_site) {
                        syncBadge = '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Nicht synchronisiert</span>';
                    }
                }
                
                html += `
                    <tr>
                        <td><strong>${template.name}</strong></td>
                        <td>${template.description || '-'}</td>
                        <td>${fields.length}</td>
                        <td>${statusBadge}</td>
                        <td>${syncBadge}</td>
                        <td>${new Date(template.created_at).toLocaleDateString('de-DE')}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editTemplate(${template.id})" title="Bearbeiten">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="toggleTemplateStatus(${template.id}, ${template.is_active == 1 ? 0 : 1})" title="${template.is_active == 1 ? 'Deaktivieren' : 'Aktivieren'}">
                                    <i class="bi bi-${template.is_active == 1 ? 'pause' : 'play'}"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteTemplate(${template.id})" title="Löschen">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            $container.html(html);
        },

        editTemplate: function(templateId) {
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_job_board_get_template',
                    nonce: neoJobBoardAjax.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        const template = response.data;
                        
                        $('#template-form')[0].reset();
                        $('#template-fields').empty();
                        
                        $('input[name="name"]').val(template.name);
                        $('textarea[name="description"]').val(template.description);
                        $('select[name="is_active"]').val(template.is_active);
                        
                        if (!$('input[name="template_id"]').length) {
                            $('#template-form').append('<input type="hidden" name="template_id" value="' + template.id + '">');
                        } else {
                            $('input[name="template_id"]').val(template.id);
                        }
                        
                        if (template.fields && window.NeoTemplatesFields) {
                            try {
                                const fields = JSON.parse(template.fields);
                                if (Array.isArray(fields)) {
                                    fields.forEach(function(fieldData, index) {
                                        window.NeoTemplatesFields.addTemplateFieldWithData(fieldData, index);
                                    });
                                }
                            } catch (e) {
                            }
                        }
                        
                        if (window.NeoTemplatesModal) {
                            window.NeoTemplatesModal.setTitle('Vorlage bearbeiten');
                            window.NeoTemplatesModal.openModal();
                        }
                    } else {
                        this.showMessage('error', response.data || 'Fehler beim Laden der Vorlage.');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    this.showMessage('error', 'Fehler beim Laden der Vorlage.');
                }.bind(this)
            });
        },

        toggleTemplateStatus: function(templateId, status) {
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_job_board_toggle_template_status',
                    nonce: neoJobBoardAjax.nonce,
                    template_id: templateId,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        NeoTemplatesCore.showMessage('success', response.data.message || 'Status erfolgreich geändert.');
                        NeoTemplatesCore.loadTemplates();
                    } else {
                        NeoTemplatesCore.showMessage('error', response.data || 'Fehler beim Ändern des Status.');
                    }
                },
                error: function(xhr, status, error) {
                    NeoTemplatesCore.showMessage('error', 'Fehler beim Ändern des Status.');
                }
            });
        },

        deleteTemplate: function(templateId) {
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_job_board_delete_template',
                    nonce: neoJobBoardAjax.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response.success) {
                        NeoTemplatesCore.showMessage('success', response.data.message || 'Vorlage erfolgreich gelöscht.');
                        NeoTemplatesCore.loadTemplates();
                    } else if (response.data && response.data.usage_count) {
                        NeoTemplatesCore.showCascadeDeleteWarning(templateId, response.data.usage_count);
                    } else {
                        NeoTemplatesCore.showMessage('error', response.data.message || response.data || 'Fehler beim Löschen der Vorlage.');
                    }
                },
                error: function(xhr, status, error) {
                    NeoTemplatesCore.showMessage('error', 'Fehler beim Löschen der Vorlage.');
                }
            });
        },

        showCascadeDeleteWarning: function(templateId, usageCount) {
            $('#neoCascadeDeleteModal').remove();
            const modalHtml = `
            <div class="modal fade" id="neoCascadeDeleteModal" tabindex="-1" role="dialog" aria-labelledby="neoCascadeDeleteModalLabel" aria-hidden="true">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="neoCascadeDeleteModalLabel">Warnung: Vorlage löschen</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body">
                    <p>Mit diesem Vorgang werden auch alle zugehörigen Bewerbungen (<b>${usageCount}</b>) <b>unwiderruflich gelöscht</b>.<br> Möchten Sie wirklich fortfahren?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-danger" id="neoCascadeDeleteConfirmBtn">Ja, endgültig löschen</button>
                  </div>
                </div>
              </div>
            </div>`;
            $('body').append(modalHtml);
            $('#neoCascadeDeleteModal').modal('show');
            $('#neoCascadeDeleteConfirmBtn').on('click', function() {
                $('#neoCascadeDeleteModal').modal('hide');
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_delete_template',
                        nonce: neoJobBoardAjax.nonce,
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            NeoTemplatesCore.showMessage('success', response.data.message || 'Vorlage und alle zugehörigen Bewerbungen wurden gelöscht.');
                            NeoTemplatesCore.loadTemplates();
                        } else {
                            NeoTemplatesCore.showMessage('error', response.data.message || response.data || 'Fehler beim Löschen der Vorlage.');
                        }
                    },
                    error: function(xhr, status, error) {
                        NeoTemplatesCore.showMessage('error', 'Fehler beim Löschen der Vorlage.');
                    }
                });
            });
        },
        

        showMessage: function(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            $('#message-container').html(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    };

})(jQuery);

window.editTemplate = function(templateId) {
    window.NeoTemplatesCore.editTemplate(templateId);
};

window.toggleTemplateStatus = function(templateId, status) {
    window.NeoTemplatesCore.toggleTemplateStatus(templateId, status);
};

window.deleteTemplate = function(templateId) {
    window.NeoTemplatesCore.deleteTemplate(templateId);
};