(function ($) {
    'use strict';
    window.NeoUmfrageSurveys = {
        init: function() {
            this.initializeModals();
        },

        initializeModals: function() {
            if (window.NeoUmfrageModals && NeoUmfrageModals.createModals) {
                NeoUmfrageModals.createModals();
            }
        },

        loadSurveys: function () {
            this.init();
            this.initSurveysDataTable();
        },

        filterSurveys: function () {
            const templateName = $(this).val();
            
            const $surveysContainer = $('#surveys-list');
            $surveysContainer.html('<div class="neo-umfrage-loading"></div>');
            
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_surveys',
                    nonce: neoUmfrageAjax.nonce,
                    template_name: templateName
                },
                success: function (response) {
                    if (response && response.success) {
                        NeoUmfrageSurveys.renderSurveysList(response.data);
                    } else {
                        $surveysContainer.html('<div class="neo-umfrage-error">Ошибка загрузки анкет</div>');
                    }
                },
                error: function () {
                    $surveysContainer.html('<div class="neo-umfrage-error">Ошибка загрузки анкет</div>');
                }
            });
        },

        initSurveysDataTable: function () {
            const $container = $('#surveys-list');
            
            $container.html(`
                <div class="neo-umfrage-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">
                    <div>
                        <label class="neo-umfrage-label">Filter nach Vorlage:</label>
                        <select id="filter-template" class="neo-umfrage-select" style="margin-left: 10px;">
                            <option value="">Alle Vorlagen</option>
                        </select>
                    </div>
                    <div>
                        <label class="neo-umfrage-label">Filter nach Benutzer:</label>
                        <select id="filter-user" class="neo-umfrage-select" style="margin-left: 10px;">
                            <option value="">Alle Benutzer</option>
                        </select>
                    </div>
                </div>
                <table id="surveys-table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vorlage</th>
                            <th>Benutzer</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                </table>
            `);

            const table = $('#surveys-table').DataTable({
                ajax: {
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'neo_umfrage_get_surveys';
                        d.nonce = neoUmfrageAjax.nonce;
                        d.template_id = $('#filter-template').val();
                        d.user_id = $('#filter-user').val();
                    },
                    dataSrc: function(json) {
                        if (json.success && json.data && Array.isArray(json.data)) {
                            return json.data;
                        }
                        return [];
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'template_name' },
                    { data: 'wp_user_name' },
                    { 
                        data: 'submitted_at',
                        render: function(data, type, row) {
                            if (type === 'display' && data) {
                                return new Date(data).toLocaleDateString('de-DE', {
                                    timeZone: 'Europe/Berlin',
                                    year: 'numeric',
                                    month: '2-digit',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'actions',                        
                        orderable: false, 
                        searchable: false,
                        render: function(data, type, row) {
                            let actions = '';
                            if (NeoUmfrage.canEdit(row.user_id)) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-secondary neo-umfrage-button-icon" onclick="NeoUmfrage.editSurvey(${row.response_id}, ${row.user_id})" title="Bearbeiten"><i class="bi bi-pencil"></i></button> `;
                            }
                            if (NeoUmfrage.canDelete(row.user_id)) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-danger neo-umfrage-button-icon" onclick="NeoUmfrage.deleteSurvey(${row.response_id}, ${row.user_id})" title="Löschen"><i class="bi bi-trash"></i></button> `;
                            }
                            actions += `<button class="neo-umfrage-button neo-umfrage-button-icon" onclick="NeoUmfrage.viewSurvey(${row.response_id})" title="Anzeigen"><i class="bi bi-eye"></i></button>`;
                            return actions;
                        }
                    }
                ],
                language: {url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/de-DE.json'},
                processing: true,
                serverSide: false,
                responsive: true,
                searching: false,
            });

            this.loadFilterOptions();

            $('#filter-template, #filter-user').on('change', function() {
                table.ajax.reload();
            });
        },

        loadFilterOptions: function() {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                NeoUmfrageTemplates.loadTemplatesForFilter();
            }
            
            this.loadUsersForFilter();
        },

        loadUsersForFilter: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_users',
                    nonce: neoUmfrageAjax.nonce
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
                            
                            const roles = neoUmfrageAjax.userRoles;
                            const isAdmin = Array.isArray(roles) ? roles.includes('administrator') : roles === 'administrator';
                            
                            if (neoUmfrageAjax.currentUserId && !isAdmin) {
                                $filter.val(neoUmfrageAjax.currentUserId);
                                if ($.fn.DataTable && $('#surveys-table').length) {
                                    $('#surveys-table').DataTable().ajax.reload();
                                }
                            }
                        }
                    }
                }
            });
        },


        deleteSurvey: function (surveyId) {
            if (confirm('Sind Sie sicher, dass Sie diese Umfrage löschen möchten?')) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_survey',
                        nonce: neoUmfrageAjax.nonce,
                        survey_id: surveyId
                    },
                    success: function (response) {
                        if (response && response.success) {
                            const message = (response.data && response.data.message) ? response.data.message : 'Umfrage erfolgreich gelöscht';
                            NeoUmfrage.showMessage('success', message);
                            if ($.fn.DataTable && $('#surveys-table').length) {
                                $('#surveys-table').DataTable().ajax.reload();
                            } else {
                                NeoUmfrageSurveys.loadSurveys();
                            }
                        } else {
                            const errorMessage = (response && response.data && response.data.message) ? response.data.message : 'Fehler beim Löschen der Umfrage';
                            NeoUmfrage.showMessage('error', errorMessage);
                        }
                    },
                    error: function () {
                        NeoUmfrage.showMessage('error', 'Fehler beim Löschen der Umfrage');
                    }
                });
            }
        },

        editSurvey: function (responseId) {
            if (window.NeoUmfrageModals && NeoUmfrageModals.openEditSurveyModal) {
                NeoUmfrageModals.openEditSurveyModal();
            }

            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveyForEdit) {
                NeoUmfrageSurveys.loadSurveyForEdit(responseId);
            }
        },

        loadSurveyForEdit: function (responseId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_survey_data',
                    nonce: neoUmfrageAjax.nonce,
                    survey_id: responseId
                },
                success: function (response) {
                    if (response && response.success) {
                        if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.populateSurveyForm) {
                            NeoUmfrageSurveys.populateSurveyForm(response.data);
                        }
                    } else {
                        NeoUmfrage.showMessage('error', 'Fehler beim Laden der Umfragedaten');
                    }
                },
                error: function () {
                    NeoUmfrage.showMessage('error', 'Fehler beim Laden der Umfragedaten');
                }
            });
        },

        populateSurveyForm: function (surveyData) {
            const response = surveyData.response;
            const fields = surveyData.response_data_object || surveyData.response_data;
            const templateId = surveyData.template_id;
            const templateName = surveyData.template_name;

            if (templateName) {
                $('#survey-template-select').html(`<option value="${templateId}" selected>${templateName}</option>`);
            }

            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplateFieldsForEdit) {
                NeoUmfrageTemplates.loadTemplateFieldsForEdit(templateId, fields);
            }

            $('#survey-template-fields').show();

            if (!$('#response-id-field').length) {
                $('#survey-form').append('<input type="hidden" id="response-id-field" name="response_id" value="' + response.id + '">');
            } else {
                $('#response-id-field').val(response.id);
            }
        },

        createViewSurveyModal: function (surveyId) {
            $('#view-survey-modal').remove();

            const modalHtml = `
        <div id="view-survey-modal" class="neo-umfrage-modal">
            <div class="neo-umfrage-modal-content">
                <div class="neo-umfrage-modal-header">
                    <h3 class="neo-umfrage-modal-title">Umfrage anzeigen</h3>
                    <button class="neo-umfrage-modal-close">&times;</button>
                </div>
                <div class="neo-umfrage-modal-body">
                    <div id="survey-view-content">
                        <div class="neo-umfrage-loading"></div>
                    </div>
                </div>
                <div class="neo-umfrage-modal-footer">
                    <button type="button" class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrageModals.closeModal()">Schließen</button>
                </div>
            </div>
        </div>
    `;

            $('body').append(modalHtml);
            $('#view-survey-modal').fadeIn(300);
            $('body').addClass('modal-open');

            NeoUmfrageSurveys.loadSurveyData(surveyId);
        },

        loadSurveyData: function (surveyId) {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_survey_data',
                    nonce: neoUmfrageAjax.nonce,
                    survey_id: surveyId
                },
                success: function (response) {
                    if (response && response.success) {
                        NeoUmfrageSurveys.displaySurveyData(response.data);
                    } else {
                        $('#survey-view-content').html('<div class="neo-umfrage-error">Fehler beim Laden der Umfragedaten</div>');
                    }
                },
                error: function () {
                    $('#survey-view-content').html('<div class="neo-umfrage-error">Fehler beim Laden der Umfragedaten</div>');
                }
            });
        },

        displaySurveyData: function (response) {
            const responseData = response.response;
            const fields = response.response_data;
            const templateName = response.template_name;

            let wpUserName = 'Unbekannter Benutzer';
            if (responseData.first_name && responseData.last_name) {
                wpUserName = responseData.first_name + ' ' + responseData.last_name;
            } else if (responseData.user_display_name) {
                wpUserName = responseData.user_display_name;
            }

            let html = '<div class="neo-umfrage-survey-view">';
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
            html += '<p><strong>Benutzer:</strong> ' + wpUserName + '</p>';

            const filledFields = fields ? fields.filter(function (field) {
                return field.label && field.value && field.value !== '' && field.value !== null;
            }) : [];

            if (filledFields.length > 0) {
                html += '<h4>Ausgefüllte Felder</h4>';
                html += '<div class="neo-umfrage-response-fields">';

                filledFields.forEach(function (field) {
                    html += '<div class="neo-umfrage-field">';
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







    };
})(jQuery);