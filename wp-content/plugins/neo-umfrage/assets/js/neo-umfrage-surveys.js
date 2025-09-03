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
            
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_surveys',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    if (response && response.success) {
                        NeoUmfrageSurveys.renderSurveysList(response.data);
                    } else {
                        NeoUmfrage.showMessage('error', 'Fehler beim Laden der Umfragen');
                    }
                },
                error: function () {
                    NeoUmfrage.showMessage('error', 'Fehler beim Laden der Umfragen');
                }
            });
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

        // Отображение списка анкет
        renderSurveysList: function (surveys) {
            const $container = $('#surveys-list');

            if (!surveys || !Array.isArray(surveys)) {
                $container.html('<p>Fehler: Umfragedaten konnten nicht abgerufen werden.</p>');
                return;
            }

            if (surveys.length === 0) {
                $container.html('<p>Keine Umfragen gefunden.</p>');
                return;
            }

            let html = '<div class="neo-umfrage-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center;">';

            // Фильтр по шаблону
            html += '<div>';
            html += '<label class="neo-umfrage-label">Filter nach Vorlage:</label>';
            html += '<select id="template-filter" class="neo-umfrage-select" style="margin-left: 10px;">';
            html += '<option value="">Alle Vorlagen</option>';
            const uniqueTemplates = [...new Set(surveys.map(s => s.template_name))];
            uniqueTemplates.forEach(templateName => {
                html += `<option value="${templateName}">${templateName}</option>`;
            });
            html += '</select>';
            html += '</div>';

            // Фильтр по пользователю
            html += '<div>';
            html += '<label class="neo-umfrage-label">Filter nach Benutzer:</label>';
            html += '<select id="user-filter" class="neo-umfrage-select" style="margin-left: 10px;">';
            html += '<option value="">Alle Benutzer</option>';
            const uniqueUsers = [...new Set(surveys.map(s => s.wp_user_name))];
            uniqueUsers.forEach(userName => {
                html += `<option value="${userName}">${userName}</option>`;
            });
            html += '</select>';
            html += '</div>';

            html += '</div>';

            html += '<table class="neo-umfrage-table">';
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
                    ${NeoUmfrage.canEdit(survey.user_id) ? `<button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrage.editSurvey(${survey.response_id}, ${survey.user_id})">Bearbeiten</button>` : ''}
                    ${NeoUmfrage.canDelete(survey.user_id) ? `<button class="neo-umfrage-button neo-umfrage-button-danger" onclick="NeoUmfrage.deleteSurvey(${survey.response_id}, ${survey.user_id})">Löschen</button>` : ''}
                    <button class="neo-umfrage-button" onclick="NeoUmfrage.viewSurvey(${survey.response_id})">Anzeigen</button>
                </td>
            </tr>
        `;
            });

            html += '</tbody></table>';
            $container.html(html);
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
                            NeoUmfrage.showMessage('success', 'Umfrage erfolgreich gelöscht');
                            NeoUmfrageSurveys.loadSurveys();
                        } else {
                            NeoUmfrage.showMessage('error', 'Fehler beim Löschen der Umfrage');
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

            // Сначала загружаем шаблоны в селект
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForSelect) {
                NeoUmfrageTemplates.loadTemplatesForSelect('#survey-template-select', function () {
                    // После загрузки шаблонов устанавливаем нужный
                    $('#survey-template-select').val(templateId);

                    // Загружаем поля шаблона и заполняем их данными
                    if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplateFieldsForEdit) {
                        NeoUmfrageTemplates.loadTemplateFieldsForEdit(templateId, fields);
                    }
                });
            }

            // Заполняем обязательные поля из ответа
            if (fields && fields.length > 0) {
                fields.forEach(function (field) {
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

            // Находим имя пользователя из полей анкеты
            let surveyUserName = 'Nicht angegeben';
            if (fields && fields.length > 0) {
                fields.forEach(function (field) {
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
            html += '<p><strong>WordPress-Benutzer:</strong> ' + wpUserName + '</p>';
            html += '<p><strong>Name aus Umfrage:</strong> ' + surveyUserName + '</p>';

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
