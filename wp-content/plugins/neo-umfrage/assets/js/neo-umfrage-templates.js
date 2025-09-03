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
        
        // Загрузка шаблонов
        loadTemplates: function () {
            // Инициализируем модальные окна при первой загрузке
            this.init();
            
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        NeoUmfrageTemplates.renderTemplatesList(response.data);
                    }
                }
            });
        },

        // Загрузка шаблонов для фильтра
        loadTemplatesForFilter: function () {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
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

        // Загрузка шаблонов для селекта
        loadTemplatesForSelect: function (selector, callback) {
            console.log('loadTemplatesForSelect вызвана с селектором:', selector);
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    console.log('AJAX ответ для шаблонов:', response);
                    if (response.success) {
                        const $select = $(selector);
                        console.log('Найденный селект:', $select.length, 'элементов');
                        
                        // Очищаем все опции
                        $select.empty();
                        
                        if (response.data && response.data.length > 0) {
                            console.log('Загружаем', response.data.length, 'шаблонов');
                            // Добавляем первую опцию для выбора
                            $select.append('<option value="">Vorlage auswählen</option>');
                            response.data.forEach(template => {
                                $select.append(`<option value="${template.id}">${template.name}</option>`);
                            });
                        } else {
                            console.log('Нет доступных шаблонов');
                            $select.append('<option value="" disabled>Keine Vorlagen verfügbar</option>');
                        }

                        // Вызываем callback если он передан
                        if (typeof callback === 'function') {
                            callback();
                        }
                    } else {
                        console.error('Fehler beim Laden der Vorlagen:', response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX-Fehler beim Laden der Vorlagen:', error);
                }
            });
        },

        // Загрузка полей шаблона для редактирования
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
            html += '<thead><tr><th>Name</th><th>Beschreibung</th><th>Erstellungsdatum</th><th>Aktionen</th></tr></thead>';
            html += '<tbody>';

            templates.forEach(template => {
                html += `
            <tr>
                <td>${template.name}</td>
                <td>${template.description || 'Keine Beschreibung'}</td>
                <td>${new Date(template.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="editTemplate(${template.id}, ${template.user_id || 0})">Bearbeiten</button>
                    <button class="neo-umfrage-button neo-umfrage-button-danger" onclick="deleteTemplate(${template.id}, ${template.user_id || 0})">Löschen</button>
                </td>
            </tr>
        `;
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        // Удаление шаблона
        deleteTemplate: function (templateId) {
            if (confirm(neoUmfrageAjax.strings.confirm_delete)) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_template',
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
})(jQuery);
