/**
 * Neo Umfrage - JavaScript für Admin
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Objekt für Admin
    window.NeoUmfrageAdmin = {
        
        // Initialisierung
        init: function() {
            NeoUmfrageAdmin.bindEvents();
            NeoUmfrageAdmin.initModals();
            NeoUmfrageAdmin.loadInitialData();
        },

        // Event-Bindung
        bindEvents: function() {
            // События для модальных окон
            $(document).on('click', '.neo-umfrage-admin-modal-close', NeoUmfrageAdmin.closeModal);
            $(document).on('click', '.neo-umfrage-admin-modal', function(e) {
                if (e.target === this) {
                    NeoUmfrageAdmin.closeModal();
                }
            });

            // События для форм
            $(document).on('submit', '.neo-umfrage-admin-form', NeoUmfrageAdmin.handleFormSubmit);
            
            // События для кнопок
            $(document).on('click', '.neo-umfrage-admin-button', NeoUmfrageAdmin.handleButtonClick);
            
            // События для селектов
            $(document).on('change', '#survey-template-select', NeoUmfrageAdmin.handleTemplateChange);
            
            // События для фильтров
            $(document).on('change', '#template-filter', NeoUmfrageAdmin.filterSurveys);
            
            // События для добавления полей в шаблонах
            $(document).on('click', '.add-field-btn', NeoUmfrageAdmin.addField);
            $(document).on('click', '.remove-field-btn', NeoUmfrageAdmin.removeField);
            $(document).on('change', '.field-type-select', NeoUmfrageAdmin.changeFieldType);
        },

        // Инициализация модальных окон
        initModals: function() {
            // Создаем модальные окна если их нет
            NeoUmfrageAdmin.createModals();
        },

        // Загрузка начальных данных
        loadInitialData: function() {
            // Загружаем статистику для главной страницы
            if ($('#main-stats').length) {
                NeoUmfrageAdmin.loadStatistics();
            }
            
            // Загружаем анкеты для страницы анкет
            if ($('#surveys-list').length) {
                NeoUmfrageAdmin.loadSurveys();
                NeoUmfrageAdmin.loadTemplatesForFilter();
            }
            
            // Загружаем шаблоны для страницы шаблонов
            if ($('#templates-list').length) {
                NeoUmfrageAdmin.loadTemplates();
            }
            
            // Загружаем статистику для страницы статистики
            if ($('#statistics-stats').length) {
                NeoUmfrageAdmin.loadStatistics();
                NeoUmfrageAdmin.loadRecentSurveys();
            }
        },

        // Создание модальных окон
        createModals: function() {
            // Модальное окно для добавления анкеты
            if (!$('#add-survey-modal').length) {
                $('body').append(`
                    <div id="add-survey-modal" class="neo-umfrage-admin-modal">
                        <div class="neo-umfrage-admin-modal-content">
                            <div class="neo-umfrage-admin-modal-header">
                                <h3 class="neo-umfrage-admin-modal-title">Umfrage hinzufügen</h3>
                                <button class="neo-umfrage-admin-modal-close">&times;</button>
                            </div>
                            <div class="neo-umfrage-admin-modal-body">
                                <form class="neo-umfrage-admin-form" id="survey-form">
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Vorlage</label>
                                        <select class="neo-umfrage-admin-select" name="template_id" id="survey-template-select" required>
                                            <option value="">Vorlage auswählen</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Pflichtfelder -->
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Name <span style="color: red;">*</span></label>
                                        <input type="text" class="neo-umfrage-admin-input" name="required_name" required>
                                    </div>
                                    
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Telefonnummer <span style="color: red;">*</span></label>
                                        <input type="tel" class="neo-umfrage-admin-input" name="required_phone" 
                                               placeholder="+49 30 12345678 oder 030 12345678"
                                               title="Geben Sie eine gültige deutsche Telefonnummer ein"
                                               required>
                                    </div>
                                    
                                    <div id="survey-template-fields" class="neo-umfrage-admin-form-group" style="display: none;">
                                        <label class="neo-umfrage-admin-label">Zusätzliche Umfragefelder</label>
                                        <div id="template-fields-container">
                                            <!-- Felder werden dynamisch hinzugefügt -->
                                        </div>
                                    </div>
                                </form>
                            </div>
                                                         <div class="neo-umfrage-admin-modal-footer">
                                 <button type="button" class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrageAdmin.closeModal()">Abbrechen</button>
                                 <button type="button" class="neo-umfrage-admin-button" onclick="NeoUmfrageAdmin.submitSurveyForm()">Speichern</button>
                             </div>
                        </div>
                    </div>
                `);
            }

            // Модальное окно для добавления шаблона
            if (!$('#add-template-modal').length) {
                $('body').append(`
                    <div id="add-template-modal" class="neo-umfrage-admin-modal">
                        <div class="neo-umfrage-admin-modal-content" style="max-width: 800px;">
                            <div class="neo-umfrage-admin-modal-header">
                                <h3 class="neo-umfrage-admin-modal-title">Vorlage hinzufügen</h3>
                                <button class="neo-umfrage-admin-modal-close">&times;</button>
                            </div>
                            <div class="neo-umfrage-admin-modal-body">
                                <form class="neo-umfrage-admin-form" id="template-form">
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Vorlagenname</label>
                                        <input type="text" class="neo-umfrage-admin-input" name="name" required>
                                    </div>
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Beschreibung</label>
                                        <textarea class="neo-umfrage-admin-textarea" name="description"></textarea>
                                    </div>
                                    <div class="neo-umfrage-admin-form-group">
                                        <label class="neo-umfrage-admin-label">Umfragefelder</label>
                                        <div class="neo-umfrage-admin-info" style="margin-bottom: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #007cba; border-radius: 4px;">
                                            <strong>Pflichtfelder:</strong> Name und Telefonnummer werden automatisch zu jeder Vorlage hinzugefügt.
                                        </div>
                                        <div id="template-fields">
                                            <!-- Feld "Name" (Pflichtfeld) -->
                                            <div class="template-field" data-field-index="0" style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin-bottom: 10px;">
                                                <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                                    <input type="text" class="neo-umfrage-admin-input" name="fields[0][label]" value="Name" readonly style="background: #e9ecef;">
                                                    <select class="neo-umfrage-admin-select field-type-select" name="fields[0][type]" disabled>
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
                                                    <input type="text" class="neo-umfrage-admin-input" name="fields[1][label]" value="Telefonnummer" readonly style="background: #e9ecef;">
                                                    <select class="neo-umfrage-admin-select field-type-select" name="fields[1][type]" disabled>
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
                                                    <input type="text" class="neo-umfrage-admin-input" name="fields[2][label]" placeholder="Feldname" required>
                                                    <select class="neo-umfrage-admin-select field-type-select" name="fields[2][type]" required>
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
                                                    <button type="button" class="neo-umfrage-admin-button neo-umfrage-admin-button-danger remove-field-btn">Löschen</button>
                                                </div>
                                                <div class="field-options" style="display: none;">
                                                    <label class="neo-umfrage-admin-label">Antwortoptionen (eine pro Zeile)</label>
                                                    <textarea class="neo-umfrage-admin-textarea" name="fields[2][options]" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="neo-umfrage-admin-button add-field-btn">Feld hinzufügen</button>
                                    </div>
                                </form>
                            </div>
                                                         <div class="neo-umfrage-admin-modal-footer">
                                 <button type="button" class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrageAdmin.closeModal()">Abbrechen</button>
                                 <button type="button" class="neo-umfrage-admin-button" onclick="NeoUmfrageAdmin.submitTemplateForm()">Speichern</button>
                             </div>
                        </div>
                    </div>
                `);
            }
        },

        // Обработка отправки формы
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const formId = $form.attr('id');
            
            // Находим кнопку сохранения в модальном окне
            let $submitBtn;
            if (formId === 'survey-form') {
                $submitBtn = $('#add-survey-modal .neo-umfrage-admin-modal-footer button:last');
            } else if (formId === 'template-form') {
                $submitBtn = $('#add-template-modal .neo-umfrage-admin-modal-footer button:last');
            }
            
            // Показываем индикатор загрузки
            if ($submitBtn.length) {
                $submitBtn.prop('disabled', true).html('<span class="neo-umfrage-admin-loading"></span>Speichern...');
            }
            
            // Собираем данные формы
            const formData = NeoUmfrageAdmin.collectFormData($form);
            
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
                success: function(response) {
                    console.log('Получен ответ:', response);
                    
                    if (response && response.success) {
                        const message = (response.data && response.data.message) ? response.data.message : 'Operation erfolgreich ausgeführt';
                        NeoUmfrageAdmin.showMessage('success', message);
                        NeoUmfrageAdmin.closeModal();
                        $form[0].reset();
                        
                        // Обновляем соответствующие списки
                        if (formId === 'survey-form') {
                            NeoUmfrageAdmin.loadSurveys();
                        } else if (formId === 'template-form') {
                            NeoUmfrageAdmin.loadTemplates();
                            NeoUmfrageAdmin.loadTemplatesForFilter();
                        }
                    } else {
                        const errorMessage = (response && response.data && response.data.message) ? response.data.message : 
                                           (response && response.message) ? response.message : 
                                           neoUmfrageAjax.strings.error;
                        NeoUmfrageAdmin.showMessage('error', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    NeoUmfrageAdmin.showMessage('error', 'Serverfehler: ' + error);
                },
                complete: function() {
                    if ($submitBtn && $submitBtn.length) {
                        $submitBtn.prop('disabled', false).html('Speichern');
                    }
                }
            });
        },

        // Сбор данных формы
        collectFormData: function($form) {
            const formData = {};
            
            // Собираем обычные поля
            $form.find('input, textarea, select').each(function() {
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
                $form.find('.template-field').each(function() {
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
                $form.find('.survey-field').each(function() {
                    const $field = $(this);
                    const fieldType = $field.data('field-type');
                    const fieldLabel = $field.find('input[name*="[label]"]').val();
                    const fieldRequired = $field.find('input[name*="[required]"]').val();
                    
                    let fieldValue = '';
                    
                    if (fieldType === 'checkbox') {
                        // Для чекбоксов собираем массив значений
                        const values = [];
                        $field.find('input[type="checkbox"]:checked').each(function() {
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

        // Обработка клика по кнопкам
        handleButtonClick: function(e) {
            const $btn = $(this);
            const action = $btn.data('action');
            
            switch(action) {
                case 'add-survey':
                    NeoUmfrageAdmin.openAddSurveyModal();
                    break;
                case 'add-template':
                    NeoUmfrageAdmin.openAddTemplateModal();
                    break;
            }
        },

        // Добавление поля в шаблон
        addField: function() {
            const fieldIndex = $('.template-field').length;
            const $fieldsContainer = $('#template-fields');
            
                         const fieldHtml = `
                 <div class="template-field" data-field-index="${fieldIndex}">
                     <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                         <input type="text" class="neo-umfrage-admin-input" name="fields[${fieldIndex}][label]" placeholder="Feldname" required>
                         <select class="neo-umfrage-admin-select field-type-select" name="fields[${fieldIndex}][type]" required>
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
                         <button type="button" class="neo-umfrage-admin-button neo-umfrage-admin-button-danger remove-field-btn">Löschen</button>
                     </div>
                    <div class="field-options" style="display: none;">
                        <label class="neo-umfrage-admin-label">Antwortoptionen (eine pro Zeile)</label>
                        <textarea class="neo-umfrage-admin-textarea" name="fields[${fieldIndex}][options]" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                </div>
            `;
            
            $fieldsContainer.append(fieldHtml);
        },

        // Удаление поля из шаблона
        removeField: function() {
            $(this).closest('.template-field').remove();
            
            // Перенумеровываем поля
            $('.template-field').each(function(index) {
                $(this).attr('data-field-index', index);
                $(this).find('input, select, textarea').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                    }
                });
            });
        },

        // Изменение типа поля
        changeFieldType: function() {
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

        // Фильтрация анкет по шаблону
        filterSurveys: function() {
            const templateId = $(this).val();
            // Здесь будет логика фильтрации
            console.log('Фильтрация по шаблону:', templateId);
        },

        // Открытие модального окна добавления анкеты
        openAddSurveyModal: function() {
            // Сбрасываем форму и заголовок
            $('#survey-form')[0].reset();
            $('#add-survey-modal .neo-umfrage-admin-modal-title').text('Umfrage hinzufügen');
            $('#add-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');
            
            // Загружаем шаблоны в селект
            NeoUmfrageAdmin.loadTemplatesForSelect('#add-survey-modal select[name="template_id"]');
        },

        // Открытие модального окна редактирования анкеты
        openEditSurveyModal: function() {
            // Сбрасываем форму и меняем заголовок
            $('#survey-form')[0].reset();
            $('#add-survey-modal .neo-umfrage-admin-modal-title').text('Umfrage bearbeiten');
            $('#add-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },

        // Открытие модального окна добавления шаблона
        openAddTemplateModal: function() {
            $('#add-template-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },

        // Закрытие модального окна
        closeModal: function() {
            $('.neo-umfrage-admin-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        },

        // Отправка формы шаблона
        submitTemplateForm: function() {
            const $form = $('#template-form');
            if ($form.length) {
                $form.trigger('submit');
            }
        },

        // Отправка формы анкеты
        submitSurveyForm: function() {
            const $form = $('#survey-form');
            if ($form.length) {
                $form.trigger('submit');
            }
        },

        // Обработка изменения шаблона
        handleTemplateChange: function() {
            const templateId = $(this).val();
            const $fieldsContainer = $('#survey-template-fields');
            const $fieldsContent = $('#template-fields-container');
            
            if (templateId) {
                // Загружаем поля шаблона
                NeoUmfrageAdmin.loadTemplateFields(templateId, $fieldsContent);
                $fieldsContainer.show();
            } else {
                $fieldsContainer.hide();
                $fieldsContent.empty();
            }
        },

        // Загрузка полей шаблона
        loadTemplateFields: function(templateId, $container) {
            $container.html('<div class="neo-umfrage-admin-loading">Загрузка полей...</div>');
            
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template_fields',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    console.log('Ответ загрузки полей:', response);
                    
                    if (response && response.success) {
                        const fields = (response.data && response.data.fields) ? response.data.fields : [];
                        // Фильтруем поля, исключая обязательные
                        const filteredFields = fields.filter(field => 
                            field.label !== 'Name' && field.label !== 'Telefonnummer'
                        );
                        NeoUmfrageAdmin.renderTemplateFields(filteredFields, $container);
                    } else {
                        const errorMessage = (response && response.data && response.data.message) ? response.data.message : 
                                           (response && response.message) ? response.message : 'Неизвестная ошибка';
                        $container.html('<div class="neo-umfrage-admin-error">Ошибка загрузки полей: ' + errorMessage + '</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="neo-umfrage-admin-error">Ошибка загрузки полей</div>');
                }
            });
        },

        // Отображение полей шаблона
        renderTemplateFields: function(fields, $container) {
            if (!fields || fields.length === 0) {
                $container.html('<div class="neo-umfrage-admin-info">В шаблоне нет полей</div>');
                return;
            }
            
            // Пропускаем обязательные поля (Name и Telefonnummer) - они уже есть в форме
            const additionalFields = fields.filter(field => 
                field.label !== 'Name' && field.label !== 'Telefonnummer'
            );
            
            let html = '';
            additionalFields.forEach(function(field, index) {
                html += NeoUmfrageAdmin.renderSurveyField(field, '');
            });
            
            $container.html(html);
        },

        // Получение статистики по полю
        getFieldStatistics: function(templateId, fieldLabel) {
            return $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_field_statistics',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId,
                    field_label: fieldLabel
                }
            });
        },

        // Отображение статистики поля
        displayFieldStatistics: function(statistics) {
            let html = '<div class="neo-umfrage-admin-statistics">';
            html += '<h3>Статистика поля: ' + statistics.field_label + '</h3>';
            html += '<p><strong>Тип поля:</strong> ' + statistics.field_type + '</p>';
            html += '<p><strong>Всего ответов:</strong> ' + statistics.total_responses + '</p>';
            
            if (statistics.statistics) {
                html += '<div class="neo-umfrage-admin-stats-details">';
                
                switch (statistics.field_type) {
                    case 'text':
                    case 'textarea':
                    case 'email':
                        if (statistics.statistics.most_common) {
                            html += '<h4>Самые частые ответы:</h4>';
                            html += '<ul>';
                            for (let [response, count] of Object.entries(statistics.statistics.most_common)) {
                                html += '<li>' + response + ': ' + count + ' раз</li>';
                            }
                            html += '</ul>';
                        }
                        break;
                        
                    case 'number':
                        if (statistics.statistics.min !== undefined) {
                            html += '<h4>Числовая статистика:</h4>';
                            html += '<ul>';
                            html += '<li>Минимум: ' + statistics.statistics.min + '</li>';
                            html += '<li>Максимум: ' + statistics.statistics.max + '</li>';
                            html += '<li>Среднее: ' + statistics.statistics.average + '</li>';
                            html += '<li>Медиана: ' + statistics.statistics.median + '</li>';
                            html += '</ul>';
                        }
                        break;
                        
                    case 'radio':
                    case 'select':
                    case 'checkbox':
                        if (statistics.statistics.option_counts) {
                            html += '<h4>Распределение ответов:</h4>';
                            html += '<ul>';
                            for (let [option, count] of Object.entries(statistics.statistics.option_counts)) {
                                const percentage = ((count / statistics.total_responses) * 100).toFixed(1);
                                html += '<li>' + option + ': ' + count + ' (' + percentage + '%)</li>';
                            }
                            html += '</ul>';
                        }
                        break;
                }
                
                html += '</div>';
            }
            
            html += '</div>';
            return html;
        },

        // Отображение одного поля анкеты
        renderSurveyField: function(field, defaultValue = '') {
            const required = field.required ? 'required' : '';
            const requiredText = field.required ? ' <span style="color: red;">*</span>' : '';
            
            let html = `<div class="neo-umfrage-admin-form-group survey-field" data-field-type="${field.type}">`;
            html += `<label class="neo-umfrage-admin-label">${field.label}${requiredText}</label>`;
            
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                    html += `<input type="${field.type}" class="neo-umfrage-admin-input" name="survey_fields[${field.label}][value]" value="${defaultValue}" ${required}>`;
                    break;
                    
                case 'tel':
                    html += `<input type="tel" class="neo-umfrage-admin-input" name="survey_fields[${field.label}][value]" 
                             value="${defaultValue}" 
                             placeholder="+49 30 12345678 oder 030 12345678"
                             title="Geben Sie eine gültige deutsche Telefonnummer ein"
                             ${required}>`;
                    break;
                    
                case 'textarea':
                    html += `<textarea class="neo-umfrage-admin-textarea" name="survey_fields[${field.label}][value]" ${required}>${defaultValue}</textarea>`;
                    break;
                    
                case 'radio':
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function(option, optionIndex) {
                            const checked = (option === defaultValue) ? 'checked' : '';
                            html += `<label class="neo-umfrage-admin-radio-label">`;
                            html += `<input type="radio" name="survey_fields[${field.label}][value]" value="${option}" ${checked} ${required}>`;
                            html += ` ${option}`;
                            html += `</label><br>`;
                        });
                    }
                    break;
                    
                case 'checkbox':
                    const checkedValues = Array.isArray(defaultValue) ? defaultValue : [];
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function(option, optionIndex) {
                            const checked = checkedValues.includes(option) ? 'checked' : '';
                            html += `<label class="neo-umfrage-admin-checkbox-label">`;
                            html += `<input type="checkbox" name="survey_fields[${field.label}][value][]" value="${option}" ${checked}>`;
                            html += ` ${option}`;
                            html += `</label><br>`;
                        });
                    }
                    break;
                    
                case 'select':
                    html += `<select class="neo-umfrage-admin-select" name="survey_fields[${field.label}][value]" ${required}>`;
                    html += `<option value="">Выберите вариант</option>`;
                    if (field.options && field.options.length > 0) {
                        field.options.forEach(function(option) {
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

        // Показ сообщения
        showMessage: function(type, message) {
            const $message = $(`
                <div class="neo-umfrage-admin-notice neo-umfrage-admin-notice-${type}">
                    ${message}
                </div>
            `);
            
            $('.neo-umfrage-admin-container').prepend($message);
            
            // Автоматически скрываем сообщение через 5 секунд
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Загрузка статистики
        loadStatistics: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const stats = response.data;
                        $('#total-surveys, #stats-total-surveys').text(stats.total_surveys);
                        $('#total-templates, #stats-total-templates').text(stats.total_templates);
                        $('#total-responses, #stats-total-responses').text(stats.total_responses);
                    }
                }
            });
        },

        // Загрузка анкет
        loadSurveys: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_surveys',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response && response.success) {
                        NeoUmfrageAdmin.renderSurveysList(response.data);
                    } else {
                        NeoUmfrageAdmin.showMessage('error', 'Fehler beim Laden der Umfragen');
                    }
                },
                error: function() {
                    NeoUmfrageAdmin.showMessage('error', 'Fehler beim Laden der Umfragen');
                }
            });
        },

        // Загрузка шаблонов
        loadTemplates: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrageAdmin.renderTemplatesList(response.data);
                    }
                }
            });
        },

        // Загрузка шаблонов для фильтра
        loadTemplatesForFilter: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
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
        loadTemplatesForSelect: function(selector, callback) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
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

        // Загрузка последних анкет
        loadRecentSurveys: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrageAdmin.renderRecentSurveys(response.data.recent_surveys);
                    }
                }
            });
        },

        // Отображение списка анкет
        renderSurveysList: function(surveys) {
            const $container = $('#surveys-list');
            
            if (!surveys || !Array.isArray(surveys)) {
                $container.html('<p>Fehler: Umfragedaten konnten nicht abgerufen werden.</p>');
                return;
            }
            
            if (surveys.length === 0) {
                $container.html('<p>Keine Umfragen gefunden.</p>');
                return;
            }
            
            let html = '<div class="neo-umfrage-admin-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">';
            
            // Фильтр по шаблону
            html += '<div>';
            html += '<label class="neo-umfrage-admin-label">Filter nach Vorlage:</label>';
            html += '<select id="template-filter" class="neo-umfrage-admin-select" style="margin-left: 10px;">';
            html += '<option value="">Alle Vorlagen</option>';
            const uniqueTemplates = [...new Set(surveys.map(s => s.template_name))];
            uniqueTemplates.forEach(templateName => {
                html += `<option value="${templateName}">${templateName}</option>`;
            });
            html += '</select>';
            html += '</div>';
            
            // Фильтр по пользователю
            html += '<div>';
            html += '<label class="neo-umfrage-admin-label">Filter nach Benutzer:</label>';
            html += '<select id="user-filter" class="neo-umfrage-admin-select" style="margin-left: 10px;">';
            html += '<option value="">Alle Benutzer</option>';
            const uniqueUsers = [...new Set(surveys.map(s => s.wp_user_name))];
            uniqueUsers.forEach(userName => {
                html += `<option value="${userName}">${userName}</option>`;
            });
            html += '</select>';
            html += '</div>';
            
            html += '</div>';
            
            html += '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Vorlage</th><th>Benutzer</th><th>Name aus Umfrage</th><th>Telefonnummer</th><th>Ausfüllungsdatum</th><th>Aktionen</th></tr></thead>';
            html += '<tbody>';
            
            surveys.forEach(survey => {
                const name = survey.name_value || 'Nicht ausgefüllt';
                const phone = survey.phone_value || 'Nicht ausgefüllt';
                const submittedDate = new Date(survey.submitted_at).toLocaleDateString('de-DE', {
                    timeZone: 'Europe/Berlin',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <tr data-user="${survey.wp_user_name}" data-template="${survey.template_name}">
                        <td>${survey.template_name || 'Nicht angegeben'}</td>
                        <td>${survey.wp_user_name || 'Unbekannter Benutzer'}</td>
                        <td>${name}</td>
                        <td>${phone}</td>
                        <td>${submittedDate}</td>
                        <td class="actions">
                            ${NeoUmfrageAdmin.canEdit() ? `<button class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrageAdmin.editSurvey(${survey.response_id})">Bearbeiten</button>` : ''}
                            ${NeoUmfrageAdmin.canDelete() ? `<button class="neo-umfrage-admin-button neo-umfrage-admin-button-danger" onclick="NeoUmfrageAdmin.deleteSurvey(${survey.response_id})">Löschen</button>` : ''}
                            <button class="neo-umfrage-admin-button" onclick="NeoUmfrageAdmin.viewSurvey(${survey.response_id})">Anzeigen</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
            
            // Добавляем обработчики фильтров
            $('#user-filter').on('change', function() {
                applyFilters();
            });
            
            $('#template-filter').on('change', function() {
                applyFilters();
            });
            
            function applyFilters() {
                const selectedUser = $('#user-filter').val();
                const selectedTemplate = $('#template-filter').val();
                
                $('.neo-umfrage-admin-table tbody tr').each(function() {
                    const userData = $(this).data('user');
                    const templateData = $(this).data('template');
                    
                    const userMatch = selectedUser === '' || userData === selectedUser;
                    const templateMatch = selectedTemplate === '' || templateData === selectedTemplate;
                    
                    if (userMatch && templateMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        },

        // Проверка прав на редактирование
        canEdit: function() {
            if (!neoUmfrageAjax.userRoles) {
                console.warn('userRoles не определены, разрешаем редактирование');
                return true;
            }
            return neoUmfrageAjax.userRoles.includes('administrator') || 
                   neoUmfrageAjax.userRoles.includes('neo_editor');
        },

        // Проверка прав на удаление
        canDelete: function() {
            if (!neoUmfrageAjax.userRoles) {
                console.warn('userRoles не определены, разрешаем удаление');
                return true;
            }
            return neoUmfrageAjax.userRoles.includes('administrator') || 
                   neoUmfrageAjax.userRoles.includes('neo_editor');
        },

        // Удаление анкеты
        deleteSurvey: function(surveyId) {
            if (confirm('Вы уверены, что хотите удалить эту анкету?')) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_survey',
                        nonce: neoUmfrageAjax.nonce,
                        survey_id: surveyId
                    },
                    success: function(response) {
                        if (response && response.success) {
                            NeoUmfrageAdmin.showMessage('success', 'Анкета удалена успешно');
                            NeoUmfrageAdmin.loadSurveys();
                        } else {
                            NeoUmfrageAdmin.showMessage('error', 'Ошибка при удалении анкеты');
                        }
                    },
                    error: function() {
                        NeoUmfrageAdmin.showMessage('error', 'Ошибка при удалении анкеты');
                    }
                });
            }
        },

        // Редактирование анкеты
        editSurvey: function(responseId) {
            // Открываем модальное окно для редактирования анкеты
            NeoUmfrageAdmin.openEditSurveyModal();
            
            // Загружаем данные анкеты для редактирования
            NeoUmfrageAdmin.loadSurveyForEdit(responseId);
        },

        // Загрузка данных анкеты для редактирования
        loadSurveyForEdit: function(responseId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_survey_data',
                    nonce: neoUmfrageAjax.nonce,
                    survey_id: responseId
                },
                success: function(response) {
                    if (response && response.success) {
                        NeoUmfrageAdmin.populateSurveyForm(response.data);
                    } else {
                        NeoUmfrageAdmin.showMessage('error', 'Ошибка загрузки данных анкеты');
                    }
                },
                error: function() {
                    NeoUmfrageAdmin.showMessage('error', 'Ошибка загрузки данных анкеты');
                }
            });
        },

        // Заполнение формы данными анкеты
        populateSurveyForm: function(surveyData) {
            const response = surveyData.response;
            const fields = surveyData.response_data;
            const templateId = surveyData.template_id; // Получаем template_id из данных
            
            // Сначала загружаем шаблоны в селект
            NeoUmfrageAdmin.loadTemplatesForSelect('#survey-template-select', function() {
                // После загрузки шаблонов устанавливаем нужный
                $('#survey-template-select').val(templateId);
                
                // Загружаем поля шаблона и заполняем их данными
                NeoUmfrageAdmin.loadTemplateFieldsForEdit(templateId, fields);
            });
            
            // Заполняем обязательные поля из ответа
            if (fields && fields.length > 0) {
                fields.forEach(function(field) {
                    if (field.label === 'Name') {
                        $('input[name="required_name"]').val(field.value);
                    } else if (field.label === 'Telefonnummer') {
                        $('input[name="required_phone"]').val(field.value);
                    }
                });
            }
            
            // Показываем поля шаблона
            $('#survey-template-fields').show();
            
            // Добавляем скрытое поле с ID ответа для обновления
            if (!$('#response-id-field').length) {
                $('#survey-form').append('<input type="hidden" id="response-id-field" name="response_id" value="' + response.id + '">');
            } else {
                $('#response-id-field').val(response.id);
            }
        },

        // Загрузка полей шаблона для редактирования
        loadTemplateFieldsForEdit: function(templateId, existingFields) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template_fields',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function(response) {
                    if (response && response.success) {
                        const fields = (response.data && response.data.fields) ? response.data.fields : [];
                        // Фильтруем поля, исключая обязательные
                        const filteredFields = fields.filter(field => 
                            field.label !== 'Name' && field.label !== 'Telefonnummer'
                        );
                        NeoUmfrageAdmin.renderTemplateFieldsForEdit(filteredFields, existingFields);
                    }
                }
            });
        },

        // Рендер полей шаблона для редактирования
        renderTemplateFieldsForEdit: function(templateFields, existingFields) {
            const $container = $('#template-fields-container');
            $container.empty();
            
            // Пропускаем обязательные поля (Name и Telefonnummer) - они уже есть в форме
            const additionalFields = templateFields.filter(field => 
                field.label !== 'Name' && field.label !== 'Telefonnummer'
            );
            
            additionalFields.forEach(function(field, index) {
                // Находим значение из существующих данных
                let fieldValue = '';
                if (existingFields) {
                    const existingField = existingFields.find(f => f.label === field.label);
                    if (existingField) {
                        fieldValue = existingField.value;
                    }
                }
                
                const fieldHtml = NeoUmfrageAdmin.renderSurveyField(field, fieldValue);
                $container.append(fieldHtml);
            });
        },

        // Просмотр анкеты
        viewSurvey: function(surveyId) {
            // Создаем модальное окно для просмотра анкеты
            NeoUmfrageAdmin.createViewSurveyModal(surveyId);
        },

        // Создание модального окна для просмотра анкеты
        createViewSurveyModal: function(surveyId) {
            // Удаляем существующее модальное окно если есть
            $('#view-survey-modal').remove();
            
            const modalHtml = `
                <div id="view-survey-modal" class="neo-umfrage-admin-modal">
                    <div class="neo-umfrage-admin-modal-content">
                        <div class="neo-umfrage-admin-modal-header">
                            <h3 class="neo-umfrage-admin-modal-title">Umfrage anzeigen</h3>
                            <button class="neo-umfrage-admin-modal-close">&times;</button>
                        </div>
                        <div class="neo-umfrage-admin-modal-body">
                            <div id="survey-view-content">
                                <div class="neo-umfrage-admin-loading">Umfragedaten werden geladen...</div>
                            </div>
                        </div>
                        <div class="neo-umfrage-admin-modal-footer">
                            <button type="button" class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrageAdmin.closeModal()">Schließen</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#view-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');
            
            // Загружаем данные анкеты
            NeoUmfrageAdmin.loadSurveyData(surveyId);
        },

        // Загрузка данных анкеты для просмотра
        loadSurveyData: function(surveyId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_survey_data',
                    nonce: neoUmfrageAjax.nonce,
                    survey_id: surveyId
                },
                success: function(response) {
                    if (response && response.success) {
                        NeoUmfrageAdmin.displaySurveyData(response.data);
                    } else {
                        $('#survey-view-content').html('<div class="neo-umfrage-admin-error">Ошибка загрузки данных анкеты</div>');
                    }
                },
                error: function() {
                    $('#survey-view-content').html('<div class="neo-umfrage-admin-error">Ошибка загрузки данных анкеты</div>');
                }
            });
        },

        // Отображение данных анкеты
        displaySurveyData: function(response) {
            const responseData = response.response;
            const fields = response.response_data;
            const templateName = response.template_name;
            
            // Находим имя пользователя из полей анкеты
            let surveyUserName = 'Nicht angegeben';
            if (fields && fields.length > 0) {
                fields.forEach(function(field) {
                    if (field.label === 'Name' && field.value) {
                        surveyUserName = field.value;
                    }
                });
            }
            
            // Получаем имя пользователя WordPress
            let wpUserName = 'Unbekannter Benutzer';
            if (responseData.first_name && responseData.last_name) {
                wpUserName = responseData.first_name + ' ' + responseData.last_name;
            } else if (responseData.user_display_name) {
                wpUserName = responseData.user_display_name;
            }
            
            let html = '<div class="neo-umfrage-admin-survey-view">';
            html += '<h4>Umfragedaten</h4>';
            html += '<p><strong>Vorlage:</strong> ' + (templateName || 'Nicht angegeben') + '</p>';
            html += '<p><strong>Ausfüllungsdatum:</strong> ' + new Date(responseData.submitted_at).toLocaleString('de-DE', {
                timeZone: 'Europe/Berlin',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) + '</p>';
            html += '<p><strong>WordPress-Benutzer:</strong> ' + wpUserName + '</p>';
            html += '<p><strong>Name aus Umfrage:</strong> ' + surveyUserName + '</p>';
            
            // Фильтруем только заполненные поля (исключаем пустые значения)
            const filledFields = fields ? fields.filter(function(field) {
                return field.label && field.value && field.value !== '' && field.value !== null;
            }) : [];
            
            if (filledFields.length > 0) {
                html += '<h4>Ausgefüllte Felder</h4>';
                html += '<div class="neo-umfrage-admin-response-fields">';
                
                filledFields.forEach(function(field) {
                    html += '<div class="neo-umfrage-admin-field">';
                    html += '<strong>' + field.label + ':</strong> ';
                    if (Array.isArray(field.value)) {
                        html += field.value.join(', ');
                    } else {
                        html += field.value;
                    }
                    html += '</div>';
                });
                
                html += '</div>';
            } else {
                html += '<p>Keine Daten gefunden.</p>';
            }
            
            html += '</div>';
            $('#survey-view-content').html(html);
        },

        // Отображение списка шаблонов
        renderTemplatesList: function(templates) {
            const $container = $('#templates-list');
            
            if (templates.length === 0) {
                $container.html('<p>Шаблоны не найдены.</p>');
                return;
            }
            
            let html = '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Название</th><th>Описание</th><th>Дата создания</th><th>Действия</th></tr></thead>';
            html += '<tbody>';
            
            templates.forEach(template => {
                html += `
                    <tr>
                        <td>${template.name}</td>
                        <td>${template.description || 'Нет описания'}</td>
                        <td>${new Date(template.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrageAdmin.editTemplate(${template.id})">Редактировать</button>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-danger" onclick="NeoUmfrageAdmin.deleteTemplate(${template.id})">Удалить</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },

        // Отображение последних анкет
        renderRecentSurveys: function(surveys) {
            const $container = $('#recent-surveys');
            
            if (surveys.length === 0) {
                $container.html('<p>Анкеты не найдены.</p>');
                return;
            }
            
            let html = '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Название</th><th>Шаблон</th><th>Дата создания</th></tr></thead>';
            html += '<tbody>';
            
            surveys.forEach(survey => {
                html += `
                    <tr>
                        <td>${survey.title}</td>
                        <td>${survey.template_name || 'Не указан'}</td>
                        <td>${new Date(survey.created_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },



        // Редактирование шаблона
        editTemplate: function(templateId) {
            // Здесь будет логика редактирования шаблона
            console.log('Редактирование шаблона:', templateId);
        },

        // Удаление шаблона
        deleteTemplate: function(templateId) {
            if (confirm(neoUmfrageAjax.strings.confirm_delete)) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_template',
                        nonce: neoUmfrageAjax.nonce,
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            NeoUmfrageAdmin.showMessage('success', response.data.message);
                            NeoUmfrageAdmin.loadTemplates();
                        } else {
                            NeoUmfrageAdmin.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        }
    };

    // Глобальные функции для вызова из HTML
    window.openAddSurveyModal = function() {
        NeoUmfrageAdmin.openAddSurveyModal();
    };

    window.openAddTemplateModal = function() {
        NeoUmfrageAdmin.openAddTemplateModal();
    };

    // Инициализация при загрузке документа
    $(document).ready(function() {
        NeoUmfrageAdmin.init();
    });

})(jQuery);
