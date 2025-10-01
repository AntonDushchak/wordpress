/**
 * Neo Job Board - Job Application Submission
 * Обработка отправки анкеты соискателя
 */

// Neo Job Board: neo-job-submit.js loaded

window.NeoJobSubmit = (function($) {
    'use strict';
    
    // Neo Job Board: NeoJobSubmit module initializing
    let isSubmitting = false;
    
    return {
        // Инициализация обработчика отправки
        init: function() {
            // NeoJobSubmit: Initializing
            this.bindSubmitEvent();
        },
        
        // Привязка события отправки формы
        bindSubmitEvent: function() {
            // NeoJobSubmit: Binding submit events
            
            // Блокируем стандартную отправку формы
            $('#jobApplicationForm').on('submit', (e) => {
                // Form submit blocked
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Обработчик кнопки отправки
            $(document).on('click', '#submit-application-btn', (e) => {
                // Submit button clicked
                e.preventDefault();
                e.stopPropagation();
                this.submitApplication();
                return false;
            });
            
        },
        
        // Отправка анкеты
        submitApplication: function() {
            if (isSubmitting) {
                return;
            }
            
            if (!this.validateForm()) {
                return;
            }
            
            isSubmitting = true;
            this.setSubmitButtonLoading(true);
            
            const formData = this.collectFormData();

            $.ajax({
                url: neoJobBoardAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_job_board_submit_application',
                    nonce: neoJobBoardAjax.nonce,
                    ...formData
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccessMessage(response.data.message || 'Bewerbung erfolgreich eingereicht!');
                        this.resetForm();
                    } else {
                        this.showErrorMessage(response.data || 'Fehler beim Einreichen der Bewerbung.');
                    }
                },
                error: (xhr, status, error) => {
                    this.showErrorMessage('Fehler beim Einreichen der Bewerbung. Bitte versuchen Sie es später erneut.');
                },
                complete: () => {
                    isSubmitting = false;
                    this.setSubmitButtonLoading(false);
                }
            });
        },
        
        // Валидация формы
        validateForm: function() {
            let isValid = true;
            const requiredFields = $('#jobApplicationForm [required]');
            
            // Проверяем обязательные поля
            requiredFields.each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('is-invalid');
                    isValid = false;
                } else {
                    $field.removeClass('is-invalid');
                }
            });
            
            // Проверяем email
            const emailField = $('input[type="email"]');
            if (emailField.length && emailField.val()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.val())) {
                    emailField.addClass('is-invalid');
                    isValid = false;
                }
            }
            
            // Проверяем телефон
            const phoneField = $('input[type="tel"]');
            if (phoneField.length && phoneField.val()) {
                const phoneRegex = /^[\d\s\+\-\(\)]+$/;
                if (!phoneRegex.test(phoneField.val())) {
                    phoneField.addClass('is-invalid');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                this.showErrorMessage('Bitte füllen Sie alle erforderlichen Felder korrekt aus.');
                
                // Прокручиваем к первому невалидному полю
                const firstInvalid = $('.is-invalid').first();
                if (firstInvalid.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalid.offset().top - 100
                    }, 500);
                }
            }
            
            return isValid;
        },
        
        // Сбор данных формы
        collectFormData: function() {
            const formData = {};
            
            // Обрабатываем сложные поля (experience, education, languages, rights)
            this.collectComplexFields(formData);
            
            // Основные поля
            $('#jobApplicationForm').find('input, textarea, select').each(function() {
                const $field = $(this);
                const originalName = $field.attr('name');
                
                // Пропускаем поля сложных типов - они уже обработаны
                if (originalName && (
                    originalName.startsWith('experience[') ||
                    originalName.startsWith('education[') ||
                    originalName.startsWith('languages[') ||
                    originalName.startsWith('rights[')
                )) {
                    return; // Пропускаем эти поля
                }
                
                const value = $field.val();
                
                // Получаем label поля для определения правильного имени
                const label = $field.closest('.mb-3').find('label').text().trim().replace('*', '').trim();
                
                // Field found
                
                // Маппинг полей на основе их labels
                let fieldName = originalName;
                if (label && (label.toLowerCase().includes('name') || label === 'Name')) {
                    fieldName = 'full_name';
                } else if (label === 'E-Mail' || label === 'Email') {
                    fieldName = 'email';
                } else if (label === 'Telefon' || label === 'Phone') {
                    fieldName = 'phone';
                } else if (label === 'Adresse' || label === 'Address') {
                    fieldName = 'address';
                } else if (label === 'Gewünschte Position') {
                    fieldName = 'desired_position';
                }
                
                // Mapped field name
                
                if (originalName) {
                    if ($field.attr('type') === 'checkbox') {
                        if (originalName.endsWith('[]')) {
                            // Множественные checkbox
                            if (!formData[fieldName]) {
                                formData[fieldName] = [];
                            }
                            if ($field.is(':checked')) {
                                formData[fieldName].push($field.val());
                            }
                        } else {
                            formData[fieldName] = $field.is(':checked') ? 1 : 0;
                        }
                    } else if ($field.attr('type') === 'radio') {
                        if ($field.is(':checked')) {
                            formData[fieldName] = $field.val();
                        }
                    } else {
                        formData[fieldName] = $field.val();
                    }
                }
            });
            
            // Добавляем ID выбранного шаблона
            const templateId = $('#template-select').val();
            if (templateId) {
                formData.template_id = templateId;
                // Template ID added
            }
            
            // Удаляем пустые массивы и пустые значения
            Object.keys(formData).forEach(function(key) {
                if (
                    (Array.isArray(formData[key]) && formData[key].length === 0) ||
                    formData[key] === "" ||
                    formData[key] === null ||
                    formData[key] === undefined
                ) {
                    delete formData[key];
                }
            });
            // Final form data collected
            return formData;
        },

        // Сбор данных сложных полей
        collectComplexFields: function(formData) {
            // Collecting complex fields
            
            // Experience data
            formData.experience = this.collectExperienceData();
            
            // Education data
            formData.education = this.collectEducationData();
            
            // Languages data
            formData.languages = this.collectLanguagesData();
        
            // Rights data
            formData.rights = this.collectRightsData();
        },

        // Сбор данных прав
        collectRightsData: function() {
            const rights = [];
            $('.right-item').each(function(index) {
                const $item = $(this);
                const right = {
                    type: $item.find('select[name*="[type]"]').val() || '',
                    issue_date: $item.find('input[name*="[issue_date]"]').val() || ''
                };
                
                // Добавляем только если есть тип права
                if (right.type) {
                    formData.rights.push(right);
                }
            });
            
        },
        
        // Сбор данных опыта работы
        collectExperienceData: function() {
            const experience = [];
            
            $('.experience-item').each(function() {
                const $item = $(this);
                const experienceData = {};
                
                $item.find('input, select, textarea').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    
                    if (name && name.includes('experience')) {
                        const fieldName = name.match(/\[([^\]]+)\]$/)[1];
                        
                        if ($field.attr('type') === 'checkbox') {
                            experienceData[fieldName] = $field.is(':checked') ? 1 : 0;
                        } else {
                            experienceData[fieldName] = $field.val();
                        }
                    }
                });
                
                if (experienceData.position) {
                    experience.push(experienceData);
                }
            });
            
            return experience;
        },
        
        // Сбор данных образования
        collectEducationData: function() {
            const education = [];
            
            $('.education-item').each(function() {
                const $item = $(this);
                const educationData = {};
                
                $item.find('input, select, textarea').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    
                    if (name && name.includes('education')) {
                        const fieldName = name.match(/\[([^\]]+)\]$/)[1];
                        
                        if ($field.attr('type') === 'checkbox') {
                            educationData[fieldName] = $field.is(':checked') ? 1 : 0;
                        } else {
                            educationData[fieldName] = $field.val();
                        }
                    }
                });
                
                if (educationData.institution) {
                    education.push(educationData);
                }
            });
            
            return education;
        },
        
        // Сбор данных языков
        collectLanguagesData: function() {
            const languages = [];
            
            $('.language-item').each(function() {
                const $item = $(this);
                const languageData = {};
                
                $item.find('input, select').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    
                    if (name && name.includes('languages')) {
                        const fieldName = name.match(/\[([^\]]+)\]$/)[1];
                        languageData[fieldName] = $field.val();
                    }
                });
                
                if (languageData.language && languageData.level) {
                    languages.push(languageData);
                }
            });
            
            return languages;
        },
        
        // Установка состояния загрузки кнопки отправки
        setSubmitButtonLoading: function(loading) {
            const $btn = $('#jobApplicationForm button[type="submit"]');
            
            if (loading) {
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Bewerbung wird eingereicht...');
            } else {
                $btn.prop('disabled', false);
                $btn.html('<i class="bi bi-send me-2"></i>Bewerbung einreichen');
            }
        },
        
        // Показать сообщение об успехе
        showSuccessMessage: function(message) {
            const alertHtml = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            this.showMessage(alertHtml);
        },
        
        // Показать сообщение об ошибке
        showErrorMessage: function(message) {
            const alertHtml = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            this.showMessage(alertHtml);
        },
        
        // Показать сообщение
        showMessage: function(alertHtml) {
            // Удаляем предыдущие сообщения
            $('.alert').remove();
            
            // Добавляем новое сообщение в начало формы
            $('#jobApplicationForm').prepend(alertHtml);
            
            // Прокручиваем к верху формы
            $('html, body').animate({
                scrollTop: $('#jobApplicationForm').offset().top - 20
            }, 500);
            
            // Автоскрытие через 10 секунд
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 10000);
        },
        
        // Сброс формы после успешной отправки
        resetForm: function() {
            // Сбрасываем основную форму
            $('#jobApplicationForm')[0].reset();
            
            // Удаляем дополнительные поля опыта (кроме первого)
            $('.experience-item:not(:first)').remove();
            
            // Удаляем дополнительные поля образования (кроме первого)
            $('.education-item:not(:first)').remove();
            
            // Удаляем дополнительные языки (кроме первого)
            $('.language-item:not(:first)').remove();
            
            // Очищаем первые поля
            $('.experience-item').first().find('input, select, textarea').val('');
            $('.education-item').first().find('input, select, textarea').val('');
            $('.language-item').first().find('input, select').val('');
            
            // Убираем классы валидации
            $('.is-invalid').removeClass('is-invalid');
            
            // Скрываем поле даты доступности
            $('#availabilityDateContainer').hide();
            $('#availability_date_input').prop('required', false);
        }
    };

})(jQuery);

// Инициализация при загрузке страницы
jQuery(document).ready(function() {
    window.NeoJobSubmit.init();
});