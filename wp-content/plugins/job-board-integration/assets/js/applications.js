(function($) {
    'use strict';

    window.JBIApplications = {
        currentTemplateFields: [],
        currentTemplateId: null,
        currentApplicationId: null,

        init: function() {
            this.loadApplications();
            this.bindEvents();
        },

        bindEvents: function() {
            $('#save-application-btn').on('click', () => this.saveApplication());
            $(document).on('click', '.add-position-btn', (e) => this.addPositionEntry($(e.target).data('field-name')));
            $(document).on('click', '.add-education-btn', (e) => this.addEducationEntry($(e.target).data('field-name')));
            $(document).on('click', '.add-experience-btn', (e) => this.addExperienceEntry($(e.target).data('field-name')));
            $(document).on('click', '.add-language-btn', (e) => this.addLanguageEntry($(e.target).data('field-name')));
            $(document).on('click', '.remove-entry-btn', (e) => $(e.target).closest('[data-index]').remove());
            $(document).on('change', '.is-current-checkbox', (e) => this.toggleEndDate(e.target));
            
            $(document).on('DOMNodeInserted', '#applicationModal', () => {
                setTimeout(() => {
                    this.initFlatpickr();
                }, 100);
            });
            
            $(document).on('shown.bs.modal', '#applicationModal', () => {
                this.initFlatpickr();
            });
        },
        
        initFlatpickr: function() {
            if (typeof flatpickr === 'undefined') {
                return;
            }
            
            const locale = typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.de ? 'de' : 'default';
            
            $('#applicationModal .date-picker').each(function() {
                const $input = $(this);
                if (!$input.data('flatpickr') && !$input.data('fp-init')) {
                    $input.data('fp-init', true);
                    flatpickr(this, {
                        dateFormat: 'Y-m-d',
                        locale: locale,
                        clickOpens: true,
                        allowInput: true
                    });
                }
            });
        },
        
        toggleEndDate: function(checkbox) {
            const $checkbox = $(checkbox);
            const $endDateInput = $checkbox.closest('[data-index]').find('input.date-picker[name*="end_date"]');
            
            if ($checkbox.is(':checked')) {
                $endDateInput.prop('disabled', true).val('');
                if ($endDateInput.data('flatpickr')) {
                    const fp = $endDateInput[0]._flatpickr;
                    if (fp) {
                        fp.setDate(null, false);
                    }
                }
            } else {
                $endDateInput.prop('disabled', false);
            }
        },

        loadApplications: function() {
            const $container = $('#applications-list');
            
            if (typeof jbiAjax === 'undefined') {
                $container.html('<div class="alert alert-danger">jbiAjax ist nicht definiert</div>');
                return;
            }

            $container.html(`
                <div class="jbi-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">
                    <div>
                        <label class="form-label">Filter nach Benutzer:</label>
                        <select id="filter-user" class="form-select" style="margin-left: 10px; display: inline-block; width: auto;">
                            <option value="">Alle Benutzer</option>
                        </select>
                    </div>
                </div>
                <table id="applications-table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Hash</th>
                            <th>Template</th>
                            <th>Aktiv</th>
                            <th>Erstellt</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                </table>
            `);

            if (jbiAjax.currentUserId) {
                $('#filter-user').val(jbiAjax.currentUserId);
            }

            const table = $('#applications-table').DataTable({
                ajax: {
                    url: jbiAjax.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'jbi_get_applications';
                        d.nonce = jbiAjax.nonce;
                        d.user_id = $('#filter-user').val();
                    },
                    dataSrc: function(json) {
                        console.log('Applications response:', json);
                        if (json.success && json.data && Array.isArray(json.data.applications)) {
                            return json.data.applications;
                        }
                        return [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Applications AJAX error:', {xhr: xhr, error: error, thrown: thrown});
                    }
                },
                columns: [
                    { 
                        data: 'is_called',
                        visible: false,
                        orderable: true
                    },
                    { data: 'id' },
                    { 
                        data: 'name',
                        render: function(data, type, row) {
                            const isCalled = row.is_called == 1 || row.is_called === true;
                            const circle = isCalled ? '<span class="badge bg-warning rounded-circle me-2" style="width: 12px; height: 12px; display: inline-block;" title="Es gab einen Anruf"></span>' : '';
                            return circle + (data || '-');
                        }
                    },
                    { 
                        data: 'hash_id',
                        render: function(data) {
                            return data ? '<code>' + data.substring(0, 8) + '</code>' : '-';
                        }
                    },
                    { 
                        data: 'template_name',
                        render: function(data, type, row) {
                            return data || 'Template #' + row.template_id;
                        }
                    },
                    { 
                        data: 'is_active',
                        render: function(data) {
                            return data == 1 ? '<span class="badge bg-success">Aktiv</span>' : '<span class="badge bg-secondary">Inaktiv</span>';
                        }
                    },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return data ? new Date(data).toLocaleDateString('de-DE') : '';
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            if (!JBIApplications.canManage(row.responsible_employee)) {
                                return '';
                            }
                            
                            const activeIcon = row.is_active == 1 ? 'bi-eye' : 'bi-eye-slash';
                            const activeTitle = row.is_active == 1 ? 'Deaktivieren' : 'Aktivieren';
                            
                            return `
                                <button class="btn btn-sm btn-info me-1" onclick="JBIApplications.editApplication(${row.id})" title="Bearbeiten"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-${row.is_active == 1 ? 'success' : 'secondary'} me-1" onclick="JBIApplications.toggleActive(${row.id}, ${row.is_active == 1 ? 0 : 1})" title="${activeTitle}"><i class="bi ${activeIcon}"></i></button>
                                <button class="btn btn-sm btn-warning me-1" onclick="JBIApplications.syncApplication(${row.id})" title="Synchronisieren"><i class="bi bi-arrow-repeat"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="JBIApplications.deleteApplication(${row.id})" title="Löschen"><i class="bi bi-trash"></i></button>
                            `;
                        }
                    }
                ],
                language: {url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'},
                order: [[0, 'desc'], [1, 'desc']]
            });

            $('#filter-user').on('change', function() {
                table.ajax.reload();
            });
            
            this.loadUsersForFilter();
        },

        canManage: function(responsibleUserId) {
            const rolesRaw = jbiAjax.userRoles;
            const roles = Array.isArray(rolesRaw)
                ? rolesRaw
                : (rolesRaw && typeof rolesRaw === 'object')
                    ? Object.values(rolesRaw)
                    : rolesRaw
                        ? [rolesRaw]
                        : [];
            const currentUserId = jbiAjax.currentUserId;
            
            if (roles.includes('administrator') || roles.includes('neo_editor')) {
                return true;
            }

            if (responsibleUserId && currentUserId && responsibleUserId == currentUserId) {
                return true;
            }

            return false;
        },

        loadUsersForFilter: function() {
            $.ajax({
                url: jbiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jbi_get_users',
                    nonce: jbiAjax.nonce
                },
                success: function (response) {
                    if (response && response.success) {
                        const $filter = $('#filter-user');
                        if ($filter.length) {
                            $filter.find('option:not(:first)').remove();
                            const users = response.data.users || [];
                            users.forEach(user => {
                                $filter.append(`<option value="${user.ID}">${user.display_name}</option>`);
                            });
                            
                            if (jbiAjax.currentUserId && !$filter.val()) {
                                $filter.val(jbiAjax.currentUserId);
                            }
                        }
                    }
                }
            });
        },

        loadActiveTemplate: function() {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_get_templates',
                nonce: jbiAjax.nonce
            }, (response) => {
                if (response.success && response.data.templates) {
                    const activeTemplate = response.data.templates.find(t => t.is_active == 1);
                    if (activeTemplate) {
                        this.currentTemplateId = activeTemplate.id;
                        this.currentTemplateFields = activeTemplate.fields;
                        this.renderFields(activeTemplate.fields);
                        $('#application-fields-container').show();
                        $('#save-application-btn').show();
                        setTimeout(() => {
                            this.initFlatpickr();
                        }, 200);
                    } else {
                        $('#application-fields-container').hide();
                        alert('Kein aktives Template gefunden. Bitte erstellen und aktivieren Sie zuerst ein Template.');
                    }
                }
            });
        },

        renderFields: function(fields) {
            const $container = $('#dynamic-fields');
            $container.empty();

            fields.forEach((field, index) => {
                const fieldName = this.getFieldName(field);
                $container.append(this.generateFieldHtml(fieldName, field, index));
            });
            
            setTimeout(() => {
                if (window.NeoProfessionAutocomplete && typeof window.NeoProfessionAutocomplete.init === 'function') {
                    window.NeoProfessionAutocomplete.init();
                    $('input[data-autocomplete="professions"], input.profession-autocomplete, input[name*="[position]"]').each(function() {
                        const $input = $(this);
                        if (!$input.data('autocomplete-initialized')) {
                            $input.attr('data-autocomplete', 'professions');
                            window.NeoProfessionAutocomplete.initAutocomplete($input);
                        }
                    });
                }
                this.initFlatpickr();
            }, 100);
        },

        getFieldName: function(field) {
            if (field.name || field.field_name) {
                return field.name || field.field_name;
            }
            
            const fieldMapping = {
                'Name': 'name',
                'Wunschposition': 'wunschposition',
                'Bildung': 'bildung',
                'Berufserfahrung': 'berufserfahrung',
                'Sprachkenntnisse': 'sprachkenntnisse',
                'Führerschein': 'fuehrerschein'
            };
            
            if (fieldMapping[field.label]) {
                return fieldMapping[field.label];
            }
            
            let name = field.label.toLowerCase();
            name = name.replace(/[^a-z0-9äöüß]/gi, '_');
            name = name.replace(/_+/g, '_');
            return name;
        },

        getFieldTypeByName: function(fieldName) {
            if (!this.currentTemplateFields) {
                return null;
            }
            for (let field of this.currentTemplateFields) {
                const name = this.getFieldName(field);
                if (name === fieldName) {
                    return field.type;
                }
            }
            return null;
        },

        generateFieldHtml: function(fieldName, field, index) {
            const isRequired = field.required ? 'required' : '';
            const requiredLabel = field.required ? '<span class="text-danger">*</span>' : '';
            const isPersonal = field.personal_data ? '<small class="text-muted">(Privat)</small>' : '';
            
            let html = `<div class="mb-3">`;
            html += `<label class="form-label">${field.label} ${requiredLabel} ${isPersonal}</label>`;

            switch (field.type) {
                case 'wunschposition':
                    html += this.renderPositionField(fieldName, isRequired);
                    break;
                case 'bildung':
                    html += this.renderBildungField(fieldName, isRequired);
                    break;
                case 'berufserfahrung':
                    html += this.renderBerufserfahrungField(fieldName, isRequired);
                    break;
                case 'sprachkenntnisse':
                    html += this.renderSprachkenntnisseField(fieldName, isRequired);
                    break;
                case 'fuehrerschein':
                case 'arbeitszeit':
                    html += this.renderCheckboxField(fieldName, field);
                    break;
                case 'textarea':
                    html += `<textarea class="form-control" name="${fieldName}" rows="3" ${isRequired}></textarea>`;
                    break;
                case 'select':
                    html += this.renderSelectField(fieldName, field, isRequired);
                    break;
                case 'radio':
                    html += this.renderRadioField(fieldName, field, isRequired);
                    break;
                case 'checkbox':
                    html += this.renderCheckboxField(fieldName, field);
                    break;
                case 'email':
                    html += `<input type="email" class="form-control" name="${fieldName}" ${isRequired}>`;
                    break;
                case 'phone':
                    html += `<input type="tel" class="form-control" name="${fieldName}" ${isRequired}>`;
                    break;
                case 'date':
                    html += `<input type="text" class="form-control date-picker" name="${fieldName}" ${isRequired}>`;
                    break;
                case 'number':
                    html += `<input type="number" class="form-control" name="${fieldName}" ${isRequired}>`;
                    break;
                case 'liste':
                    html += this.renderListeField(fieldName, isRequired);
                    break;
                default:
                    html += `<input type="text" class="form-control" name="${fieldName}" ${isRequired}>`;
            }

            html += `</div>`;
            return html;
        },
        
        renderListeField: function(fieldName, required) {
            return `<textarea class="form-control" name="${fieldName}" rows="5" placeholder="Jede Zeile wird als separates Element behandelt..." ${required}></textarea>
                <small class="form-text text-muted">Jede Zeile wird als separates Element behandelt</small>`;
        },

        renderPositionField: function(fieldName, required) {
            return `
                <div class="positions-container" data-field-name="${fieldName}">
                    <div class="position-entry mb-2" data-index="0">
                        <div class="row">
                            <div class="col-md-10">
                                <input type="text" class="form-control profession-autocomplete" name="${fieldName}[0][position]" data-autocomplete="professions" placeholder="Position eingeben..." autocomplete="off" ${required}>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm add-position-btn" data-field-name="${fieldName}">
                    <i class="bi bi-plus"></i> Weitere Position
                </button>
            `;
        },

        renderBildungField: function(fieldName, required) {
            return `
                <div class="education-container" data-field-name="${fieldName}">
                    <div class="education-entry card mb-2" data-index="0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Bildungseinrichtung</label>
                                    <input type="text" class="form-control" name="${fieldName}[0][institution]">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Abschluss</label>
                                    <input type="text" class="form-control" name="${fieldName}[0][degree]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Von</label>
                                    <input type="text" class="form-control date-picker" name="${fieldName}[0][start_date]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Bis</label>
                                    <input type="text" class="form-control date-picker" name="${fieldName}[0][end_date]">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-10">
                                <div class="form-check">
                                    <input class="form-check-input is-current-checkbox" type="checkbox" name="${fieldName}[0][is_current]" value="1">
                                    <label class="form-check-label">Aktuell</label>
                                </div>
                                </div>
                                <div class="col-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm add-education-btn" data-field-name="${fieldName}">
                    <i class="bi bi-plus"></i> Weitere Bildung
                </button>
            `;
        },

        renderBerufserfahrungField: function(fieldName, required) {
            return `
                <div class="experience-container" data-field-name="${fieldName}">
                    <div class="experience-entry card mb-2" data-index="0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="${fieldName}[0][position]">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unternehmen</label>
                                    <input type="text" class="form-control" name="${fieldName}[0][company]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Von</label>
                                    <input type="text" class="form-control date-picker" name="${fieldName}[0][start_date]">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Bis</label>
                                    <input type="text" class="form-control date-picker" name="${fieldName}[0][end_date]">
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-10">
                                    <div class="form-check">
                                        <input class="form-check-input is-current-checkbox" type="checkbox" name="${fieldName}[0][is_current]" value="1">
                                        <label class="form-check-label">Aktuell tätig</label>
                                    </div>
                                </div>
                                <div class="col-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm add-experience-btn" data-field-name="${fieldName}">
                    <i class="bi bi-plus"></i> Weitere Erfahrung
                </button>
            `;
        },

        renderSprachkenntnisseField: function(fieldName, required) {
            return `
                <div class="languages-container" data-field-name="${fieldName}">
                    <div class="language-entry card mb-2" data-index="0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Sprache</label>
                                    <select class="form-select" name="${fieldName}[0][language]">
                                        <option value="">Wählen...</option>
                                        <option value="Deutsch">Deutsch</option>
                                        <option value="Englisch">Englisch</option>
                                        <option value="Spanisch">Spanisch</option>
                                        <option value="Französisch">Französisch</option>
                                        <option value="Italienisch">Italienisch</option>
                                        <option value="Niederländisch">Niederländisch</option>
                                        <option value="Polnisch">Polnisch</option>
                                        <option value="Russisch">Russisch</option>
                                        <option value="Türkisch">Türkisch</option>
                                        <option value="Chinesisch">Chinesisch</option>
                                        <option value="Japanisch">Japanisch</option>
                                        <option value="Arabisch">Arabisch</option>
                                        <option value="Portugiesisch">Portugiesisch</option>
                                        <option value="Rumänisch">Rumänisch</option>
                                        <option value="Ungarisch">Ungarisch</option>
                                        <option value="Tschechisch">Tschechisch</option>
                                        <option value="Griechisch">Griechisch</option>
                                        <option value="Schwedisch">Schwedisch</option>
                                        <option value="Dänisch">Dänisch</option>
                                        <option value="Norwegisch">Norwegisch</option>
                                        <option value="Finnisch">Finnisch</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Niveau</label>
                                    <select class="form-select" name="${fieldName}[0][level]">
                                        <option value="">Wählen...</option>
                                        <option value="A1">A1 - Anfänger</option>
                                        <option value="A2">A2 - Grundlegende Kenntnisse</option>
                                        <option value="B1">B1 - Fortgeschritten</option>
                                        <option value="B2">B2 - Selbständig</option>
                                        <option value="C1">C1 - Fachkundig</option>
                                        <option value="C2">C2 - Annähernd muttersprachlich</option>
                                        <option value="Muttersprache">Muttersprache</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm add-language-btn" data-field-name="${fieldName}">
                    <i class="bi bi-plus"></i> Weitere Sprache
                </button>
            `;
        },

        renderSelectField: function(fieldName, field, required) {
            const options = field.options ? field.options.split('\n') : [];
            let html = `<select class="form-select" name="${fieldName}" ${required}>`;
            html += `<option value="">Bitte wählen...</option>`;
            options.forEach(opt => {
                html += `<option value="${opt.trim()}">${opt.trim()}</option>`;
            });
            html += `</select>`;
            return html;
        },

        renderRadioField: function(fieldName, field, required) {
            const options = field.options ? field.options.split('\n') : [];
            let html = '';
            options.forEach((opt, i) => {
                html += `
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="${fieldName}" value="${opt.trim()}" id="${fieldName}_${i}" ${required}>
                        <label class="form-check-label" for="${fieldName}_${i}">${opt.trim()}</label>
                    </div>
                `;
            });
            return html;
        },

        renderCheckboxField: function(fieldName, field) {
            const options = field.options ? field.options.split('\n') : [];
            let html = '';
            options.forEach((opt, i) => {
                html += `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="${fieldName}[]" value="${opt.trim()}" id="${fieldName}_${i}">
                        <label class="form-check-label" for="${fieldName}_${i}">${opt.trim()}</label>
                    </div>
                `;
            });
            return html;
        },

        addPositionEntry: function(fieldName) {
            const container = $(`.positions-container[data-field-name="${fieldName}"], .positions-container`).first();
            const index = container.find('.position-entry').length;
            const html = `
                <div class="position-entry mb-2" data-index="${index}">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control profession-autocomplete" name="${fieldName}[${index}][position]" data-autocomplete="professions" placeholder="Position eingeben..." autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
            const newInput = container.find(`input[name="${fieldName}[${index}][position]"]`);
            if (window.NeoProfessionAutocomplete) {
                newInput.attr('data-autocomplete', 'professions');
                window.NeoProfessionAutocomplete.initAutocomplete(newInput);
            }
        },

        addEducationEntry: function(fieldName) {
            const container = $(`.education-container[data-field-name="${fieldName}"]`);
            const index = container.find('.education-entry').length;
            const html = `
                <div class="education-entry card mb-2" data-index="${index}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Bildungseinrichtung</label>
                                <input type="text" class="form-control" name="${fieldName}[${index}][institution]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Abschluss</label>
                                <input type="text" class="form-control" name="${fieldName}[${index}][degree]">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Von</label>
                                <input type="text" class="form-control date-picker" name="${fieldName}[${index}][start_date]">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Bis</label>
                                <input type="text" class="form-control date-picker" name="${fieldName}[${index}][end_date]">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-10">
                                <div class="form-check">
                                    <input class="form-check-input is-current-checkbox" type="checkbox" name="${fieldName}[${index}][is_current]" value="1">
                                    <label class="form-check-label">Aktuell</label>
                                </div>
                            </div>
                            <div class="col-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
            setTimeout(() => {
                this.initFlatpickr();
            }, 100);
        },

        addExperienceEntry: function(fieldName) {
            const container = $(`.experience-container[data-field-name="${fieldName}"]`);
            const index = container.find('.experience-entry').length;
            const html = `
                <div class="experience-entry card mb-2" data-index="${index}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="${fieldName}[${index}][position]">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unternehmen</label>
                                <input type="text" class="form-control" name="${fieldName}[${index}][company]">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Von</label>
                                <input type="text" class="form-control date-picker" name="${fieldName}[${index}][start_date]">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Bis</label>
                                <input type="text" class="form-control date-picker" name="${fieldName}[${index}][end_date]">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-10">
                                <div class="form-check">
                                    <input class="form-check-input is-current-checkbox" type="checkbox" name="${fieldName}[${index}][is_current]" value="1">
                                    <label class="form-check-label">Aktuell tätig</label>
                                </div>
                            </div>
                            <div class="col-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
            setTimeout(() => {
                this.initFlatpickr();
            }, 100);
        },

        addLanguageEntry: function(fieldName) {
            const container = $(`.languages-container[data-field-name="${fieldName}"]`);
            const index = container.find('.language-entry').length;
            const html = `
                <div class="language-entry card mb-2" data-index="${index}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Sprache</label>
                                <select class="form-select" name="${fieldName}[${index}][language]">
                                    <option value="">Wählen...</option>
                                    <option value="Deutsch">Deutsch</option>
                                    <option value="Englisch">Englisch</option>
                                    <option value="Spanisch">Spanisch</option>
                                    <option value="Französisch">Französisch</option>
                                    <option value="Italienisch">Italienisch</option>
                                    <option value="Niederländisch">Niederländisch</option>
                                    <option value="Polnisch">Polnisch</option>
                                    <option value="Russisch">Russisch</option>
                                    <option value="Türkisch">Türkisch</option>
                                    <option value="Chinesisch">Chinesisch</option>
                                    <option value="Japanisch">Japanisch</option>
                                    <option value="Arabisch">Arabisch</option>
                                    <option value="Portugiesisch">Portugiesisch</option>
                                    <option value="Rumänisch">Rumänisch</option>
                                    <option value="Ungarisch">Ungarisch</option>
                                    <option value="Tschechisch">Tschechisch</option>
                                    <option value="Griechisch">Griechisch</option>
                                    <option value="Schwedisch">Schwedisch</option>
                                    <option value="Dänisch">Dänisch</option>
                                    <option value="Norwegisch">Norwegisch</option>
                                    <option value="Finnisch">Finnisch</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Niveau</label>
                                <select class="form-select" name="${fieldName}[${index}][level]">
                                    <option value="">Wählen...</option>
                                    <option value="A1">A1 - Anfänger</option>
                                    <option value="A2">A2 - Grundlegende Kenntnisse</option>
                                    <option value="B1">B1 - Fortgeschritten</option>
                                    <option value="B2">B2 - Selbständig</option>
                                    <option value="C1">C1 - Fachkundig</option>
                                    <option value="C2">C2 - Muttersprachlich</option>
                                </select>
                            </div>
                            <div class="col-md-2 text-end">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-entry-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(html);
        },


        saveApplication: function() {
            const form = $('#application-form')[0];
            if (!form || !form.checkValidity()) {
                if (form) form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const fields = {};
            let responsibleEmployee = null;

            for (let [key, value] of formData.entries()) {
                if (key === 'responsible_employee') {
                    responsibleEmployee = value;
                    continue;
                }
                
                const match = key.match(/^(.+?)\[(\d+)\]\[(.+)\]$/);
                if (match) {
                    const [, fieldName, index, subField] = match;
                    if (!fields[fieldName]) fields[fieldName] = [];
                    if (!fields[fieldName][index]) fields[fieldName][index] = {};
                    fields[fieldName][index][subField] = value;
                } else if (key.endsWith('[]')) {
                    const cleanKey = key.replace('[]', '');
                    if (!fields[cleanKey]) fields[cleanKey] = [];
                    fields[cleanKey].push(value);
                } else {
                    const fieldElement = form.querySelector(`[name="${key}"]`);
                    if (fieldElement && fieldElement.tagName === 'TEXTAREA' && this.currentTemplateFields) {
                        const fieldType = this.getFieldTypeByName(key);
                        if (fieldType === 'liste') {
                            const lines = value.split('\n').map(line => line.trim()).filter(line => line.length > 0);
                            fields[key] = lines;
                        } else {
                            fields[key] = value;
                        }
                    } else {
                        fields[key] = value;
                    }
                }
            }

            const action = $('#save-application-btn').attr('data-action') === 'update' ? 'jbi_update_application' : 'jbi_create_application';
            const postData = {
                action: action,
                nonce: jbiAjax.nonce,
                fields: fields
            };

            if (action === 'jbi_update_application') {
                postData.application_id = this.currentApplicationId;
                if (responsibleEmployee) {
                    postData.responsible_employee = responsibleEmployee;
                }
            } else {
                postData.template_id = this.currentTemplateId;
            }

            $.post(jbiAjax.ajaxurl, postData, (response) => {
                if (response.success) {
                    alert(response.data.message || 'Erfolgreich gespeichert');
                    $('#applicationModal').modal('hide');
                    $('#applications-table').DataTable().ajax.reload();
                    this.resetForm();
                } else {
                    alert(response.data.message || jbiAjax.strings.error);
                }
            });
        },

        resetForm: function() {
            $('#application-form')[0]?.reset();
            $('#dynamic-fields').empty();
            $('#responsible-employee-field').remove();
            $('#application-fields-container').hide();
            $('#save-application-btn').hide();
            $('#applicationModalLabel').text('Neue Bewerbung erstellen');
            $('#save-application-btn').text('Speichern').removeAttr('data-action');
            this.currentTemplateId = null;
            this.currentTemplateFields = [];
            this.currentApplicationId = null;
        },

        editApplication: function(applicationId) {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_get_application',
                nonce: jbiAjax.nonce,
                application_id: applicationId
            }, (response) => {
                if (response.success && response.data.application) {
                    const app = response.data.application;
                    this.currentTemplateId = app.template_id;
                    this.currentTemplateFields = app.template_fields || [];
                    this.currentApplicationId = applicationId;

                    this.renderFields(this.currentTemplateFields);
                    this.fillFieldsFromData(app.fields_data || {});
                    this.renderResponsibleEmployeeField(app.responsible_employee);
                    
                    setTimeout(() => {
                        this.initFlatpickr();
                    }, 100);
                    
                    $('#applicationModalLabel').text('Bewerbung bearbeiten');
                    $('#save-application-btn').text('Speichern').attr('data-action', 'update').show();
                    $('#application-fields-container').show();
                    $('#applicationModal').modal('show');
                    
                    if ($('#applications-table').length) {
                        $('#applications-table').DataTable().ajax.reload();
                    }
                } else {
                    alert('Fehler beim Laden der Bewerbung');
                }
            });
        },

        renderResponsibleEmployeeField: function(currentResponsibleId) {
            const $form = $('#application-form');
            let $field = $('#responsible-employee-field');
            
            if ($field.length === 0) {
                $field = $(`
                    <div class="mb-3" id="responsible-employee-field">
                        <label class="form-label">Verantwortlicher Mitarbeiter</label>
                        <select class="form-select" name="responsible_employee" id="responsible-employee-select">
                            <option value="">Wählen...</option>
                        </select>
                    </div>
                `);
                $form.prepend($field);
            }
            
            const $select = $('#responsible-employee-select');
            $select.empty().append('<option value="">Wählen...</option>');
            
            $.ajax({
                url: jbiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'jbi_get_users',
                    nonce: jbiAjax.nonce
                },
                success: (response) => {
                    if (response && response.success) {
                        const users = response.data.users || [];
                        users.forEach(user => {
                            const selected = (currentResponsibleId && user.ID == currentResponsibleId) ? 'selected' : '';
                            $select.append(`<option value="${user.ID}" ${selected}>${user.display_name}</option>`);
                        });
                    }
                }
            });
        },

        fillFieldsFromData: function(fieldsData) {
            for (let [fieldName, fieldValue] of Object.entries(fieldsData)) {
                if (Array.isArray(fieldValue)) {
                    const fieldType = this.getFieldTypeByName(fieldName);
                    if (fieldType === 'liste') {
                        const textareaValue = fieldValue.join('\n');
                        $(`textarea[name="${fieldName}"]`).val(textareaValue);
                    } else if (fieldType === 'arbeitszeit' || fieldType === 'fuehrerschein') {
                        $(`input[type="checkbox"][name="${fieldName}[]"]`).prop('checked', false);
                        fieldValue.forEach(value => {
                            $(`input[type="checkbox"][name="${fieldName}[]"]`).each(function() {
                                if ($(this).val() == value || $(this).val() === value) {
                                    $(this).prop('checked', true);
                                }
                            });
                        });
                    } else if (typeof fieldValue[0] === 'object' && fieldValue.length > 0) {
                        for (let index = 0; index < fieldValue.length; index++) {
                            if (index > 0) {
                                if (fieldType === 'sprachkenntnisse') {
                                    this.addLanguageEntry(fieldName);
                                } else if (fieldType === 'bildung') {
                                    this.addEducationEntry(fieldName);
                                } else if (fieldType === 'berufserfahrung') {
                                    this.addExperienceEntry(fieldName);
                                } else if (fieldType === 'wunschposition') {
                                    this.addPositionEntry(fieldName);
                                }
                            }
                            
                            const item = fieldValue[index];
                            if (typeof item === 'object') {
                                for (let [subKey, subValue] of Object.entries(item)) {
                                    const $targetField = $(`[name="${fieldName}[${index}][${subKey}]"]`);
                                    if ($targetField.length > 0) {
                                        if ($targetField.is(':checkbox')) {
                                            $targetField.prop('checked', subValue == '1' || subValue === 1 || subValue === true || subValue === 'true');
                                        } else if ($targetField.is('select')) {
                                            $targetField.val(subValue).trigger('change');
                                        } else {
                                            $targetField.val(subValue);
                                        }
                                    }
                                }
                            }
                        }
                        
                        setTimeout(() => {
                            this.initFlatpickr();
                            if (fieldType === 'wunschposition' && window.NeoProfessionAutocomplete) {
                                $(`input[name="${fieldName}"][data-autocomplete="professions"]`).each(function() {
                                    const $input = $(this);
                                    if (!$input.data('autocomplete-initialized')) {
                                        $input.attr('data-autocomplete', 'professions');
                                        window.NeoProfessionAutocomplete.initAutocomplete($input);
                                    }
                                });
                            }
                        }, 100);
                    } else {
                        fieldValue.forEach((item, index) => {
                            const $existingField = $(`[name="${fieldName}[${index}]"]`);
                            if ($existingField.length > 0) {
                                if ($existingField.is(':checkbox')) {
                                    $existingField.prop('checked', true);
                                } else {
                                    $existingField.val(item);
                                }
                            }
                        });
                    }
                } else {
                    const $field = $(`[name="${fieldName}"]`);
                    if ($field.length > 0) {
                        if ($field.is(':checkbox')) {
                            $field.prop('checked', fieldValue == '1' || fieldValue === 1 || fieldValue === true);
                        } else {
                            $field.val(fieldValue);
                        }
                    }
                }
            }
            
            setTimeout(() => {
                if (window.NeoProfessionAutocomplete) {
                    $('input[data-autocomplete="professions"], input.profession-autocomplete, input[name*="[position]"]').each(function() {
                        const $input = $(this);
                        if (!$input.data('autocomplete-initialized')) {
                            $input.attr('data-autocomplete', 'professions');
                            window.NeoProfessionAutocomplete.initAutocomplete($input);
                        }
                    });
                }
                this.initFlatpickr();
            }, 200);
        },

        toggleActive: function(applicationId, newStatus) {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_toggle_application_active',
                nonce: jbiAjax.nonce,
                application_id: applicationId,
                is_active: newStatus
            }, (response) => {
                if (response.success) {
                    $('#applications-table').DataTable().ajax.reload();
                } else {
                    alert(response.data?.message || 'Fehler beim Ändern des Status');
                }
            });
        },

        deleteApplication: function(applicationId) {
            if (!confirm('Möchten Sie diese Bewerbung wirklich löschen?')) {
                return;
            }

            const jobFound = confirm('Hat der Kandidat eine Stelle gefunden?');
            
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_delete_application',
                nonce: jbiAjax.nonce,
                application_id: applicationId,
                job_found: jobFound ? 1 : 0
            }, (response) => {
                if (response.success) {
                    alert(response.data?.message || 'Bewerbung erfolgreich gelöscht');
                    $('#applications-table').DataTable().ajax.reload();
                } else {
                    alert(response.data?.message || 'Fehler beim Löschen');
                }
            });
        },

        viewApplication: function(applicationId) {
            this.editApplication(applicationId);
        },

        syncApplication: function(applicationId) {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_sync_application',
                nonce: jbiAjax.nonce,
                application_id: applicationId
            }, (response) => {
                if (response.success) {
                    alert(response.data?.message || 'Bewerbung erfolgreich synchronisiert');
                    $('#applications-table').DataTable().ajax.reload();
                } else {
                    alert(response.data?.message || 'Fehler beim Synchronisieren');
                }
            });
        }
    };

    window.openAddApplicationModal = function() {
        JBIApplications.resetForm();
        JBIApplications.loadActiveTemplate();
        $('#applicationModalLabel').text('Neue Bewerbung erstellen');
        $('#save-application-btn').text('Erstellen').removeAttr('data-action');
        $('#applicationModal').modal('show');
    };

    $(document).ready(function() {
        if ($('#applications-list').length) {
            JBIApplications.init();
        }
    });

})(jQuery);
