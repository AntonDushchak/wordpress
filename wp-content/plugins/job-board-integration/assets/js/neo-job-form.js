/**
 * Neo Job Board - Job Application Form Management
 * Управление формой анкеты соискателя
 */

window.NeoJobForm = (function($) {
    'use strict';
    
    let experienceCounter = 1;
    let educationCounter = 1;
    let languageCounter = 1;
    let professions = [];
    
    return {
        init: function() {
            if ($('#jobApplicationForm').length > 0) {
                this.loadProfessions();
                this.initAutocomplete();
                this.setupFieldAdders();
                this.setupAvailabilityToggle();
                this.setupCurrentToggle();
            }
        },
        
        loadProfessions: function() {
            $.post(neoJobBoardAjax.ajaxurl, {
                action: 'neo_job_board_get_professions'
            })
            .done((response) => {
                if (response.success) {
                    professions = response.data;
                }
            });
        },
        
        initAutocomplete: function() {
            $(document).on('input', '.job-position-autocomplete', function() {
                const input = $(this);
                const query = input.val().toLowerCase();
                const container = input.parent();
                
                container.find('.autocomplete-suggestions').remove();
                
                if (query.length < 2) {
                    return;
                }
                
                const filtered = professions.filter(profession => 
                    profession.toLowerCase().includes(query)
                ).slice(0, 8);
                
                if (filtered.length > 0) {
                    const suggestions = $('<div class="autocomplete-suggestions"></div>');
                    
                    filtered.forEach(profession => {
                        const item = $('<div class="autocomplete-item"></div>')
                            .text(profession)
                            .on('click', function() {
                                input.val(profession);
                                suggestions.remove();
                            });
                        suggestions.append(item);
                    });
                    
                    container.append(suggestions);
                }
            });
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.job-position-autocomplete').length) {
                    $('.autocomplete-suggestions').remove();
                }
            });
        },
        
        setupFieldAdders: function() {
            $('#addExperience').on('click', () => {
                this.addExperienceField();
            });
            
            $(document).on('click', '.remove-experience', function() {
                NeoJobForm.removeExperienceField($(this));
            });
            
            $('#addEducation').on('click', () => {
                this.addEducationField();
            });
            
            $(document).on('click', '.remove-education', function() {
                NeoJobForm.removeEducationField($(this));
            });
            
            $('#addLanguage').on('click', () => {
                this.addLanguageField();
            });
            
            $(document).on('click', '.remove-language', function() {
                $(this).closest('.language-item').remove();
            });
        },
        
        addExperienceField: function() {
            const container = $('#experienceContainer');
            const button = $('#addExperience');
            const currentCount = container.find('.experience-item').length + 1;
            
            const newItem = $(`
                <div class="experience-item border p-3 mb-3 rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Berufserfahrung #${currentCount}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-experience">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Position *</label>
                            <input type="text" class="form-control job-position-autocomplete" name="experience[${experienceCounter}][position]" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unternehmen</label>
                            <input type="text" class="form-control" name="experience[${experienceCounter}][company]">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Startdatum *</label>
                            <input type="date" class="form-control" name="experience[${experienceCounter}][start_date]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enddatum</label>
                            <input type="date" class="form-control end-date" name="experience[${experienceCounter}][end_date]">
                            <div class="form-check mt-2">
                                <input class="form-check-input current-job" type="checkbox" name="experience[${experienceCounter}][is_current]">
                                <label class="form-check-label">Aktuelle Stelle</label>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            button.before(newItem);
            experienceCounter++;
            this.updateExperienceNumbers();
        },
        
        removeExperienceField: function(button) {
            const container = $('#experienceContainer');
            const experienceItems = container.find('.experience-item');
            
            if (experienceItems.length > 1) {
                button.closest('.experience-item').remove();
                this.updateExperienceNumbers();
            } else {
                alert('Es muss mindestens eine Berufserfahrung angegeben werden');
            }
        },
        
        addEducationField: function() {
            const container = $('#educationContainer');
            const button = $('#addEducation');
            const currentCount = container.find('.education-item').length + 1;
            
            const newItem = $(`
                <div class="education-item border p-3 mb-3 rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Bildung #${currentCount}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-education">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Bildungseinrichtung *</label>
                            <input type="text" class="form-control" name="education[${educationCounter}][institution]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Abschluss/Qualifikation</label>
                            <input type="text" class="form-control" name="education[${educationCounter}][degree]">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fachrichtung</label>
                        <input type="text" class="form-control" name="education[${educationCounter}][field_of_study]">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Startdatum *</label>
                            <input type="date" class="form-control" name="education[${educationCounter}][start_date]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enddatum</label>
                            <input type="date" class="form-control education-end-date" name="education[${educationCounter}][end_date]">
                            <div class="form-check mt-2">
                                <input class="form-check-input current-education" type="checkbox" name="education[${educationCounter}][is_current]">
                                <label class="form-check-label">Aktuelle Ausbildung</label>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            button.before(newItem);
            educationCounter++;
            this.updateEducationNumbers();
        },
        
        removeEducationField: function(button) {
            const container = $('#educationContainer');
            const educationItems = container.find('.education-item');
            
            if (educationItems.length > 1) {
                button.closest('.education-item').remove();
                this.updateEducationNumbers();
            } else {
                alert('Es muss mindestens eine Bildung angegeben werden');
            }
        },
        
        addLanguageField: function() {
            const container = $('#languagesContainer');
            const button = $('#addLanguage');
            
            const newItem = $(`
                <div class="language-item row mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Sprache</label>
                        <input type="text" class="form-control" name="languages[${languageCounter}][language]" placeholder="z.B. Englisch">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Sprachniveau</label>
                        <select class="form-control" name="languages[${languageCounter}][level]">
                            <option value="">Niveau auswählen</option>
                            <option value="A1">A1 - Anfänger</option>
                            <option value="A2">A2 - Grundlegend</option>
                            <option value="B1">B1 - Mittelstufe</option>
                            <option value="B2">B2 - Obere Mittelstufe</option>
                            <option value="C1">C1 - Fortgeschritten</option>
                            <option value="C2">C2 - Muttersprachlich</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-language">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            
            if (languageCounter === 1) {
                container.find('.remove-language').first().show();
            }
            
            button.before(newItem);
            languageCounter++;
        },
        
        setupAvailabilityToggle: function() {
            $(document).on('change', 'input[name="availability_type"]', function() {
                const dateContainer = $('#availabilityDateContainer');
                const dateInput = $('#availability_date_input');
                
                if ($(this).val() === 'date') {
                    dateContainer.show();
                    dateInput.prop('required', true);
                } else {
                    dateContainer.hide();
                    dateInput.prop('required', false);
                    dateInput.val('');
                }
            });
        },
        
        setupCurrentToggle: function() {
            $(document).on('change', '.current-job', function() {
                const endDateField = $(this).closest('.experience-item').find('.end-date');
                if ($(this).is(':checked')) {
                    endDateField.prop('disabled', true).val('');
                } else {
                    endDateField.prop('disabled', false);
                }
            });
            
            $(document).on('change', '.current-education', function() {
                const endDateField = $(this).closest('.education-item').find('.education-end-date');
                if ($(this).is(':checked')) {
                    endDateField.prop('disabled', true).val('');
                } else {
                    endDateField.prop('disabled', false);
                }
            });
        },
        
        updateExperienceNumbers: function() {
            $('#experienceContainer .experience-item').each(function(index) {
                $(this).find('h6').text(`Berufserfahrung #${index + 1}`);
            });
        },
        
        updateEducationNumbers: function() {
            $('#educationContainer .education-item').each(function(index) {
                $(this).find('h6').text(`Bildung #${index + 1}`);
            });
        }
    };

})(jQuery);

jQuery(document).ready(function() {
    window.NeoJobForm.init();
});