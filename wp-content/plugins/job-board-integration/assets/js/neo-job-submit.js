window.NeoJobSubmit = (function($) {
    'use strict';
    
    let isSubmitting = false;
    
    return {
        init: function() {
            this.bindSubmitEvent();
        },
        
        bindSubmitEvent: function() {
            $('#jobApplicationForm').on('submit', (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            $(document).on('click', '#submit-application-btn', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.submitApplication();
                return false;
            });
            
        },
        
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
        
        validateForm: function() {
            let isValid = true;
            const requiredFields = $('#jobApplicationForm [required]');
            
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
            
            const emailField = $('input[type="email"]');
            if (emailField.length && emailField.val()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.val())) {
                    emailField.addClass('is-invalid');
                    isValid = false;
                }
            }
            
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
                
                const firstInvalid = $('.is-invalid').first();
                if (firstInvalid.length) {
                    $('html, body').animate({
                        scrollTop: firstInvalid.offset().top - 100
                    }, 500);
                }
            }
            
            return isValid;
        },
        
        collectFormData: function() {
            const formData = {};
            
            this.collectComplexFields(formData);
            
            $('#jobApplicationForm').find('input, textarea, select').each(function() {
                const $field = $(this);
                const originalName = $field.attr('name');
                
                if (originalName && (
                    originalName.startsWith('experience[') ||
                    originalName.startsWith('education[') ||
                    originalName.startsWith('languages[') ||
                    originalName.startsWith('rights[')
                )) {
                    return;
                }
                
                const value = $field.val();
                
                const label = $field.closest('.mb-3').find('label').text().trim().replace('*', '').trim();
                
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
                
                if (originalName) {
                    if ($field.attr('type') === 'checkbox') {
                        if (originalName.endsWith('[]')) {
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
            
            const templateId = $('#template-select').val();
            if (templateId) {
                formData.template_id = templateId;
            }
            
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
            return formData;
        },

        collectComplexFields: function(formData) {
            
            formData.experience = this.collectExperienceData();
            
            formData.education = this.collectEducationData();
            
            formData.languages = this.collectLanguagesData();
        
            formData.rights = this.collectRightsData();
        },

        collectRightsData: function() {
            const rights = [];
            $('.right-item').each(function(index) {
                const $item = $(this);
                const right = {
                    type: $item.find('select[name*="[type]"]').val() || '',
                    issue_date: $item.find('input[name*="[issue_date]"]').val() || ''
                };
                
                if (right.type) {
                    formData.rights.push(right);
                }
            });
            
        },
        
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
        
        showSuccessMessage: function(message) {
            const alertHtml = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            this.showMessage(alertHtml);
        },
        
        showErrorMessage: function(message) {
            const alertHtml = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
            
            this.showMessage(alertHtml);
        },
        
        showMessage: function(alertHtml) {
            $('.alert').remove();
            
            $('#jobApplicationForm').prepend(alertHtml);
            
            $('html, body').animate({
                scrollTop: $('#jobApplicationForm').offset().top - 20
            }, 500);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 10000);
        },
        
        resetForm: function() {
            $('#jobApplicationForm')[0].reset();
            
            $('.experience-item:not(:first)').remove();
            
            $('.education-item:not(:first)').remove();
            
            $('.language-item:not(:first)').remove();
            
            $('.experience-item').first().find('input, select, textarea').val('');
            $('.education-item').first().find('input, select, textarea').val('');
            $('.language-item').first().find('input, select').val('');
            
            $('.is-invalid').removeClass('is-invalid');
            
            $('#availabilityDateContainer').hide();
            $('#availability_date_input').prop('required', false);
        }
    };

})(jQuery);

jQuery(document).ready(function() {
    window.NeoJobSubmit.init();
});