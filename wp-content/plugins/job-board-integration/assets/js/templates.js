(function($) {
    'use strict';

    window.JBITemplates = {
        fieldCounter: 0,
        
        fieldTypes: {
            'text': 'Text',
            'textarea': 'Mehrzeiliger Text',
            'email': 'E-Mail',
            'phone': 'Telefon',
            'select': 'Auswahlliste',
            'checkbox': 'Kontrollkästchen',
            'radio': 'Optionsfeld',
            'file': 'Dateien',
            'date': 'Datum',
            'number': 'Zahl',
            'url': 'URL',
            'wunschposition': 'Wunschposition (Speziell)',
            'bildung': 'Bildung (Speziell)',
            'berufserfahrung': 'Berufserfahrung (Speziell)',
            'sprachkenntnisse': 'Sprachkenntnisse (Speziell)',
            'fuehrerschein': 'Führerschein (Speziell)',
            'arbeitszeit': 'Arbeitszeit (Speziell)',
            'liste': 'Liste (Speziell)'
        },

        fixedFieldNames: {
            'wunschposition': 'Wunschposition',
            'bildung': 'Bildung',
            'berufserfahrung': 'Berufserfahrung',
            'sprachkenntnisse': 'Sprachkenntnisse',
            'fuehrerschein': 'Führerschein',
            'arbeitszeit': 'Arbeitszeit'
        },

        init: function() {
            this.loadTemplates();
            this.bindEvents();
        },

        bindEvents: function() {
            $('#add-field-btn').on('click', () => this.addField());
            $('#save-template-btn').on('click', () => this.saveTemplate());
            $('#template-form').on('submit', (e) => {
                e.preventDefault();
                this.saveTemplate();
            });
            
            // Автоматически добавляем поле Name при открытии модального окна
            $('#templateModal').on('show.bs.modal', () => {
                if (this.fieldCounter === 0) {
                    this.addNameField();
                }
            });
        },

        loadTemplates: function() {
            const $container = $('#templates-list');
            
            if (typeof jbiAjax === 'undefined') {
                $container.html('<div class="alert alert-danger">jbiAjax is not defined. Please check if scripts are loaded correctly.</div>');
                console.error('jbiAjax is undefined');
                return;
            }
            
            console.log('jbiAjax:', jbiAjax);
            
            $container.html(`
                <table id="templates-table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Beschreibung</th>
                            <th>Status</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                </table>
            `);

            $('#templates-table').DataTable({
                ajax: {
                    url: jbiAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'jbi_get_templates',
                        nonce: jbiAjax.nonce
                    },
                    dataSrc: function(json) {
                        console.log('DataTables response:', json);
                        if (json.success && json.data && Array.isArray(json.data.templates)) {
                            return json.data.templates;
                        }
                        console.error('Invalid response format:', json);
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', {xhr: xhr, error: error, thrown: thrown});
                        console.error('Response text:', xhr.responseText);
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { 
                        data: 'description',
                        render: function(data) {
                            return data || 'Keine Beschreibung';
                        }
                    },
                    { 
                        data: 'is_active',
                        render: function(data) {
                            if (data == 1) {
                                return '<span class="badge bg-success">Aktiv</span>';
                            } else {
                                return '<span class="badge bg-secondary">Inaktiv</span>';
                            }
                        }
                    },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString('de-DE') : '';
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let html = `<button class="btn btn-sm btn-info me-1" onclick="JBITemplates.viewTemplate(${row.id})" title="Ansehen"><i class="bi bi-eye"></i></button> `;
                            html += `<button class="btn btn-sm btn-primary me-1" onclick="JBITemplates.sendTemplate(${row.id})" title="Senden"><i class="bi bi-send"></i></button> `;
                            html += `<button class="btn btn-sm btn-danger" onclick="JBITemplates.deleteTemplate(${row.id})" title="Löschen"><i class="bi bi-trash"></i></button>`;
                            return html;
                        }
                    }
                ],
                language: {url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'},
                order: [[0, 'desc']]
            });
        },

        addNameField: function() {
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            
            const nameFieldData = {
                type: 'text',
                label: 'Name (Vor- und Nachname)',
                required: true,
                personal_data: true,
                filterable: false,
                options: '',
                isNameField: true
            };

            const html = this.generateFieldHtml(fieldId, nameFieldData);
            $('#template-fields').append(html);
            
            // Делаем поле Name неизменяемым
            $(`#${fieldId}_type`).prop('disabled', true);
            $(`#${fieldId}_label`).prop('readonly', true);
            $(`#${fieldId} .form-check input`).prop('disabled', true);
            $(`#${fieldId} .btn-outline-danger`).hide();
            
            this.handleTypeChange(fieldId, nameFieldData.type);
        },

        addField: function(fieldData = null) {
            this.fieldCounter++;
            const fieldId = 'field_' + this.fieldCounter;
            
            if (!fieldData) {
                fieldData = {
                    type: 'text',
                    label: '',
                    required: false,
                    personal_data: false,
                    filterable: false,
                    options: ''
                };
            }

            const html = this.generateFieldHtml(fieldId, fieldData);
            $('#template-fields').append(html);
            
            $(`#${fieldId}_type`).on('change', (e) => {
                this.handleTypeChange(fieldId, e.target.value);
            });
            
            this.handleTypeChange(fieldId, fieldData.type);
        },

        generateFieldHtml: function(fieldId, field) {
            const isSpecial = this.fixedFieldNames[field.type];
            const isNameField = field.isNameField || false;
            const label = isSpecial ? this.fixedFieldNames[field.type] : (field.label || '');
            
            const typeOptions = Object.entries(this.fieldTypes)
                .map(([value, text]) => `<option value="${value}" ${field.type === value ? 'selected' : ''}>${text}</option>`)
                .join('');

            return `
                <div class="card mb-3 template-field ${isSpecial ? 'border-success' : ''} ${isNameField ? 'border-primary' : ''}" id="${fieldId}">
                    <div class="card-header ${isSpecial ? 'bg-success text-white' : ''} ${isNameField ? 'bg-primary text-white' : ''}">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Feld #${this.fieldCounter}${isSpecial ? ' (Spezialfeld)' : ''}${isNameField ? ' (Name - Pflichtfeld)' : ''}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="JBITemplates.removeField('${fieldId}')" ${isNameField ? 'style="display:none;"' : ''}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Typ</label>
                                <select class="form-select" id="${fieldId}_type" name="fields[${fieldId}][type]" ${isNameField ? 'disabled' : ''}>
                                    ${typeOptions}
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Label *</label>
                                <input type="text" class="form-control" id="${fieldId}_label" name="fields[${fieldId}][label]" value="${label}" required ${isSpecial || isNameField ? 'readonly' : ''}>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Optionen</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[${fieldId}][required]" value="1" ${field.required ? 'checked' : ''} ${isNameField ? 'disabled' : ''}>
                                    <label class="form-check-label">Pflichtfeld</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[${fieldId}][personal_data]" value="1" ${field.personal_data ? 'checked' : ''} ${isNameField ? 'disabled' : ''}>
                                    <label class="form-check-label">Privat</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[${fieldId}][filterable]" value="1" ${field.filterable ? 'checked' : ''} ${isNameField ? 'disabled' : ''}>
                                    <label class="form-check-label">Filterbar</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 field-options" id="${fieldId}_options" style="display: none;">
                                <label class="form-label">Optionen (eine pro Zeile)</label>
                                <textarea class="form-control" name="fields[${fieldId}][options]" rows="3">${field.options || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        handleTypeChange: function(fieldId, type) {
            const $label = $(`#${fieldId}_label`);
            const $options = $(`#${fieldId}_options`);
            
            if (this.fixedFieldNames[type]) {
                $label.val(this.fixedFieldNames[type]).prop('readonly', true);
            } else {
                $label.prop('readonly', false);
            }
            
            if (['select', 'checkbox', 'radio', 'fuehrerschein', 'arbeitszeit'].includes(type)) {
                $options.show();
                
                if (type === 'fuehrerschein' && !$options.find('textarea').val()) {
                    $options.find('textarea').val('Klasse A (Motorräder)\nKlasse A1 (Leichtkrafträder)\nKlasse A2 (Mittelschwere Motorräder)\nKlasse B (PKW)\nKlasse BE (PKW mit Anhänger)\nKlasse C (LKW)\nKlasse CE (LKW mit Anhänger)\nKlasse C1 (Leichte LKW)\nKlasse C1E (Leichte LKW mit Anhänger)\nKlasse D (Busse)\nKlasse DE (Busse mit Anhänger)\nKlasse D1 (Kleinbusse)\nKlasse D1E (Kleinbusse mit Anhänger)\nKlasse T (Traktor)\nKlasse L (Moped/Mofa)\nKlasse M (Moped)');
                }
                
                if (type === 'arbeitszeit' && !$options.find('textarea').val()) {
                    $options.find('textarea').val('Vollzeit\nTeilzeit\nMinijob\nSchichtarbeit');
                }
            } else if (type === 'liste') {
                $options.hide();
            } else {
                $options.hide();
            }
        },

        removeField: function(fieldId) {
            const $field = $(`#${fieldId}`);
            if ($field.hasClass('border-primary')) {
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Das Name-Feld kann nicht gelöscht werden');
                } else {
                    alert('Das Name-Feld kann nicht gelöscht werden');
                }
                return;
            }
            
            const self = this;
            if (window.NeoDash && window.NeoDash.confirm) {
                NeoDash.confirm(jbiAjax.strings.confirm_delete, {
                    type: 'warning',
                    title: 'Bestätigung des Löschens',
                    confirmText: 'Löschen',
                    cancelText: 'Abbrechen'
                }).then((confirmed) => {
                    if (confirmed) {
                        $(`#${fieldId}`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                });
            } else {
                if (confirm(jbiAjax.strings.confirm_delete)) {
                    $(`#${fieldId}`).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            }
        },

        saveTemplate: function() {
            const form = $('#template-form')[0];
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const data = {
                action: 'jbi_save_template',
                nonce: jbiAjax.nonce,
                name: formData.get('name'),
                description: formData.get('description'),
                fields: this.getFieldsData()
            };

            $.post(jbiAjax.ajaxurl, data, (response) => {
                if (response.success) {
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                    $('#templateModal').modal('hide');
                    $('#templates-table').DataTable().ajax.reload();
                    this.resetForm();
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError(response.data.message || jbiAjax.strings.error);
                    } else {
                        alert(response.data.message || jbiAjax.strings.error);
                    }
                }
            });
        },

        getFieldsData: function() {
            const fields = [];
            const self = this;
            
            $('.template-field').each(function() {
                const $field = $(this);
                const fieldId = $field.attr('id');
                const isNameField = $field.hasClass('border-primary');
                
                let type = $field.find(`[name="fields[${fieldId}][type]"]`).val();
                let label = $field.find(`[name="fields[${fieldId}][label]"]`).val();
                let required = $field.find(`[name="fields[${fieldId}][required]"]`).is(':checked');
                let personal_data = $field.find(`[name="fields[${fieldId}][personal_data]"]`).is(':checked');
                let filterable = $field.find(`[name="fields[${fieldId}][filterable]"]`).is(':checked');
                
                if (isNameField) {
                    type = 'text';
                    label = 'Name (Vor- und Nachname)';
                    required = true;
                    personal_data = true;
                    filterable = false;
                }
                
                let field_name_value = '';
                if (isNameField) {
                    field_name_value = 'name';
                } else if (self.fixedFieldNames && self.fixedFieldNames[type]) {
                    const hiddenFieldName = $field.find(`input[name="fields[${fieldId}][field_name]"]`);
                    field_name_value = hiddenFieldName.length > 0 ? hiddenFieldName.val() : type;
                }
                
                fields.push({
                    type: type,
                    label: label,
                    required: required,
                    personal_data: personal_data,
                    filterable: filterable,
                    options: $field.find(`[name="fields[${fieldId}][options]"]`).val(),
                    name: field_name_value,
                    field_name: field_name_value
                });
            });
            
            return fields;
        },

        resetForm: function() {
            $('#template-form')[0].reset();
            $('#template-fields').empty();
            this.fieldCounter = 0;
            // Автоматически добавляем поле Name при сбросе формы
            this.addNameField();
        },

        sendTemplate: function(templateId) {
            const self = this;
            if (window.NeoDash && window.NeoDash.confirm) {
                NeoDash.confirm('Möchten Sie diese Vorlage an die externe API senden?', {
                    type: 'info',
                    title: 'Senden des Templates',
                    confirmText: 'Senden',
                    cancelText: 'Abbrechen'
                }).then((confirmed) => {
                    if (!confirmed) return;

                    $.post(jbiAjax.ajaxurl, {
                        action: 'jbi_send_template',
                        nonce: jbiAjax.nonce,
                        template_id: templateId
                    }, (response) => {
                        if (response.success) {
                            if (window.NeoDash && window.NeoDash.toastSuccess) {
                                NeoDash.toastSuccess(response.data.message);
                            } else {
                                alert(response.data.message);
                            }
                            $('#templates-table').DataTable().ajax.reload();
                        } else {
                            if (window.NeoDash && window.NeoDash.toastError) {
                                NeoDash.toastError(response.data.message || jbiAjax.strings.error);
                            } else {
                                alert(response.data.message || jbiAjax.strings.error);
                            }
                        }
                    });
                });
            } else {
                if (!confirm('Möchten Sie diese Vorlage an die externe API senden?')) {
                    return;
                }

                $.post(jbiAjax.ajaxurl, {
                    action: 'jbi_send_template',
                    nonce: jbiAjax.nonce,
                    template_id: templateId
                }, (response) => {
                    if (response.success) {
                        alert(response.data.message);
                        $('#templates-table').DataTable().ajax.reload();
                    } else {
                        alert(response.data.message || jbiAjax.strings.error);
                    }
                });
            }
        },

        viewTemplate: function(templateId) {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_get_template',
                nonce: jbiAjax.nonce,
                template_id: templateId
            }, (response) => {
                if (response.success && response.data && response.data.template) {
                    const template = response.data.template;
                    const fields = template.fields || [];
                    
                    let fieldsHtml = '';
                    if (fields.length === 0) {
                        fieldsHtml = '<p class="text-muted">Keine Felder definiert</p>';
                    } else {
                        fields.forEach((field, index) => {
                            const fieldType = this.fieldTypes[field.type] || field.type;
                            const fieldName = field.name || field.field_name || '-';
                            
                            fieldsHtml += `
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Feld #${index + 1}</strong>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="mb-2">
                                                    <strong>Label:</strong> ${field.label || '-'}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Typ:</strong> ${fieldType}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Feldname:</strong> <code>${fieldName}</code>
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Eigenschaften:</strong>
                                                    <ul class="list-unstyled mb-0">
                                                        <li><i class="bi ${field.required ? 'bi-check-circle text-success' : 'bi-x-circle text-secondary'}"></i> Pflichtfeld</li>
                                                        <li><i class="bi ${field.personal_data ? 'bi-check-circle text-warning' : 'bi-x-circle text-secondary'}"></i> Privat</li>
                                                        <li><i class="bi ${field.filterable ? 'bi-check-circle text-info' : 'bi-x-circle text-secondary'}"></i> Filterbar</li>
                                                    </ul>
                                                </div>
                                                ${field.options ? `
                                                <div class="mb-2">
                                                    <strong>Optionen:</strong>
                                                    <ul class="list-unstyled mb-0">
                                                        ${field.options.split('\n').filter(opt => opt.trim()).map(opt => `<li>${opt.trim()}</li>`).join('')}
                                                    </ul>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    const modalHtml = `
                        <div class="modal fade" id="viewTemplateModal" tabindex="-1" aria-labelledby="viewTemplateModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title" id="viewTemplateModalLabel">
                                            <i class="bi bi-eye me-2"></i>Vorlage: ${template.name || 'Unbekannt'}
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                                        <div class="mb-3">
                                            <strong>Name:</strong>
                                            <p>${template.name || '-'}</p>
                                        </div>
                                        ${template.description ? `
                                        <div class="mb-3">
                                            <strong>Beschreibung:</strong>
                                            <p>${template.description}</p>
                                        </div>
                                        ` : ''}
                                        <div class="mb-3">
                                            <strong>Status:</strong>
                                            <p>
                                                ${template.is_active == 1 ? '<span class="badge bg-success">Aktiv</span>' : '<span class="badge bg-secondary">Inaktiv</span>'}
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Erstellt am:</strong>
                                            <p>${template.created_at ? new Date(template.created_at).toLocaleString('de-DE') : '-'}</p>
                                        </div>
                                        <div class="mb-3">
                                            <strong>Felder (${fields.length}):</strong>
                                            <div class="mt-2">
                                                ${fieldsHtml}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    const existingModal = document.getElementById('viewTemplateModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modalElement = document.getElementById('viewTemplateModal');
                    const bsModal = new bootstrap.Modal(modalElement);
                    
                    modalElement.addEventListener('hidden.bs.modal', () => {
                        modalElement.remove();
                    }, { once: true });
                    
                    bsModal.show();
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError(response.data?.message || 'Fehler beim Laden der Vorlage');
                    } else {
                        alert(response.data?.message || 'Fehler beim Laden der Vorlage');
                    }
                }
            });
        },

        deleteTemplate: function(templateId) {
            const self = this;
            if (window.NeoDash && window.NeoDash.confirm) {
                NeoDash.confirm(jbiAjax.strings.confirm_delete, {
                    type: 'danger',
                    title: 'Bestätigung des Löschens',
                    confirmText: 'Löschen',
                    cancelText: 'Abbrechen'
                }).then((confirmed) => {
                    if (!confirmed) return;

                    $.post(jbiAjax.ajaxurl, {
                        action: 'jbi_delete_template',
                        nonce: jbiAjax.nonce,
                        template_id: templateId
                    }, (response) => {
                        if (response.success) {
                            if (window.NeoDash && window.NeoDash.toastSuccess) {
                                NeoDash.toastSuccess(response.data.message);
                            } else {
                                alert(response.data.message);
                            }
                            $('#templates-table').DataTable().ajax.reload();
                        } else {
                            if (window.NeoDash && window.NeoDash.toastError) {
                                NeoDash.toastError(response.data.message || jbiAjax.strings.error);
                            } else {
                                alert(response.data.message || jbiAjax.strings.error);
                            }
                        }
                    });
                });
            } else {
                if (!confirm(jbiAjax.strings.confirm_delete)) {
                    return;
                }

                $.post(jbiAjax.ajaxurl, {
                    action: 'jbi_delete_template',
                    nonce: jbiAjax.nonce,
                    template_id: templateId
                }, (response) => {
                    if (response.success) {
                        alert(response.data.message);
                        $('#templates-table').DataTable().ajax.reload();
                    } else {
                        alert(response.data.message || jbiAjax.strings.error);
                    }
                });
            }
        }
    };

    window.openAddTemplateModal = function() {
        JBITemplates.resetForm();
        $('#templateModal').modal('show');
    };

    $(document).ready(function() {
        if ($('#templates-list').length) {
            JBITemplates.init();
        }
    });

})(jQuery);

