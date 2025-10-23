/**
 * Neo Umfrage - Работа с шаблонами
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoUmfrageTemplates = {
        init: function() {
            this.initializeModals();
        },

        initializeModals: function() {
            if (window.NeoUmfrageModals && NeoUmfrageModals.createModals) {
                NeoUmfrageModals.createModals();
            }
        },
        
        loadTemplates: function () {
            this.init();        
            this.initTemplatesDataTable();
        },

        loadTemplatesForFilter: function () {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce,
                    show_only_active: 1
                },
                success: function (response) {
                    if (response.success) {
                        const $filter = $('#filter-template');
                        if ($filter.length) {
                            $filter.find('option:not(:first)').remove();
                            response.data.forEach(template => {
                                $filter.append(`<option value="${template.id}">${template.name}</option>`);
                            });
                        }
                    }
                }
            });
        },

        loadTemplatesForSelect: function (selector, callback) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce,
                    show_only_active: 1
                },
                success: function (response) {
                    if (response.success) {
                        const $select = $(selector);
                        $select.empty();
                        
                        if (response.data && response.data.length > 0) {
                            $select.append('<option value="">Vorlage auswählen</option>');
                            response.data.forEach(template => {
                                $select.append(`<option value="${template.id}">${template.name}</option>`);
                            });
                        } else {
                            $select.append('<option value="" disabled>Keine Vorlagen verfügbar</option>');
                        }

                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX-Fehler beim Laden der Vorlagen:', error);
                }
            });
        },

        loadTemplateFieldsForEdit: function (templateId, existingFields) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template_fields',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function (response) {
                    if (response && response.success) {
                        const fields = (response.data && response.data.fields) ? response.data.fields : [];
                        NeoUmfrageTemplates.renderTemplateFieldsForEdit(fields, existingFields);
                    }
                }
            });
        },

        renderTemplateFieldsForEdit: function (templateFields, existingFields) {
            const $container = $('#template-fields-container');
            $container.empty();

            templateFields.forEach(function (field, index) {
                let fieldValue = '';
                if (existingFields) {
                    const existingField = existingFields.find(f => f.label === field.label);
                    if (existingField) {
                        fieldValue = existingField.value;
                    }
                }

                const fieldHtml = NeoUmfrageModals.renderSurveyField(field, fieldValue);
                $container.append(fieldHtml);
            });
        },

        initTemplatesDataTable: function () {
            const $container = $('#templates-list');
            
            $container.html(`
                <div class="neo-umfrage-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">
                    <div>
                        <label class="neo-umfrage-label">Filter nach Status:</label>
                        <select id="filter-status" class="neo-umfrage-select" style="margin-left: 10px;">
                            <option value="">Alle Status</option>
                            <option value="1">Aktiv</option>
                            <option value="0">Inaktiv</option>
                        </select>
                    </div>
                </div>
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

            const table = $('#templates-table').DataTable({
                ajax: {
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'neo_umfrage_get_templates';
                        d.nonce = neoUmfrageAjax.nonce;
                        d.show_only_active = $('#filter-status').val() === '' ? 'all' : $('#filter-status').val();
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { 
                        data: 'description',
                        render: function(data, type, row) {
                            return data || 'Keine Beschreibung';
                        }
                    },
                    { 
                        data: 'is_active',
                        render: function(data, type, row) {
                            const statusText = data == 1 ? 'Aktiv' : 'Inaktiv';
                            const statusClass = data == 1 ? 'status-active' : 'status-inactive';
                            return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                        }
                    },
                    { 
                        data: 'created_at',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString('de-DE', {
                                    timeZone: 'Europe/Berlin',
                                    year: 'numeric',
                                    month: '2-digit',
                                    day: '2-digit'
                                });
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'actions',                        
                        orderable: false, 
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = '';
                            if (NeoUmfrage.canEdit(row.user_id)) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="editTemplate(${row.id}, ${row.user_id || 0})">Bearbeiten</button> `;
                            }
                            if (row.is_active == 1) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-warning" onclick="deactivateTemplate(${row.id})">Deaktivieren</button> `;
                            } else {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-success" onclick="activateTemplate(${row.id})">Aktivieren</button> `;
                            }
                            if (NeoUmfrage.canDelete(row.user_id)) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-danger" onclick="deleteTemplateWithSurveys(${row.id})">Löschen</button>`;
                            }
                            return actions;
                        }
                    }
                ],
                language: {url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'},
                processing: true,
                serverSide: false,
                responsive: true,
                searching: true,
                order: [[0, 'desc']]
            });

            $('#filter-status').on('change', function() {
                table.ajax.reload();
            });
        },

        renderTemplatesList: function (templates) {
            console.log('renderTemplatesList вызвана, но используется DataTables');
        },

        deactivateTemplate: function (templateId) {
            if (confirm('Sind Sie sicher, dass Sie diese Vorlage deaktivieren möchten? Die Vorlage wird nicht in Listen angezeigt, aber bestehende Umfragen bleiben erhalten.')) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_deactivate_template',
                        nonce: neoUmfrageAjax.nonce,
                        template_id: templateId
                    },
                    success: function (response) {
                        if (response.success) {
                            NeoUmfrage.showMessage('success', response.data.message);
                            if ($.fn.DataTable && $('#templates-table').length) {
                                $('#templates-table').DataTable().ajax.reload();
                            } else {
                                NeoUmfrageTemplates.loadTemplates();
                            }
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        },

        activateTemplate: function (templateId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_toggle_template_status',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId,
                    is_active: 1
                },
                success: function (response) {
                    if (response.success) {
                        NeoUmfrage.showMessage('success', response.data.message);
                        if ($.fn.DataTable && $('#templates-table').length) {
                            $('#templates-table').DataTable().ajax.reload();
                        } else {
                            NeoUmfrageTemplates.loadTemplates();
                        }
                    } else {
                        NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                    }
                }
            });
        },

        deleteTemplateWithSurveys: function (templateId) {
            const confirmMessage = 'WARNUNG: Diese Aktion löscht die Vorlage und ALLE zugehörigen Umfragen unwiderruflich!\n\n' +
                                 'Sind Sie absolut sicher, dass Sie fortfahren möchten?\n\n' +
                                 'Geben Sie "LÖSCHEN" ein, um zu bestätigen:';
            
            const userInput = prompt(confirmMessage);
            
            if (userInput === 'LÖSCHEN') {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_template_with_surveys',
                        nonce: neoUmfrageAjax.nonce,
                        template_id: templateId
                    },
                    success: function (response) {
                        if (response.success) {
                            NeoUmfrage.showMessage('success', response.data.message);
                            if ($.fn.DataTable && $('#templates-table').length) {
                                $('#templates-table').DataTable().ajax.reload();
                            } else {
                                NeoUmfrageTemplates.loadTemplates();
                            }
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            } else if (userInput !== null) {
                NeoUmfrage.showMessage('error', 'Löschvorgang abgebrochen. Bitte geben Sie "LÖSCHEN" ein, um zu bestätigen.');
            }
        },

        editTemplate: function (templateId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function (response) {
                    if (response && response.success) {
                        NeoUmfrageTemplates.openEditTemplateModal(response.data.template);
                    } else {
                        NeoUmfrage.showMessage('error', 'Fehler beim Laden der Vorlage');
                    }
                },
                error: function () {
                    NeoUmfrage.showMessage('error', 'Fehler beim Laden der Vorlage');
                }
            });
        },

        openEditTemplateModal: function (template) {
            $('#template-form input[name="name"]').val(template.name);
            $('#template-form textarea[name="description"]').val(template.description);
            
            if (!$('#template-form input[name="template_id"]').length) {
                $('#template-form').append('<input type="hidden" name="template_id" value="' + template.id + '">');
            } else {
                $('#template-form input[name="template_id"]').val(template.id);
            }
            
            $('.template-field').remove();
            
            if (template.fields && template.fields.length > 0) {
                template.fields.forEach(function(field, index) {
                    NeoUmfrageTemplates.addTemplateField(field, index);
                });
            }
            
            NeoUmfrageModals.openAddTemplateModal();
            
            $('.neo-umfrage-modal-title').text('Vorlage bearbeiten');
        },

        addTemplateField: function (field, index) {
            const $fieldsContainer = $('#template-fields');
            
            const fieldHtml = `
                <div class="template-field" data-field-index="${index}">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                        <input type="text" class="neo-umfrage-input" name="fields[${index}][label]" placeholder="Feldname" value="${field.label}" required>
                        <select class="neo-umfrage-select field-type-select" name="fields[${index}][type]">
                            <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${field.type === 'number' ? 'selected' : ''}>Zahl</option>
                            <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                            <option value="select" ${field.type === 'select' ? 'selected' : ''}>Auswahl</option>
                            <option value="radio" ${field.type === 'radio' ? 'selected' : ''}>Radio</option>
                            <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                        </select>
                        <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                            <input type="checkbox" name="fields[${index}][required]" value="1" ${field.required ? 'checked' : ''}>
                            Pflichtfeld
                        </label>
                        <button type="button" class="neo-umfrage-button neo-umfrage-button-danger remove-field-btn">Löschen</button>
                    </div>
                    <div class="field-options" style="display: ${field.type === 'select' || field.type === 'radio' || field.type === 'checkbox' ? 'block' : 'none'};">
                        <label class="neo-umfrage-label">Optionen (eine pro Zeile):</label>
                        <textarea class="neo-umfrage-textarea" name="fields[${index}][options]" placeholder="Option 1&#10;Option 2&#10;Option 3">${field.options ? field.options.join('\n') : ''}</textarea>
                    </div>
                </div>
            `;
            
            $fieldsContainer.append(fieldHtml);
        },

        refreshTemplatesTable: function() {
            if ($.fn.DataTable && $('#templates-table').length) {
                $('#templates-table').DataTable().ajax.reload();
            } else {
                this.loadTemplates();
            }
        }
    };

    window.deactivateTemplate = function(templateId) {
        NeoUmfrageTemplates.deactivateTemplate(templateId);
    };

    window.activateTemplate = function(templateId) {
        NeoUmfrageTemplates.activateTemplate(templateId);
    };

    window.deleteTemplateWithSurveys = function(templateId) {
        NeoUmfrageTemplates.deleteTemplateWithSurveys(templateId);
    };

    window.editTemplate = function(templateId) {
        NeoUmfrageTemplates.editTemplate(templateId);
    };

})(jQuery);