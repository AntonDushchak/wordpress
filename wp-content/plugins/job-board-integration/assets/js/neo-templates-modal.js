/**
 * Neo Job Board Templates - Modal Management
 * Управление модальными окнами
 */

window.NeoTemplatesModal = (function($) {
    'use strict';
    
    let modalInstance = null;
    let isSubmitting = false;
    
    return {
        // Инициализация модального окна
        init: function() {
            // NeoTemplatesModal: Initializing
            
            // Полная блокировка отправки форм
            $(document).on('submit', 'form', function(e) {
                // Form submit blocked
                if (this.id === 'template-form') {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    // Template form submit blocked
                    return false;
                }
            });
            
            
            this.bindEvents();
            this.initModal();
        },
        
        // Инициализация Bootstrap модала
        initModal: function() {
            const modalElement = document.getElementById('templateModal');
            if (!modalElement) return;
            
            modalInstance = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });
            
            // Событие при закрытии модала
            modalElement.addEventListener('hidden.bs.modal', () => {
                this.resetModal();
            });
        },
        
        // Привязка событий
        bindEvents: function() {
            // NeoTemplatesModal: Binding events
            const self = this;
            
            // Кнопка добавления нового шаблона
            $(document).on('click', '#add-template-btn', function(e) {
                // Add template button clicked
                e.preventDefault();
                e.stopPropagation();
                self.openAddTemplateModal();
                return false;
            });
            
            // Кнопка добавления поля
            $(document).on('click', '#add-field-btn', function(e) {
                // Add field button clicked
                e.preventDefault();
                e.stopPropagation();
                if (window.NeoTemplatesFields) {
                    window.NeoTemplatesFields.addTemplateField();
                }
                return false;
            });
            
            // Полная блокировка отправки формы шаблона
            $(document).on('submit', '#template-form', function(e) {
                // Template form submit blocked
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            });
            
            // Кнопка сохранения - с максимальной блокировкой
            $(document).on('click', '#save-template-btn', function(e) {
                // Save button clicked
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Убираем все обработчики с формы временно
                $('#template-form').off('submit');
                
                // Вызываем сохранение
                self.saveTemplate();
                return false;
            });

            // Кнопка отмены
            $(document).on('click', '#cancel-template-btn', () => {
                this.closeModal();
            });
        },
        
        // Открыть модал для добавления шаблона
        openAddTemplateModal: function() {
            // Opening add template modal
            this.resetModal();
            this.setTitle('Neue Vorlage erstellen');
            
            // Автоматически добавляем обязательное поле Name
            const self = this;
            setTimeout(() => {
                if (window.NeoTemplatesFields) {
                    if (typeof window.NeoTemplatesFields.ensureNameField === 'function') {
                        window.NeoTemplatesFields.ensureNameField();
                    } else {
                        // Fallback - добавляем поле вручную
                        self.addNameFieldFallback();
                    }
                } else {
                    console.error('NeoTemplatesFields not available, using fallback');
                    self.addNameFieldFallback();
                }
            }, 200);
            
            // Убеждаемся, что кнопка сохранения существует и видима
            setTimeout(function() {
                const saveBtn = $('#save-template-btn');
                // Save button status checked
            }, 100);
            
            this.openModal();
            // Modal opened successfully
        },
        
        // Fallback функция для добавления поля Name
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
        
        // Открыть модальное окно
        openModal: function() {
            if (modalInstance) {
                modalInstance.show();
            }
        },
        
        // Закрыть модальное окно
        closeModal: function() {
            if (modalInstance) {
                modalInstance.hide();
            }
        },
        
        // Установить заголовок модала
        setTitle: function(title) {
            $('#templateModalLabel').text(title);
        },
        
        // Сброс модального окна
        resetModal: function() {
            // Очищаем форму
            $('#template-form')[0].reset();
            
            // Удаляем скрытое поле ID
            $('input[name="template_id"]').remove();
            
            // Очищаем поля шаблона
            $('#template-fields').empty();
            if (window.NeoTemplatesFields && window.NeoTemplatesFields.clearAllFields) {
                window.NeoTemplatesFields.clearAllFields();
            }
            
            // Убираем классы валидации
            $('.is-invalid').removeClass('is-invalid');
            
            // Сбрасываем состояние кнопки
            this.resetSaveButton();
        },
        
        // Сохранение шаблона
        saveTemplate: function() {
            // saveTemplate: Starting
            
            if (isSubmitting) {
                // Already submitting
                return;
            }
            
            // Валидация полей
            if (window.NeoTemplatesFields && !window.NeoTemplatesFields.validateFields()) {
                // Validation failed
                return;
            }
            
            // Валидация основных полей
            const name = $('#template-name').val().trim();
            if (!name) {
                $('#template-name').addClass('is-invalid');
                alert('Bitte geben Sie einen Vorlagennamen ein.');
                return;
            }
            
            this.setSaveButtonLoading(true);
            isSubmitting = true;
            
            // Собираем данные формы
            const formData = {
                action: 'neo_job_board_save_template',
                nonce: neoJobBoardAjax.nonce,
                name: name,
                description: $('#template-description').val(),
                is_active: 1
            };
            // Form data collected
            
            // Добавляем ID шаблона если редактируем
            const templateId = $('input[name="template_id"]').val();
            if (templateId) {
                formData.template_id = templateId;
            }
            
            // Добавляем поля шаблона
            if (window.NeoTemplatesFields) {
                const fieldsData = window.NeoTemplatesFields.getFieldsData();
                
                if (fieldsData && fieldsData.length > 0) {
                    // Отправляем поля как отдельные параметры, а не JSON строку
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
            
            // Отправляем AJAX запрос
            // Отправляем запрос
            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    // Пробуем парсить JSON если это строка
                    let parsedResponse = response;
                    if (typeof response === 'string') {
                        try {
                            parsedResponse = JSON.parse(response);
                        } catch (e) {
                            alert('Сервер вернул неверный формат ответа: ' + response.substring(0, 200));
                            return;
                        }
                    }
                    
                    if (parsedResponse.success) {
                        alert('Vorlage erfolgreich gespeichert!');
                        this.closeModal();
                        
                        // Обновляем список шаблонов
                        setTimeout(function() {
                            if (window.reloadTemplatesList) {
                                window.reloadTemplatesList();
                            } else if (window.NeoTemplatesCore && window.NeoTemplatesCore.loadTemplates) {
                                window.NeoTemplatesCore.loadTemplates();
                            } else {
                                // Ручная загрузка шаблонов
                                $.ajax({
                                    url: neoJobBoardAjax.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'neo_job_board_get_templates',
                                        nonce: neoJobBoardAjax.nonce
                                    },
                                    success: function(response) {
                                        // Manual templates reload response
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
                        alert('Fehler beim Speichern: ' + (parsedResponse.data || 'Unbekannter Fehler'));
                    }
                },
                error: (xhr, status, error) => {
                    alert('AJAX Fehler beim Speichern der Vorlage: ' + error);
                },
                complete: () => {
                    this.setSaveButtonLoading(false);
                    isSubmitting = false;
                }
            });
        },
        
        // Установка состояния загрузки для кнопки сохранения
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
        
        // Сброс кнопки сохранения
        resetSaveButton: function() {
            const $btn = $('#save-template-btn');
            $btn.prop('disabled', false);
            $btn.html('<i class="bi bi-check-lg me-2"></i>Speichern');
        }
    };

})(jQuery);

// Глобальная функция для открытия модала добавления шаблона
window.openAddTemplateModal = function() {
    if (window.NeoTemplatesModal) {
        window.NeoTemplatesModal.openAddTemplateModal();
    }
};