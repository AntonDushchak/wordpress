/**
 * Neo Umfrage - Основной JavaScript файл
 * Версия: 1.0.0
 */

(function ($) {
    'use strict';

    // Основной объект плагина
    window.NeoUmfrage = {

        // Инициализация
        init: function () {
            this.bindEvents();
            this.loadInitialData();
        },

        // Привязка событий
        bindEvents: function () {
            // События для форм анкет
            $(document).on('submit', '.neo-umfrage-form', function(e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleFormSubmit) {
                    NeoUmfrageModals.handleFormSubmit(e, $(this));
                }
            });



            // События для кнопок добавления шаблонов
            $(document).on('click', 'button[onclick*="openAddTemplateModal"]', function (e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.openAddTemplateModal) {
                    NeoUmfrageModals.openAddTemplateModal();
                } else {
                    console.error('NeoUmfrageModals.openAddTemplateModal is not found');
                }
            });

            // События для модальных окон
            $(document).on('click', '.neo-umfrage-modal-close', this.closeModal);
            $(document).on('click', '.neo-umfrage-modal', function (e) {
                if (e.target === this) {
                    NeoUmfrage.closeModal();
                }
            });

            // События для кнопок управления полями в шаблонах
            $(document).on('click', '.add-field-btn', function (e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.addField) {
                    NeoUmfrageModals.addField();
                }
            });

            $(document).on('click', '.remove-field-btn', function (e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.removeField) {
                    NeoUmfrageModals.removeField.call(this);
                }
            });

            // События для изменения типа поля
            $(document).on('change', '.field-type-select', function (e) {
                if (window.NeoUmfrageModals && NeoUmfrageModals.changeFieldType) {
                    NeoUmfrageModals.changeFieldType.call(this);
                }
            });

            // События для изменения шаблона в модальном окне анкеты
            $(document).on('change', '#survey-template-select', function (e) {
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleTemplateChange) {
                    NeoUmfrageModals.handleTemplateChange.call(this);
                }
            });
        },

        // Загрузка начальных данных
        loadInitialData: function () {
            // Загружаем статистику для главной страницы
            if ($('#main-stats').length) {
                this.loadStatistics();
            }

            // Загружаем анкеты для страницы анкет
            if ($('#surveys-list').length) {
                this.loadSurveys();
                this.loadTemplatesForFilter();
            }

            // Загружаем шаблоны для страницы шаблонов
            if ($('#templates-list').length) {
                this.loadTemplates();
            }

            // Загружаем статистику для страницы статистики
            if ($('#statistics-stats').length) {
                this.loadStatistics();
                this.loadRecentSurveys();
            }
        },

        // Проверка прав доступа
        canEdit: function (objectUserId) {
            const rolesRaw = neoUmfrageAjax.userRoles;
            const roles = Array.isArray(rolesRaw)
                ? rolesRaw
                : (rolesRaw && typeof rolesRaw === 'object')
                    ? Object.values(rolesRaw)
                    : rolesRaw
                        ? [rolesRaw]
                        : [];
            const currentUserId = neoUmfrageAjax.currentUserId;

            if (roles.includes('administrator') || roles.includes('neo-editor')) {
                return true;
            }

            // Проверка владельца объекта (например, анкеты)
            if (objectUserId && currentUserId && objectUserId == currentUserId) {
                return true;
            }

            return false;
        },

        canDelete: function (objectUserId) {
            // Проверка ролей
            const rolesRaw = neoUmfrageAjax.userRoles;
            const roles = Array.isArray(rolesRaw)
                ? rolesRaw
                : (rolesRaw && typeof rolesRaw === 'object')
                    ? Object.values(rolesRaw)
                    : rolesRaw
                        ? [rolesRaw]
                        : [];
            const currentUserId = neoUmfrageAjax.currentUserId;

            if (roles.includes('administrator') || roles.includes('neo-editor')) {
                return true;
            }

            // Проверка владельца объекта
            if (objectUserId && currentUserId && objectUserId == currentUserId) {
                return true;
            }

            return false;
        },
        
        // Закрытие модального окна
        closeModal: function () {
            if (window.NeoUmfrageModals && NeoUmfrageModals.closeModal) {
                NeoUmfrageModals.closeModal();
            }
        },

        // Показ сообщения
        showMessage: function (type, message) {
            const $message = $(`
                <div class="neo-umfrage-message neo-umfrage-message-${type}">
                    ${message}
                </div>
            `);

            $('.neo-umfrage-container').prepend($message);

            // Автоматически скрываем сообщение через 5 секунд
            setTimeout(() => {
                $message.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
        },

        // Загрузка статистики
        loadStatistics: function () {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
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
        loadSurveys: function () {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveys) {
                NeoUmfrageSurveys.loadSurveys();
            }
        },

        // Загрузка шаблонов
        loadTemplates: function () {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplates) {
                NeoUmfrageTemplates.loadTemplates();
            }
        },

        // Загрузка шаблонов для фильтра
        loadTemplatesForFilter: function () {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                NeoUmfrageTemplates.loadTemplatesForFilter();
            }
        },

        // Отображение списка шаблонов
        renderTemplatesList: function (templates) {
            const $container = $('#templates-list');

            if (templates.length === 0) {
                $container.html('<p>Keine Vorlagen gefunden.</p>');
                return;
            }

            let html = '<table class="neo-umfrage-table">';
            html += '<thead><tr><th>Titel</th><th>Beschreibung</th><th>Erstellungsdatum</th><th>Aktionen</th></tr></thead>';
            html += '<tbody>';

            templates.forEach(template => {
                html += `
                    <tr>
                        <td>${template.name}</td>
                        <td>${template.description || 'Keine Beschreibung'}</td>
                        <td>${new Date(template.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="neo-umfrage-button neo-umfrage-button-secondary" onclick="NeoUmfrage.editTemplate(${template.id})">Bearbeiten</button>
                            <button class="neo-umfrage-button neo-umfrage-button-danger" onclick="NeoUmfrage.deleteTemplate(${template.id})">Löschen</button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        // Отображение последних анкет
        renderStatistics: function (stats) {
            const $container = $('#recent-surveys');

            let html = '<div class="neo-umfrage-stats">';
            html += `<div class="stat-item"><strong>Всего анкет:</strong><span>${stats.total_surveys || 0}</span></div>`;
            html += `<div class="stat-item"><strong>Всего шаблонов:</strong><span>${stats.total_templates || 0}</span></div>`;
            html += `<div class="stat-item"><strong>Всего ответов:</strong><span>${stats.total_responses || 0}</span></div>`;
            html += '</div>';

            $container.html(html);
        },

        // Редактирование анкеты
        editSurvey: function (surveyId, userId) {
            // Проверяем права на редактирование
            if (!this.canEdit(userId)) {
                this.showMessage('error', 'Sie haben keine Berechtigung, diese Umfrage zu bearbeiten');
                return;
            }

            // Открываем модальное окно для редактирования анкеты
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.editSurvey) {
                NeoUmfrageSurveys.editSurvey(surveyId);
            }
        },

        // Удаление анкеты
        deleteSurvey: function (surveyId, userId) {
            // Проверяем права на удаление
            if (!this.canDelete(userId)) {
                this.showMessage('error', 'Sie haben keine Berechtigung, diese Umfrage zu löschen');
                return;
            }

            if (confirm(neoUmfrageAjax.strings.confirm_delete)) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_survey',
                        nonce: neoUmfrageAjax.nonce,
                        survey_id: surveyId
                    },
                    success: function (response) {
                        if (response.success) {
                            NeoUmfrage.showMessage('success', response.data.message);
                            // Обновляем DataTable если он существует
                            if ($.fn.DataTable && $('#surveys-table').length) {
                                $('#surveys-table').DataTable().ajax.reload();
                            } else if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveys) {
                                NeoUmfrageSurveys.loadSurveys();
                            }
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        },



        // Просмотр анкеты
        viewSurvey: function (surveyId) {
            // Открываем модальное окно для просмотра анкеты
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.createViewSurveyModal) {
                NeoUmfrageSurveys.createViewSurveyModal(surveyId);
            }
        }
    };

    // Глобальные функции для вызова из HTML
    window.openAddSurveyModal = function () {
        if (window.NeoUmfrageModals && NeoUmfrageModals.openAddSurveyModal) {
            NeoUmfrageModals.openAddSurveyModal();
        }
    };

    window.openAddTemplateModal = function () {
        if (window.NeoUmfrageModals && NeoUmfrageModals.openAddTemplateModal) {
            NeoUmfrageModals.openAddTemplateModal();
        }
    };

    // Глобальные функции для редактирования и удаления шаблонов
    window.editTemplate = function (templateId, userId) {
        // Проверяем права на редактирование
        if (!NeoUmfrage.canEdit(userId)) {
            NeoUmfrage.showMessage('error', 'Sie haben keine Berechtigung, diese Vorlage zu bearbeiten');
            return;
        }

        // Делегируем в модуль шаблонов
        if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.editTemplate) {
            NeoUmfrageTemplates.editTemplate(templateId);
        }
    };

    window.deleteTemplate = function (templateId, userId) {
        // Проверяем права на удаление
        if (!NeoUmfrage.canDelete(userId)) {
            NeoUmfrage.showMessage('error', 'Sie haben keine Berechtigung, diese Vorlage zu löschen');
            return;
        }

        // Делегируем в модуль шаблонов
        if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.deleteTemplate) {
            NeoUmfrageTemplates.deleteTemplate(templateId);
        }
    };

    // Инициализация при загрузке документа
    $(document).ready(function () {
        NeoUmfrage.init();
    });

})(jQuery);
