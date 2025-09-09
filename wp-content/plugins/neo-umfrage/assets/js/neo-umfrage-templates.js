/**
 * Neo Umfrage - Работа с шаблонами
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoUmfrageTemplates = {
        
        // Инициализация
        init: function() {
            this.initializeModals();
        },

        // Инициализация модальных окон для шаблонов
        initializeModals: function() {
            // Создаем модальные окна если они еще не созданы
            if (window.NeoUmfrageModals && NeoUmfrageModals.createModals) {
                NeoUmfrageModals.createModals();
            }
        },
        
        // Загрузка шаблонов для админки (все шаблоны)
        loadTemplates: function () {
            // Инициализируем модальные окна при первой загрузке
            this.init();
            
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce,
                    show_only_active: 0 // Показываем все шаблоны в админке
                },
                success: function (response) {
                    if (response.success) {
                        NeoUmfrageTemplates.renderTemplatesList(response.data);
                    }
                }
            });
        },

        // Загрузка шаблонов для фильтра (только активные)
        loadTemplatesForFilter: function () {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce,
                    show_only_active: 1 // Показываем только активные шаблоны для фильтра
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

        // Vorlagen für Select laden (nur aktive)
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

        // Vorlagenfelder für Bearbeitung laden
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

        // Рендер полей шаблона для редактирования
        renderTemplateFieldsForEdit: function (templateFields, existingFields) {
            const $container = $('#template-fields-container');
            $container.empty();

            templateFields.forEach(function (field, index) {
                // Находим значение из существующих данных
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

        // Отображение списка шаблонов
        renderTemplatesList: function (templates) {
            const $container = $('#templates-list');

            if (templates.length === 0) {
                $container.html('<p>Keine Vorlagen gefunden.</p>');
                return;
            }

            let html = '<table class="neo-umfrage-table">';
            html += '<thead><tr><th>Name</th><th>Beschreibung</th><th>Status</th><th>Erstellungsdatum</th><th>Aktionen</th></tr></thead>';
            html += '<tbody>';

            templates.forEach(template => {
                const statusText = template.is_active == 1 ? 'Aktiv' : 'Inaktiv';
                const statusClass = template.is_active == 1 ? 'status-active' : 'status-inactive';
                
                html += `
            <tr>
                <td>${template.name}</td>
                <td>${template.description || 'Keine Beschreibung'}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${new Date(template.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="editTemplate(${template.id}, ${template.user_id || 0})">Bearbeiten</button>
                    ${template.is_active == 1 ? 
                        `<button class="neo-umfrage-button neo-umfrage-button-warning" onclick="deactivateTemplate(${template.id})">Deaktivieren</button>` : 
                        `<button class="neo-umfrage-button neo-umfrage-button-success" onclick="activateTemplate(${template.id})">Aktivieren</button>`
                    }
                    <button class="neo-umfrage-button neo-umfrage-button-danger" onclick="deleteTemplateWithSurveys(${template.id})">Löschen</button>
                </td>
            </tr>
        `;
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        // Деактивация шаблона
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
                            NeoUmfrageTemplates.loadTemplates();
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        },

        // Активация шаблона
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
                        NeoUmfrageTemplates.loadTemplates();
                    } else {
                        NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                    }
                }
            });
        },

        // Полное удаление шаблона с анкетами
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
                            NeoUmfrageTemplates.loadTemplates();
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            } else if (userInput !== null) {
                NeoUmfrage.showMessage('error', 'Löschvorgang abgebrochen. Bitte geben Sie "LÖSCHEN" ein, um zu bestätigen.');
            }
        },

        // Редактирование шаблона
        editTemplate: function (templateId) {
            // Отправляем AJAX запрос для получения данных шаблона
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

        // Открытие модального окна редактирования шаблона
        openEditTemplateModal: function (template) {
            // Заполняем форму данными шаблона
            $('#template-form input[name="name"]').val(template.name);
            $('#template-form textarea[name="description"]').val(template.description);
            
            // Добавляем скрытое поле с ID шаблона
            if (!$('#template-form input[name="template_id"]').length) {
                $('#template-form').append('<input type="hidden" name="template_id" value="' + template.id + '">');
            } else {
                $('#template-form input[name="template_id"]').val(template.id);
            }
            
            // Очищаем существующие поля
            $('.template-field').remove();
            
            // Добавляем поля шаблона
            if (template.fields && template.fields.length > 0) {
                template.fields.forEach(function(field, index) {
                    NeoUmfrageTemplates.addTemplateField(field, index);
                });
            }
            
            // Открываем модальное окно
            NeoUmfrageModals.openAddTemplateModal();
            
            // Меняем заголовок модального окна
            $('.neo-umfrage-modal-title').text('Vorlage bearbeiten');
        },

        // Добавление поля шаблона при редактировании
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
        }
    };

    // Глобальные функции для вызова из HTML
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
