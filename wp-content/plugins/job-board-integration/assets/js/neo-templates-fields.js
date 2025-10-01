/**
 * Neo Job Board Templates - Fields Management
 * Управление полями шаблонов
 */

window.NeoTemplatesFields = (function($) {
    'use strict';
    
    let fieldCounter = 0;
    
    const fieldTypes = {
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
        // Специальные типы полей с фиксированными названиями
        'position': 'Position',
        'bildung': 'Bildung',
        'berufserfahrung': 'Berufserfahrung',
        'sprachkenntnisse': 'Sprachkenntnisse',
        'fuehrerschein': 'Führerschein',
        // Обычные поля с предопределенными опциями
        'arbeitszeit': 'Arbeitszeit',
        'liste': 'Liste'
    };

    // Фиксированные названия для специальных полей
    const fixedFieldNames = {
        'position': 'Position',
        'bildung': 'Bildung', 
        'berufserfahrung': 'Berufserfahrung',
        'sprachkenntnisse': 'Sprachkenntnisse',
        'fuehrerschein': 'Führerschein'
    };

    return {
        // Инициализация модуля
        init: function() {
            this.ensureNameField();
        },
        
        // Убедиться что поле Name существует и не может быть удалено
        ensureNameField: function() {
            // Проверяем, есть ли уже поле Name
            const existingNameField = $('#template-fields-container').find('[data-field-name="name"]');
            
            if (existingNameField.length === 0) {
                // Добавляем обязательное поле Name в начало
                // Не передаем индекс, чтобы использовать правильную нумерацию
                fieldCounter = 0; // Сбрасываем счетчик для системного поля
                this.addTemplateFieldWithData({
                    type: 'text',
                    label: 'Name (Vor- und Nachname)',
                    required: true,
                    personal_data: true,
                    options: '',
                    system_field: true,
                    field_name: 'name'
                });
            }
        },
        
        // Добавить новое поле шаблона
        addTemplateField: function() {
            this.addTemplateFieldWithData({
                type: 'text',
                label: '',
                required: false,
                personal_data: false,
                options: ''
            });
        },
        
        // Добавить поле с данными
        addTemplateFieldWithData: function(fieldData, index) {
            // Если индекс не передан, используем следующий счетчик
            if (index === undefined) {
                fieldCounter++;
                index = fieldCounter;
            } else {
                // Если индекс передан, используем его (для загрузки существующих полей)
                index = parseInt(index) + 1;
                if (index > fieldCounter) {
                    fieldCounter = index;
                }
            }
            
            const fieldId = 'field_' + index;
            
            const fieldHtml = this.generateFieldHtml(fieldId, fieldData);
            $('#template-fields').append(fieldHtml);
            
            // Используем обычный Bootstrap select для лучшей совместимости с модальными окнами
            
            // Обработчик изменения типа поля
            $(`#${fieldId}_type`).on('change', (e) => {
                this.handleFieldTypeChange(fieldId, e.target.value);
            });
            
            // Показываем/скрываем опции в зависимости от типа
            this.handleFieldTypeChange(fieldId, fieldData.type);
            
            // Если это специальное поле, устанавливаем фиксированное название
            if (fixedFieldNames[fieldData.type]) {
                // Устанавливаем фиксированное название для специальных типов при создании
                fieldData.label = fixedFieldNames[fieldData.type];
                setTimeout(() => {
                    this.handleFieldTypeChange(fieldId, fieldData.type);
                }, 100);
            }
        },
        
        // Генерация HTML поля
        generateFieldHtml: function(fieldId, fieldData) {
            const typeOptions = Object.entries(fieldTypes)
                .map(([value, label]) => `<option value="${value}" ${fieldData.type === value ? 'selected' : ''}>${label}</option>`)
                .join('');
            
            // Устанавливаем фиксированное название для специальных типов полей
            if (fixedFieldNames[fieldData.type] && !fieldData.label) {
                fieldData.label = fixedFieldNames[fieldData.type];
            }
            
            const isSystemField = fieldData.system_field || false;
            const isSpecialField = fixedFieldNames[fieldData.type] || false;
            const fieldNumber = fieldId.replace('field_', '');
            const fieldTitle = isSystemField ? `${fieldData.label} (Systemfeld)` : 
                              isSpecialField ? `${fieldData.label} (Spezialfeld)` : 
                              `Feld #${fieldNumber}`;
            const removeButton = (isSystemField || isSpecialField) ? '' : `
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="NeoTemplatesFields.removeTemplateField('${fieldId}')">
                    <i class="bi bi-trash"></i> Entfernen
                </button>
            `;
                
            return `
                <div class="card mb-3 template-field ${isSystemField ? 'border-primary' : isSpecialField ? 'border-success' : ''}" id="${fieldId}" ${fieldData.field_name ? `data-field-name="${fieldData.field_name}"` : ''}>
                    <div class="card-header ${isSystemField ? 'bg-primary text-white' : isSpecialField ? 'bg-success text-white' : ''}">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${fieldTitle}</h6>
                            ${removeButton}
                        </div>
                        ${isSystemField ? '<small>Dieses Feld ist erforderlich und kann nicht entfernt werden</small>' : 
                          isSpecialField ? '<small>Spezialfeld mit festem Namen</small>' : ''}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Typ</label>
                                <select class="form-select form-control" id="${fieldId}_type" name="fields[${fieldId}][type]" ${isSystemField ? 'disabled' : ''}>
                                    ${typeOptions}
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Label *</label>
                                <input type="text" class="form-control" id="${fieldId}_label" name="fields[${fieldId}][label]" value="${fieldData.label || ''}" required ${isSystemField || fixedFieldNames[fieldData.type] ? 'readonly' : ''}>
                                ${isSystemField ? `<input type="hidden" name="fields[${fieldId}][field_name]" value="${fieldData.field_name}">` : ''}
                                ${fixedFieldNames[fieldData.type] ? `<input type="hidden" name="fields[${fieldId}][field_name]" value="${fieldData.type}">` : ''}
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label">Erforderlich</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="fields[${fieldId}][required]" value="1" ${fieldData.required ? 'checked' : ''} ${isSystemField ? 'disabled' : ''}>
                                            <label class="form-check-label">Ja</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Persönliche Daten</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="fields[${fieldId}][personal_data]" value="1" ${fieldData.personal_data ? 'checked' : ''} ${isSystemField ? 'disabled' : ''}>
                                            <label class="form-check-label">Nicht an API</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 field-options" id="${fieldId}_options_container" style="display: none;">
                                <label class="form-label">Optionen (eine pro Zeile)</label>
                                <textarea class="form-control" name="fields[${fieldId}][options]" rows="3" placeholder="Option 1\nOption 2\nOption 3">${fieldData.options || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        // Обработка изменения типа поля
        handleFieldTypeChange: function(fieldId, fieldType) {
            const $optionsContainer = $(`#${fieldId}_options_container`);
            const $labelInput = $(`#${fieldId}_label`);
            
            // Устанавливаем фиксированное название для специальных типов полей
            if (fixedFieldNames[fieldType]) {
                $labelInput.val(fixedFieldNames[fieldType]);
                $labelInput.prop('readonly', true);
                
                // Добавляем или обновляем скрытое поле с именем поля
                let $hiddenFieldName = $(`#${fieldId}`).find('input[name="fields[' + fieldId + '][field_name]"]');
                if ($hiddenFieldName.length === 0) {
                    $labelInput.after(`<input type="hidden" name="fields[${fieldId}][field_name]" value="${fieldType}">`);
                } else {
                    $hiddenFieldName.val(fieldType);
                }
            } else {
                // Для обычных полей разрешаем редактирование (если это не системное поле)
                const isSystemField = $(`#${fieldId}`).hasClass('border-primary');
                if (!isSystemField) {
                    $labelInput.prop('readonly', false);
                    // Удаляем скрытое поле field_name для обычных полей
                    $(`#${fieldId}`).find('input[name="fields[' + fieldId + '][field_name]"]').not('[name*="system"]').remove();
                }
            }
            
            if (['select', 'checkbox', 'radio', 'fuehrerschein', 'arbeitszeit'].includes(fieldType)) {
                $optionsContainer.show();
            } else {
                $optionsContainer.hide();
            }
            
            // Специальная настройка для типа "fuehrerschein"
            if (fieldType === 'fuehrerschein') {
                // Автоматически заполняем опции для водительских прав
                const $optionsTextarea = $(`#${fieldId}_options_container textarea`);
                if (!$optionsTextarea.val()) {
                    $optionsTextarea.val('Klasse A (Motorräder)\nKlasse B (PKW)\nKlasse C (LKW)\nKlasse D (Busse)\nKlasse BE (PKW mit Anhänger)\nKlasse CE (LKW mit Anhänger)\nKlasse DE (Busse mit Anhänger)');
                }
                $optionsContainer.show();
            }
            
            // Специальная настройка для типа "arbeitszeit"
            if (fieldType === 'arbeitszeit') {
                // Автоматически заполняем опции для рабочего времени
                const $optionsTextarea = $(`#${fieldId}_options_container textarea`);
                if (!$optionsTextarea.val()) {
                    $optionsTextarea.val('Vollzeit\nTeilzeit\nMinijob\nMidijob\nSchichtarbeit\nGleitzeit\nBefristet\nUnbefristet\nWerkstudent');
                }
                $optionsContainer.show();
            }
            
            // Для типа "liste" ничего особенного не делаем - это обычное поле
        },
        
        // Удалить поле шаблона
        removeTemplateField: function(fieldId) {
            const $field = $(`#${fieldId}`);
            
            // Проверяем, не является ли это системным или специальным полем
            if ($field.hasClass('border-primary') || $field.hasClass('border-success') || $field.data('field-name')) {
                alert('System- und Spezialfelder können nicht entfernt werden!');
                return;
            }
            
            if (confirm('Sind Sie sicher, dass Sie dieses Feld entfernen möchten?')) {
                $field.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        },
        
        // Получить данные всех полей
        getFieldsData: function() {
            const fieldsData = [];
            
            // Если не найдены .template-field, попробуем другие селекторы
            let $fields = $('.template-field');
            if ($fields.length === 0) {
                $fields = $('.field-item, .template-field-item, [data-field-id]');
            }
            
            $fields.each(function(index) {
                const $field = $(this);
                const fieldId = $field.attr('id') || $field.attr('data-field-id') || index;
                
                // Попробуем разные селекторы для поиска полей
                let typeField = $field.find(`[name="fields[${fieldId}][type]"]`);
                let labelField = $field.find(`[name="fields[${fieldId}][label]"]`);
                let requiredField = $field.find(`[name="fields[${fieldId}][required]"]`);
                let personalDataField = $field.find(`[name="fields[${fieldId}][personal_data]"]`);
                let optionsField = $field.find(`[name="fields[${fieldId}][options]"]`);
                
                // Если не найдены, попробуем поиск по индексу
                if (typeField.length === 0) {
                    typeField = $field.find(`[name*="field_${index}_type"]`);
                    labelField = $field.find(`[name*="field_${index}_label"]`);
                    requiredField = $field.find(`[name*="field_${index}_required"]`);
                    personalDataField = $field.find(`[name*="field_${index}_personal_data"]`);
                    optionsField = $field.find(`[name*="field_${index}_options"]`);
                }
                
                const fieldData = {
                    type: typeField.val() || '',
                    label: labelField.val() || '',
                    required: requiredField.is(':checked'),
                    personal_data: personalDataField.is(':checked'),
                    options: optionsField.val() || ''
                };
                
                if (fieldData.label) {
                    fieldsData.push(fieldData);
                }
            });
            
            // Если все еще нет полей, попробуем найти их по именам атрибутов
            if (fieldsData.length === 0) {
                // Ищем все элементы с именами field_*_type
                $('[name*="field_"][name*="_type"]').each(function(index) {
                    const name = $(this).attr('name');
                    const match = name.match(/field_(\d+)_type/);
                    if (match) {
                        const fieldIndex = match[1];
                        
                        const fieldData = {
                            type: $(`[name="field_${fieldIndex}_type"]`).val() || '',
                            label: $(`[name="field_${fieldIndex}_label"]`).val() || '',
                            required: $(`[name="field_${fieldIndex}_required"]`).is(':checked'),
                            personal_data: $(`[name="field_${fieldIndex}_personal_data"]`).is(':checked'),
                            options: $(`[name="field_${fieldIndex}_options"]`).val() || ''
                        };
                        
                        if (fieldData.label) {
                            fieldsData.push(fieldData);
                        }
                    }
                });
            }
            
            return fieldsData;
        },
        
        // Валидация полей
        validateFields: function() {
            let isValid = true;
            const fieldNames = [];
            
            $('.template-field').each(function() {
                const $field = $(this);
                const fieldId = $field.attr('id');
                
                const label = $field.find(`[name="fields[${fieldId}][label]"]`).val().trim();
                
                // Проверяем обязательные поля
                if (!label) {
                    $field.find(`[name="fields[${fieldId}][label]"]`).addClass('is-invalid');
                    isValid = false;
                } else {
                    $field.find(`[name="fields[${fieldId}][label]"]`).removeClass('is-invalid');
                }
            });
            
            return isValid;
        },
        
        // Очистить все поля
        clearAllFields: function() {
            $('#template-fields').empty();
            fieldCounter = 0;
        }
    };

})(jQuery);