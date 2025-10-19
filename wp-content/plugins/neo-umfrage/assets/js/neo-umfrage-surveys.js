(function ($) {
    'use strict';
    window.NeoUmfrageSurveys = {

        // Инициализация
        init: function() {
            this.initializeModals();
        },

        // Инициализация модальных окон для анкет
        initializeModals: function() {
            // Создаем модальные окна если они еще не созданы
            if (window.NeoUmfrageModals && NeoUmfrageModals.createModals) {
                NeoUmfrageModals.createModals();
            }
        },

        // Загрузка анкет
        loadSurveys: function () {
            // Инициализируем модальные окна при первой загрузке
            this.init();
            
            // Инициализируем DataTables
            this.initSurveysDataTable();
        },

        // Фильтрация анкет по шаблону
        filterSurveys: function () {
            const templateName = $(this).val();
            
            // Показываем индикатор загрузки
            const $surveysContainer = $('#surveys-list');
            $surveysContainer.html('<div class="neo-umfrage-loading"></div>');
            
            // Отправляем AJAX запрос для получения отфильтрованных анкет
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

        // Инициализация DataTables для анкет
        initSurveysDataTable: function () {
            const $container = $('#surveys-list');
            
            // Создаем HTML для DataTables
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

            // Инициализируем DataTable
            const table = $('#surveys-table').DataTable({
                ajax: {
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'neo_umfrage_get_surveys';
                        d.nonce = neoUmfrageAjax.nonce;
                        d.template_id = $('#filter-template').val();
                        d.user_id = $('#filter-user').val();
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
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrage.editSurvey(${row.response_id}, ${row.user_id})">Bearbeiten</button> `;
                            }
                            if (NeoUmfrage.canDelete(row.user_id)) {
                                actions += `<button class="neo-umfrage-button neo-umfrage-button-danger" onclick="NeoUmfrage.deleteSurvey(${row.response_id}, ${row.user_id})">Löschen</button> `;
                            }
                            actions += `<button class="neo-umfrage-button" onclick="NeoUmfrage.viewSurvey(${row.response_id})">Anzeigen</button>`;
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

            // Загружаем опции для фильтров сначала
            this.loadFilterOptions();

            // Обновление таблицы при смене фильтра
            $('#filter-template, #filter-user').on('change', function() {
                table.ajax.reload();
            });
        },

        // Загрузка опций для фильтров
        loadFilterOptions: function() {
            // Загружаем шаблоны для фильтра
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                NeoUmfrageTemplates.loadTemplatesForFilter();
            }
            
            // Загружаем пользователей для фильтра
            this.loadUsersForFilter();
        },

        // Загрузка пользователей для фильтра
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
                            response.data.forEach(user => {

                                $filter.append(`<option value="${user.ID}">${user.display_name}</option>`);
                            });
                            
                            // Устанавливаем текущего пользователя по умолчанию (только для не-администраторов)
                            const roles = neoUmfrageAjax.userRoles;
                            const isAdmin = Array.isArray(roles) ? roles.includes('administrator') : roles === 'administrator';
                            
                            if (neoUmfrageAjax.currentUserId && !isAdmin) {
                                $filter.val(neoUmfrageAjax.currentUserId);
                                // Перезагружаем DataTable с новым фильтром
                                if ($.fn.DataTable && $('#surveys-table').length) {
                                    $('#surveys-table').DataTable().ajax.reload();
                                }
                            }
                        }
                    }
                }
            });
        },



        // Удаление анкеты
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
                            // Обновляем DataTable
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

        // Редактирование анкеты
        editSurvey: function (responseId) {
            // Открываем модальное окно для редактирования анкеты
            if (window.NeoUmfrageModals && NeoUmfrageModals.openEditSurveyModal) {
                NeoUmfrageModals.openEditSurveyModal();
            }

            // Загружаем данные анкеты для редактирования
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveyForEdit) {
                NeoUmfrageSurveys.loadSurveyForEdit(responseId);
            }
        },

        // Загрузка данных анкеты для редактирования
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

        // Заполнение формы данными анкеты
        populateSurveyForm: function (surveyData) {
            const response = surveyData.response;
            const fields = surveyData.response_data;
            const templateId = surveyData.template_id; // Получаем template_id из данных
            const templateName = surveyData.template_name; // Получаем название шаблона

            // При редактировании показываем только название шаблона (селект отключен)
            if (templateName) {
                $('#survey-template-select').html(`<option value="${templateId}" selected>${templateName}</option>`);
            }

            // Загружаем поля шаблона и заполняем их данными
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplateFieldsForEdit) {
                NeoUmfrageTemplates.loadTemplateFieldsForEdit(templateId, fields);
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

        // Создание модального окна для просмотра анкеты
        createViewSurveyModal: function (surveyId) {
            // Удаляем существующее модальное окно если есть
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

            // Загружаем данные анкеты
            NeoUmfrageSurveys.loadSurveyData(surveyId);
        },

        // Загрузка данных анкеты для просмотра
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

        // Отображение данных анкеты
        displaySurveyData: function (response) {
            const responseData = response.response;
            const fields = response.response_data;
            const templateName = response.template_name;

            // Получаем имя пользователя WordPress
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

            // Фильтруем только заполненные поля (исключаем пустые значения)
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