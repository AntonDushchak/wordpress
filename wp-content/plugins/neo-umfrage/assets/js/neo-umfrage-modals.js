/**
 * Neo Umfrage - Модальные окна и формы
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoUmfrageModals = {
        
        // Создание модальных окон
        createModals: function () {
            // Модальное окно для добавления анкеты
            if (!$('#add-survey-modal').length) {
                $('body').append(`
            <div id="add-survey-modal" class="neo-umfrage-modal">
                <div class="neo-umfrage-modal-content">
                    <div class="neo-umfrage-modal-header">
                        <h3 class="neo-umfrage-modal-title">Umfrage hinzufügen</h3>
                        <button class="neo-umfrage-modal-close">&times;</button>
                    </div>
                    <div class="neo-umfrage-modal-body">
                        <form class="neo-umfrage-form" id="survey-form">
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Vorlage</label>
                                <select class="neo-umfrage-select" name="template_id" id="survey-template-select" required>
                                    <option value="">Vorlage auswählen</option>
                                </select>
                            </div>
                            
                            <!-- Pflichtfelder -->
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Name <span style="color: red;">*</span></label>
                                <input type="text" class="neo-umfrage-input" name="required_name" required>
                            </div>
                            
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Telefonnummer <span style="color: red;">*</span></label>
                                <input type="tel" class="neo-umfrage-input" name="required_phone" 
                                       placeholder="+49 30 12345678 oder 030 12345678"
                                       title="Geben Sie eine gültige deutsche Telefonnummer ein"
                                       required>
                            </div>
                            
                            <div id="survey-template-fields" class="neo-umfrage-form-group" style="display: none;">
                                <label class="neo-umfrage-label">Zusätzliche Umfragefelder</label>
                                <div id="template-fields-container">
                                    <!-- Felder werden dynamisch hinzugefügt -->
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="neo-umfrage-modal-footer">
                         <button type="button" class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrageModals.closeModal()">Abbrechen</button>
                         <button type="button" class="neo-umfrage-button" onclick="NeoUmfrageModals.submitSurveyForm()">Speichern</button>
                     </div>
                </div>
            </div>
        `);
            }

            // Модальное окно для добавления шаблона
            if (!$('#add-template-modal').length) {
                $('body').append(`
            <div id="add-template-modal" class="neo-umfrage-modal">
                <div class="neo-umfrage-modal-content" style="max-width: 800px;">
                    <div class="neo-umfrage-modal-header">
                        <h3 class="neo-umfrage-modal-title">Vorlage hinzufügen</h3>
                        <button class="neo-umfrage-modal-close">&times;</button>
                    </div>
                    <div class="neo-umfrage-modal-body">
                        <form class="neo-umfrage-form" id="template-form">
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Vorlagenname</label>
                                <input type="text" class="neo-umfrage-input" name="name" required>
                            </div>
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Beschreibung</label>
                                <textarea class="neo-umfrage-textarea" name="description"></textarea>
                            </div>
                            <div class="neo-umfrage-form-group">
                                <label class="neo-umfrage-label">Umfragefelder</label>
                                <div class="neo-umfrage-info" style="margin-bottom: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #007cba; border-radius: 4px;">
                                    <strong>Pflichtfelder:</strong> Name und Telefonnummer werden automatisch zu jeder Vorlage hinzugefügt.
                                </div>
                                <div id="template-fields">
                                    <!-- Feld "Name" (Pflichtfeld) -->
                                    <div class="template-field" data-field-index="0" style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                            <input type="text" class="neo-umfrage-input" name="fields[0][label]" value="Name" readonly style="background: #e9ecef;">
                                            <select class="neo-umfrage-select field-type-select" name="fields[0][type]" disabled>
                                                <option value="text" selected>Text</option>
                                            </select>
                                            <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                                <input type="checkbox" name="fields[0][required]" value="1" checked disabled>
                                                Pflichtfeld
                                            </label>
                                            <span style="color: #666; font-size: 12px;">(kann nicht gelöscht werden)</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Поле "Номер телефона" (обязательное) -->
                                    <div class="template-field" data-field-index="1" style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                            <input type="text" class="neo-umfrage-input" name="fields[1][label]" value="Telefonnummer" readonly style="background: #e9ecef;">
                                            <select class="neo-umfrage-select field-type-select" name="fields[1][type]" disabled>
                                                <option value="tel" selected>Telefon</option>
                                            </select>
                                            <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                                <input type="checkbox" name="fields[1][required]" value="1" checked disabled>
                                                Pflichtfeld
                                            </label>
                                            <span style="color: #666; font-size: 12px;">(kann nicht gelöscht werden)</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Дополнительные поля (начинаются с индекса 2) -->
                                    <div class="template-field" data-field-index="2">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                            <input type="text" class="neo-umfrage-input" name="fields[2][label]" placeholder="Feldname" required>
                                            <select class="neo-umfrage-select field-type-select" name="fields[2][type]" required>
                                                <option value="text">Text</option>
                                                <option value="tel">Telefon</option>
                                                <option value="textarea">Mehrzeiliger Text</option>
                                                <option value="email">Email</option>
                                                <option value="number">Zahl</option>
                                                <option value="radio">Einzelauswahl</option>
                                                <option value="checkbox">Mehrfachauswahl</option>
                                                <option value="select">Dropdown-Liste</option>
                                            </select>
                                            <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                                <input type="checkbox" name="fields[2][required]" value="1">
                                                Pflichtfeld
                                            </label>
                                            <button type="button" class="neo-umfrage-button neo-umfrage-button-danger remove-field-btn">Löschen</button>
                                        </div>
                                        <div class="field-options" style="display: none;">
                                            <label class="neo-umfrage-label">Antwortoptionen (eine pro Zeile)</label>
                                            <textarea class="neo-umfrage-textarea" name="fields[2][options]" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="neo-umfrage-button add-field-btn">Feld hinzufügen</button>
                            </div>
                        </form>
                    </div>
                    <div class="neo-umfrage-modal-footer">
                         <button type="button" class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrageModals.closeModal()">Abbrechen</button>
                         <button type="button" class="neo-umfrage-button" onclick="NeoUmfrageModals.submitTemplateForm()">Speichern</button>
                     </div>
                </div>
            </div>
        `);
            }
        },

        // Обработка отправки формы
        handleFormSubmit: function (e) {
            e.preventDefault();

            const $form = $(this);
            const formId = $form.attr('id');

            // Находим кнопку сохранения в модальном окне
            let $submitBtn;
            if (formId === 'survey-form') {
                $submitBtn = $('#add-survey-modal .neo-umfrage-modal-footer button:last');
            } else if (formId === 'template-form') {
                $submitBtn = $('#add-template-modal .neo-umfrage-modal-footer button:last');
            }

            // Показываем индикатор загрузки
            if ($submitBtn.length) {
                $submitBtn.prop('disabled', true).html('<span class="neo-umfrage-loading"></span>Speichern...');
            }

            // Собираем данные формы
            const formData = NeoUmfrageModals.collectFormData($form);

            // Определяем действие в зависимости от формы
            let action = '';
            if (formId === 'survey-form') {
                action = 'neo_umfrage_save_survey';
            } else if (formId === 'template-form') {
                action = 'neo_umfrage_save_template';
            }

            // Отладочная информация
            console.log('Отправляемые данные:', {
                action: action,
                nonce: neoUmfrageAjax.nonce,
                ...formData
            });

            // Отправляем AJAX запрос
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: neoUmfrageAjax.nonce,
                    ...formData
                },
                success: function (response) {
                    console.log('Получен ответ:', response);

                    if (response && response.success) {
                        const message = (response.data && response.data.message) ? response.data.message : 'Operation erfolgreich ausgeführt';
                        NeoUmfrage.showMessage('success', message);
                        NeoUmfrageModals.closeModal();
                        $form[0].reset();

                        // Обновляем соответствующие списки
                        if (formId === 'survey-form') {
                            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveys) {
                                NeoUmfrageSurveys.loadSurveys();
                            }
                        } else if (formId === 'template-form') {
                            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplates) {
                                NeoUmfrageTemplates.loadTemplates();
                            }
                            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                                NeoUmfrageTemplates.loadTemplatesForFilter();
                            }
                        }
                    } else {
                        const errorMessage = (response && response.data && response.data.message) ? response.data.message :
                            (response && response.message) ? response.message :
                                neoUmfrageAjax.strings.error;
                        NeoUmfrage.showMessage('error', errorMessage);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    NeoUmfrage.showMessage('error', 'Serverfehler: ' + error);
                },
                complete: function () {
                    if ($submitBtn && $submitBtn.length) {
                        $submitBtn.prop('disabled', false).html('Speichern');
                    }
                }
            });
        },

        // Сбор данных формы
        collectFormData: function ($form) {
            const formData = {};

            // Собираем обычные поля
            $form.find('input, textarea, select').each(function () {
                const $field = $(this);
                const name = $field.attr('name');
                const type = $field.attr('type');

                if (name && type !== 'button' && type !== 'submit') {
                    if (type === 'checkbox') {
                        formData[name] = $field.is(':checked');
                    } else {
                        formData[name] = $field.val();
                    }
                }
            });

            // Обрабатываем поля шаблона
            if ($form.attr('id') === 'template-form') {
                const fields = [];
                $form.find('.template-field').each(function () {
                    const $field = $(this);
                    const fieldData = {
                        label: $field.find('input[name*="[label]"]').val(),
                        type: $field.find('select[name*="[type]"]').val(),
                        required: $field.find('input[name*="[required]"]').is(':checked'),
                        options: []
                    };

                    // Обрабатываем варианты ответов
                    const optionsText = $field.find('textarea[name*="[options]"]').val();
                    if (optionsText) {
                        fieldData.options = optionsText.split('\n').filter(option => option.trim() !== '');
                    }

                    fields.push(fieldData);
                });

                // Отправляем как JSON строку
                formData.fields = JSON.stringify(fields);
            }

            // Обрабатываем поля анкеты
            if ($form.attr('id') === 'survey-form') {
                const surveyFields = [];

                // Добавляем обязательные поля
                surveyFields.push({
                    label: 'Name',
                    type: 'text',
                    required: true,
                    value: $form.find('input[name="required_name"]').val()
                });

                surveyFields.push({
                    label: 'Telefonnummer',
                    type: 'tel',
                    required: true,
                    value: $form.find('input[name="required_phone"]').val()
                });

                // Добавляем дополнительные поля из шаблона
                $form.find('.survey-field').each(function () {
                    const $field = $(this);
                    const fieldType = $field.data('field-type');
                    const fieldLabel = $field.find('input[name*="[label]"]').val();
                    const fieldRequired = $field.find('input[name*="[required]"]').val();

                    let fieldValue = '';

                    if (fieldType === 'checkbox') {
                        // Для чекбоксов собираем массив значений
                        const values = [];
                        $field.find('input[type="checkbox"]:checked').each(function () {
                            values.push($(this).val());
                        });
                        fieldValue = values;
                    } else {
                        // Для остальных типов полей
                        fieldValue = $field.find('input, textarea, select').val();
                    }

                    surveyFields.push({
                        label: fieldLabel,
                        type: fieldType,
                        required: fieldRequired === '1',
                        value: fieldValue
                    });
                });

                // Отправляем как JSON строку
                formData.survey_fields = JSON.stringify(surveyFields);

                // Добавляем ID ответа для редактирования, если есть
                const responseId = $form.find('input[name="response_id"]').val();
                if (responseId) {
                    formData.response_id = responseId;
                }
            }

            return formData;
        },

        // Добавление поля в шаблон
        addField: function () {
            const fieldIndex = $('.template-field').length;
            const $fieldsContainer = $('#template-fields');

            const fieldHtml = `
         <div class="template-field" data-field-index="${fieldIndex}">
             <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                 <input type="text" class="neo-umfrage-input" name="fields[${fieldIndex}][label]" placeholder="Feldname" required>
                 <select class="neo-umfrage-select field-type-select" name="fields[${fieldIndex}][type]" required>
                     <option value="text">Text</option>
                     <option value="tel">Telefon</option>
                     <option value="textarea">Mehrzeiliger Text</option>
                     <option value="email">Email</option>
                     <option value="number">Zahl</option>
                     <option value="radio">Einzelauswahl</option>
                     <option value="checkbox">Mehrfachauswahl</option>
                     <option value="select">Dropdown-Liste</option>
                 </select>
                 <label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                     <input type="checkbox" name="fields[${fieldIndex}][required]" value="1">
                     Pflichtfeld
                 </label>
                 <button type="button" class="neo-umfrage-button neo-umfrage-button-danger remove-field-btn">Löschen</button>
             </div>
            <div class="field-options" style="display: none;">
                <label class="neo-umfrage-label">Antwortoptionen (eine pro Zeile)</label>
                <textarea class="neo-umfrage-textarea" name="fields[${fieldIndex}][options]" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
            </div>
        </div>
    `;

            $fieldsContainer.append(fieldHtml);
        },

        // Удаление поля из шаблона
        removeField: function () {
            $(this).closest('.template-field').remove();

            // Перенумеровываем поля
            $('.template-field').each(function (index) {
                $(this).attr('data-field-index', index);
                $(this).find('input, select, textarea').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                    }
                });
            });
        },

        // Изменение типа поля
        changeFieldType: function () {
            const $field = $(this).closest('.template-field');
            const fieldType = $(this).val();
            const $options = $field.find('.field-options');

            // Показываем/скрываем поле для вариантов ответов
            if (['radio', 'checkbox', 'select'].includes(fieldType)) {
                $options.show();
            } else {
                $options.hide();
            }
        },

        // Загрузка полей шаблона
        loadTemplateFields: function (templateId, $container) {
            $container.html('<div class="neo-umfrage-loading">Загрузка полей...</div>');

            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template_fields',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function (response) {
                    console.log('Ответ загрузки полей:', response);

                    if (response && response.success) {
                        const fields = (response.data && response.data.fields) ? response.data.fields : [];
                        // Фильтруем поля, исключая обязательные
                        const filteredFields = fields.filter(field =>
                            field.label !== 'Name' && field.label !== 'Telefonnummer'
                        );
                        NeoUmfrageModals.renderTemplateFields(filteredFields, $container);
                    } else {
                        const errorMessage = (response && response.data && response.data.message) ? response.data.message :
                            (response && response.message) ? response.message : 'Неизвестная ошибка';
                        $container.html('<div class="neo-umfrage-error">Ошибка загрузки полей: ' + errorMessage + '</div>');
                    }
                },
                error: function () {
                    $container.html('<div class="neo-umfrage-error">Ошибка загрузки полей</div>');
                }
            });
        },

        // Отображение полей шаблона
        renderTemplateFields: function (fields, $container) {
            if (!fields || fields.length === 0) {
                $container.html('<div class="neo-umfrage-info">В шаблоне нет полей</div>');
                return;
            }

            // Пропускаем обязательные поля (Name и Telefonnummer) - они уже есть в форме
            const additionalFields = fields.filter(field =>
                field.label !== 'Name' && field.label !== 'Telefonnummer'
            );

            let html = '';
            additionalFields.forEach(function (field, index) {
                html += NeoUmfrageModals.renderSurveyField(field, '');
            });

            $container.html(html);
        },

        // Отображение одного поля анкеты
        renderSurveyField: function (field, defaultValue = '') {
            const required = field.required ? 'required' : '';
            const requiredText = field.required ? ' <span style="color: red;">*</span>' : '';

            let html = `<div class="neo-umfrage-form-group survey-field" data-field-type="${field.type}">`;
            html += `<label class="neo-umfrage-label">${field.label}${requiredText}</label>`;

            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                    html += `<input type="${field.type}" class="neo-umfrage-input" name="survey_fields[${field.label}][value]" value="${defaultValue}" ${required}>`;
                    break;

                case 'tel':
                    html += `<input type="tel" class="neo-umfrage-input" name="survey_fields[${field.label}][value]" 
                     value="${defaultValue}" 
                     placeholder="+49 30 12345678 oder 030 12345678"
                     title="Geben Sie eine gültige deutsche Telefonnummer ein"
                     ${required}>`;
                    break;

                case 'textarea':
                    html += `<textarea class="neo-umfrage-textarea" name="survey_fields[${field.label}][value]" ${required}>${defaultValue}</textarea>`;
                    break;

                case 'radio':
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function (option, optionIndex) {
                            const checked = (option === defaultValue) ? 'checked' : '';
                            html += `<label class="neo-umfrage-radio-label">`;
                            html += `<input type="radio" name="survey_fields[${field.label}][value]" value="${option}" ${checked} ${required}>`;
                            html += ` ${option}`;
                            html += `</label><br>`;
                        });
                    }
                    break;

                case 'checkbox':
                    const checkedValues = Array.isArray(defaultValue) ? defaultValue : [];
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function (option, optionIndex) {
                            const checked = checkedValues.includes(option) ? 'checked' : '';
                            html += `<label class="neo-umfrage-checkbox-label">`;
                            html += `<input type="checkbox" name="survey_fields[${field.label}][value][]" value="${option}" ${checked}>`;
                            html += ` ${option}`;
                            html += `</label><br>`;
                        });
                    }
                    break;

                case 'select':
                    html += `<select class="neo-umfrage-select" name="survey_fields[${field.label}][value]" ${required}>`;
                    html += `<option value="">Выберите вариант</option>`;
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function (option) {
                            const selected = (option === defaultValue) ? 'selected' : '';
                            html += `<option value="${option}" ${selected}>${option}</option>`;
                        });
                    }
                    html += `</select>`;
                    break;
            }

            // Добавляем скрытые поля для метаданных
            html += `<input type="hidden" name="survey_fields[${field.label}][label]" value="${field.label}">`;
            html += `<input type="hidden" name="survey_fields[${field.label}][type]" value="${field.type}">`;
            html += `<input type="hidden" name="survey_fields[${field.label}][required]" value="${field.required ? '1' : '0'}">`;

            html += `</div>`;
            return html;
        },

        // Открытие модального окна добавления анкеты
        openAddSurveyModal: function () {
            // Сбрасываем форму и заголовок
            $('#survey-form')[0].reset();
            $('#add-survey-modal .neo-umfrage-modal-title').text('Umfrage hinzufügen');
            $('#add-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');

            // Загружаем шаблоны в селект
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForSelect) {
                NeoUmfrageTemplates.loadTemplatesForSelect('#add-survey-modal select[name="template_id"]');
            }
        },

        // Открытие модального окна редактирования анкеты
        openEditSurveyModal: function () {
            // Сбрасываем форму и меняем заголовок
            $('#survey-form')[0].reset();
            $('#add-survey-modal .neo-umfrage-modal-title').text('Umfrage bearbeiten');
            $('#add-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },

        // Открытие модального окна добавления шаблона
        openAddTemplateModal: function () {
            $('#add-template-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },

        // Закрытие модального окна
        closeModal: function () {
            $('.neo-umfrage-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        },

        // Отправка формы шаблона
        submitTemplateForm: function () {
            const $form = $('#template-form');
            if ($form.length) {
                $form.trigger('submit');
            }
        },

        // Отправка формы анкеты
        submitSurveyForm: function () {
            const $form = $('#survey-form');
            if ($form.length) {
                $form.trigger('submit');
            }
        },

        // Обработка изменения шаблона
        handleTemplateChange: function () {
            const templateId = $(this).val();
            const $fieldsContainer = $('#survey-template-fields');
            const $fieldsContent = $('#template-fields-container');

            if (templateId) {
                // Загружаем поля шаблона
                if (window.NeoUmfrageModals && NeoUmfrageModals.loadTemplateFields) {
                    NeoUmfrageModals.loadTemplateFields(templateId, $fieldsContent);
                }
                $fieldsContainer.show();
            } else {
                $fieldsContainer.hide();
                $fieldsContent.empty();
            }
        }
    };
})(jQuery);
