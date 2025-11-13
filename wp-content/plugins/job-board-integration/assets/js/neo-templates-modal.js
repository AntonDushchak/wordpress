window.NeoTemplatesModal = (function($) {
    'use strict';
    
    let modalInstance = null;
    let isSubmitting = false;
    
    return {
        init: function() {
            $(document).on('submit', 'form', function(e) {
                if (this.id === 'template-form') {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
            });
            
            
            this.bindEvents();
            this.initModal();
        },
        
        initModal: function() {
            const modalElement = document.getElementById('templateModal');
            if (!modalElement) return;
            
            modalInstance = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });
            
            modalElement.addEventListener('hidden.bs.modal', () => {
                this.resetModal();
            });
        },
        
        bindEvents: function() {
            const self = this;
            
            $(document).on('click', '#add-template-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openAddTemplateModal();
                return false;
            });
            
            $(document).on('click', '#add-field-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.NeoTemplatesFields) {
                    window.NeoTemplatesFields.addTemplateField();
                }
                return false;
            });
            
            $(document).on('submit', '#template-form', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            });
            
            $(document).on('click', '#save-template-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                $('#template-form').off('submit');
                
                self.saveTemplate();
                return false;
            });

            $(document).on('click', '#cancel-template-btn', () => {
                this.closeModal();
            });
        },
        
        openAddTemplateModal: function() {
            this.resetModal();
            this.setTitle('Neue Vorlage erstellen');
            
            const self = this;
            setTimeout(() => {
                if (window.NeoTemplatesFields) {
                    if (typeof window.NeoTemplatesFields.ensureNameField === 'function') {
                        window.NeoTemplatesFields.ensureNameField();
                    } else {
                        self.addNameFieldFallback();
                    }
                } else {
                    console.error('NeoTemplatesFields not available, using fallback');
                    self.addNameFieldFallback();
                }
            }, 200);
            
            setTimeout(function() {
                const saveBtn = $('#save-template-btn');
            }, 100);
            
            this.openModal();
        },
        
        addNameFieldFallback: function() {
            const fieldHtml = `
                <div class="card mb-3 template-field border-primary" id="field_0" data-field-name="name">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Name (Vor- und Nachname) (Systemfeld)</h6>
                        </div>
                        <small>Dieses Feld ist erforderlich und kann nicht entfernt werden</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Typ</label>
                                <select class="form-select form-control" name="fields[field_0][type]" disabled>
                                    <option value="text" selected>Text</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Label *</label>
                                <input type="text" class="form-control" name="fields[field_0][label]" value="Name (Vor- und Nachname)" required readonly>
                                <input type="hidden" name="fields[field_0][field_name]" value="name">
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Erforderlich</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="fields[field_0][required]" value="1" checked disabled>
                                            <label class="form-check-label">Ja</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Persönliche Daten</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="fields[field_0][personal_data]" value="1" checked disabled>
                                            <label class="form-check-label">Nicht an API</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#template-fields').prepend(fieldHtml);
        },
        
        openModal: function() {
            if (modalInstance) {
                modalInstance.show();
            }
        },
        
        closeModal: function() {
            if (modalInstance) {
                modalInstance.hide();
            }
        },
        
        setTitle: function(title) {
            $('#templateModalLabel').text(title);
        },
        
        resetModal: function() {
            $('#template-form')[0].reset();
            
            $('input[name="template_id"]').remove();
            
            $('#template-fields').empty();
            if (window.NeoTemplatesFields && window.NeoTemplatesFields.clearAllFields) {
                window.NeoTemplatesFields.clearAllFields();
            }
            
            $('.is-invalid').removeClass('is-invalid');
            
            this.resetSaveButton();
        },
        
        saveTemplate: function() {
            
            if (isSubmitting) {
                return;
            }
            
            if (window.NeoTemplatesFields && !window.NeoTemplatesFields.validateFields()) {
                return;
            }
            
            const name = $('#template-name').val().trim();
            if (!name) {
                $('#template-name').addClass('is-invalid');
                if (window.NeoDash && window.NeoDash.toastWarning) {
                    NeoDash.toastWarning('Bitte geben Sie einen Vorlagennamen ein.');
                } else {
                    alert('Bitte geben Sie einen Vorlagennamen ein.');
                }
                return;
            }
            
            this.setSaveButtonLoading(true);
            isSubmitting = true;
            
            const formData = {
                action: 'neo_job_board_save_template',
                nonce: neoJobBoardAjax.nonce,
                name: name,
                description: $('#template-description').val(),
                is_active: 1
            };
            
            const templateId = $('input[name="template_id"]').val();
            if (templateId) {
                formData.template_id = templateId;
            }
            
            if (window.NeoTemplatesFields) {
                const fieldsData = window.NeoTemplatesFields.getFieldsData();
                
                if (fieldsData && fieldsData.length > 0) {
                    fieldsData.forEach((field, index) => {
                        formData[`field_${index}_type`] = field.type;
                        formData[`field_${index}_label`] = field.label;
                        formData[`field_${index}_required`] = field.required ? 1 : 0;
                        formData[`field_${index}_personal_data`] = field.personal_data ? 1 : 0;
                        formData[`field_${index}_options`] = field.options || '';
                    });
                    formData.fields_count = fieldsData.length;
                } else {
                    formData.fields_count = 0;
                }
            } else {
                formData.fields_count = 0;
            }
            
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    let parsedResponse = response;
                    if (typeof response === 'string') {
                        try {
                            parsedResponse = JSON.parse(response);
                        } catch (e) {
                            if (window.NeoDash && window.NeoDash.toastError) {
                                NeoDash.toastError('Der Server hat einen ungültigen Antwortformat zurückgegeben: ' + response.substring(0, 200));
                            } else {
                                alert('Der Server hat einen ungültigen Antwortformat zurückgegeben: ' + response.substring(0, 200));
                            }
                            return;
                        }
                    }
                    
                    if (parsedResponse.success) {
                        if (window.NeoDash && window.NeoDash.toastSuccess) {
                            NeoDash.toastSuccess('Vorlage erfolgreich gespeichert!');
                        } else {
                            alert('Vorlage erfolgreich gespeichert!');
                        }
                        this.closeModal();
                        
                        setTimeout(function() {
                            if (window.reloadTemplatesList) {
                                window.reloadTemplatesList();
                            } else if (window.NeoTemplatesCore && window.NeoTemplatesCore.loadTemplates) {
                                window.NeoTemplatesCore.loadTemplates();
                            } else {
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
                                            $('#templates-list').html('<div class="alert alert-success">Найдено шаблонов: ' + templates.length + ' (включая новый). Последнее обновление: ' + (response.data.timestamp || 'неизвестно') + '</div>');
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                    }
                                });
                            }
                        }, 500);
                    } else {
                        if (window.NeoDash && window.NeoDash.toastError) {
                            NeoDash.toastError('Fehler beim Speichern: ' + (parsedResponse.data || 'Unbekannter Fehler'));
                        } else {
                            alert('Fehler beim Speichern: ' + (parsedResponse.data || 'Unbekannter Fehler'));
                        }
                    }
                },
                error: (xhr, status, error) => {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError('AJAX Fehler beim Speichern der Vorlage: ' + error);
                    } else {
                        alert('AJAX Fehler beim Speichern der Vorlage: ' + error);
                    }
                },
                complete: () => {
                    this.setSaveButtonLoading(false);
                    isSubmitting = false;
                }
            });
        },
        
        setSaveButtonLoading: function(loading) {
            const $btn = $('#save-template-btn');
            
            if (loading) {
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Speichern...');
            } else {
                $btn.prop('disabled', false);
                $btn.html('<i class="bi bi-check-lg me-2"></i>Speichern');
            }
        },
        
        resetSaveButton: function() {
            const $btn = $('#save-template-btn');
            $btn.prop('disabled', false);
            $btn.html('<i class="bi bi-check-lg me-2"></i>Speichern');
        }
    };

})(jQuery);

window.openAddTemplateModal = function() {
    if (window.NeoTemplatesModal) {
        window.NeoTemplatesModal.openAddTemplateModal();
    }
};