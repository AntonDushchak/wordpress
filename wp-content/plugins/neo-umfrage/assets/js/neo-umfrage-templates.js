/**
 * Neo Umfrage - Работа с шаблонами
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoUmfrageTemplates = {
        
        // Загрузка шаблонов
        loadTemplates: function () {
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
                        const $filter = $('#template-filter');
                        $filter.find('option:not(:first)').remove();
                        response.data.forEach(template => {
                            $filter.append(`<option value="${template.id}">${template.name}</option>`);
                        });
                    }
                }
            });
        },

        // Загрузка шаблонов для селекта
        loadTemplatesForSelect: function (selector, callback) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        const $select = $(selector);
                        $select.find('option:not(:first)').remove();
                        response.data.forEach(template => {
                            $select.append(`<option value="${template.id}">${template.name}</option>`);
                        });

                        // Вызываем callback если он передан
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
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
                        // Фильтруем поля, исключая обязательные
                        const filteredFields = fields.filter(field =>
                            field.label !== 'Name' && field.label !== 'Telefonnummer'
                        );
                        NeoUmfrageTemplates.renderTemplateFieldsForEdit(filteredFields, existingFields);
                    }
                }
            });
        },

        // Рендер полей шаблона для редактирования
        renderTemplateFieldsForEdit: function (templateFields, existingFields) {
            const $container = $('#template-fields-container');
            $container.empty();

            // Пропускаем обязательные поля (Name и Telefonnummer) - они уже есть в форме
            const additionalFields = templateFields.filter(field =>
                field.label !== 'Name' && field.label !== 'Telefonnummer'
            );

            additionalFields.forEach(function (field, index) {
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
                $container.html('<p>Шаблоны не найдены.</p>');
                return;
            }

            let html = '<table class="neo-umfrage-table">';
            html += '<thead><tr><th>Название</th><th>Описание</th><th>Дата создания</th><th>Действия</th></tr></thead>';
            html += '<tbody>';

            templates.forEach(template => {
                html += `
            <tr>
                <td>${template.name}</td>
                <td>${template.description || 'Нет описания'}</td>
                <td>${new Date(template.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrageTemplates.editTemplate(${template.id})">Редактировать</button>
                    <button class="neo-umfrage-button neo-umfrage-button-danger" onclick="NeoUmfrageTemplates.deleteTemplate(${template.id})">Удалить</button>
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
            // Здесь будет логика редактирования шаблона
            console.log('Редактирование шаблона:', templateId);
        }
    };
})(jQuery);
