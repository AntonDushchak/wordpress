/**
 * Neo Umfrage - JavaScript für Admin
 * Version: 1.0.0
 */

(function ($) {
    'use strict';

    // Objekt für Umfrage
    window.NeoUmfrage = {

        // Initialisierung
        init: function () {
            NeoUmfrage.bindEvents();
            NeoUmfrage.initModals();
            NeoUmfrage.loadInitialData();
        },

        // Event-Bindung
        bindEvents: function () {
            // События для модальных окон
            $(document).on('click', '.neo-umfrage-modal-close', function() {
                if (window.NeoUmfrageModals && NeoUmfrageModals.closeModal) {
                    NeoUmfrageModals.closeModal();
                }
            });
            $(document).on('click', '.neo-umfrage-modal', function (e) {
                if (e.target === this) {
                    if (window.NeoUmfrageModals && NeoUmfrageModals.closeModal) {
                        NeoUmfrageModals.closeModal();
                    }
                }
            });

            // События для форм
            $(document).on('submit', '.neo-umfrage-form', function(e) {
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleFormSubmit) {
                    NeoUmfrageModals.handleFormSubmit(e);
                }
            });

            // События для кнопок
            $(document).on('click', '.neo-umfrage-button', NeoUmfrage.handleButtonClick);

            // События для селектов
            $(document).on('change', '#survey-template-select', function() {
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleTemplateChange) {
                    NeoUmfrageModals.handleTemplateChange.call(this);
                }
            });

            // События для фильтров
            $(document).on('change', '#template-filter', function() {
                if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.filterSurveys) {
                    NeoUmfrageSurveys.filterSurveys.call(this);
                }
            });

            // События для добавления полей в шаблонах
            $(document).on('click', '.add-field-btn', function() {
                if (window.NeoUmfrageModals && NeoUmfrageModals.addField) {
                    NeoUmfrageModals.addField();
                }
            });
            $(document).on('click', '.remove-field-btn', function() {
                if (window.NeoUmfrageModals && NeoUmfrageModals.removeField) {
                    NeoUmfrageModals.removeField();
                }
            });
            $(document).on('change', '.field-type-select', function() {
                if (window.NeoUmfrageModals && NeoUmfrageModals.changeFieldType) {
                    NeoUmfrageModals.changeFieldType();
                }
            });
        },

        // Инициализация модальных окон
        initModals: function () {
            // Создаем модальные окна если их нет
            if (window.NeoUmfrageModals && NeoUmfrageModals.createModals) {
                NeoUmfrageModals.createModals();
            }
        },

        // Загрузка начальных данных
        loadInitialData: function () {
            // Загружаем анкеты для страницы анкет
            if ($('#surveys-list').length) {
                if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveys) {
                    NeoUmfrageSurveys.loadSurveys();
                }
                if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                    NeoUmfrageTemplates.loadTemplatesForFilter();
                }
            }

            // Загружаем шаблоны для страницы шаблонов
            if ($('#templates-list').length) {
                if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplates) {
                    NeoUmfrageTemplates.loadTemplates();
                }
            }

            // Загружаем статистику для страницы статистики
            if ($('#statistics-stats').length) {
                if (window.NeoUmfrageStatistics && NeoUmfrageStatistics.loadStatistics) {
                    NeoUmfrageStatistics.loadStatistics();
                }
                if (window.NeoUmfrageStatistics && NeoUmfrageStatistics.loadRecentSurveys) {
                    NeoUmfrageStatistics.loadRecentSurveys();
                }
            }
        },

        // Обработка клика по кнопкам
        handleButtonClick: function (e) {
            const $btn = $(this);
            const action = $btn.data('action');

            switch (action) {
                case 'add-survey':
                    if (window.NeoUmfrageModals && NeoUmfrageModals.openAddSurveyModal) {
                        NeoUmfrageModals.openAddSurveyModal();
                    }
                    break;
                case 'add-template':
                    if (window.NeoUmfrageModals && NeoUmfrageModals.openAddTemplateModal) {
                        NeoUmfrageModals.openAddTemplateModal();
                    }
                    break;
            }
        },

        // Показ сообщения
        showMessage: function (type, message) {
            const $message = $(`
                <div class="neo-umfrage-notice neo-umfrage-notice-${type}">
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

        // Просмотр анкеты
        viewSurvey: function (surveyId) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.createViewSurveyModal) {
                NeoUmfrageSurveys.createViewSurveyModal(surveyId);
            }
        },

        // Редактирование шаблона
        editTemplate: function (templateId) {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.editTemplate) {
                NeoUmfrageTemplates.editTemplate(templateId);
            }
        },

        // Проверка прав на редактирование
        canEdit: function () {
            if (!neoUmfrageAjax.userRoles) {
                console.warn('userRoles не определены, разрешаем редактирование');
                return true;
            }
            return neoUmfrageAjax.userRoles.includes('administrator') ||
                neoUmfrageAjax.userRoles.includes('neo_editor');
        },

        // Проверка прав на удаление
        canDelete: function () {
            if (!neoUmfrageAjax.userRoles) {
                console.warn('userRoles не определены, разрешаем удаление');
                return true;
            }
            return neoUmfrageAjax.userRoles.includes('administrator') ||
                neoUmfrageAjax.userRoles.includes('neo_editor');
        },

        // Удаление анкеты
        deleteSurvey: function (surveyId) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.deleteSurvey) {
                NeoUmfrageSurveys.deleteSurvey(surveyId);
            }
        },

        // Редактирование анкеты
        editSurvey: function (responseId) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.editSurvey) {
                NeoUmfrageSurveys.editSurvey(responseId);
            }
        },

        // Загрузка данных анкеты для редактирования
        loadSurveyForEdit: function (responseId) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveyForEdit) {
                NeoUmfrageSurveys.loadSurveyForEdit(responseId);
            }
        },

        // Заполнение формы данными анкеты
        populateSurveyForm: function (surveyData) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.populateSurveyForm) {
                NeoUmfrageSurveys.populateSurveyForm(surveyData);
            }
        },
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

    // Инициализация при загрузке документа
    $(document).ready(function () {
        NeoUmfrage.init();
    });

})(jQuery);
