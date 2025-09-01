/**
 * Neo Umfrage - Основной JavaScript файл
 * Версия: 1.0.0
 */

(function($) {
    'use strict';

    // Основной объект плагина
    window.NeoUmfrage = {
        
        // Инициализация
        init: function() {
            this.bindEvents();
            this.loadInitialData();
        },

        // Привязка событий
        bindEvents: function() {
            // События для форм анкет
            $(document).on('submit', '.neo-umfrage-form', this.handleFormSubmit);
            $(document).on('change', '.neo-umfrage-option input', this.handleOptionChange);
            $(document).on('input', '.neo-umfrage-text-input, .neo-umfrage-textarea', this.handleTextInput);
            
            // События для кнопок
            $(document).on('click', '.neo-umfrage-button', this.handleButtonClick);
            
            // События для модальных окон
            $(document).on('click', '.neo-umfrage-modal-close', this.closeModal);
            $(document).on('click', '.neo-umfrage-modal', function(e) {
                if (e.target === this) {
                    NeoUmfrage.closeModal();
                }
            });
        },

        // Загрузка начальных данных
        loadInitialData: function() {
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

        // Обработка отправки формы
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Показываем индикатор загрузки
            $submitBtn.prop('disabled', true).html('<span class="neo-umfrage-loading"></span>Отправка...');
            
            // Собираем данные формы
            const formData = NeoUmfrage.collectFormData($form);
            
            // Отправляем AJAX запрос
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_save_survey',
                    nonce: neoUmfrageAjax.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrage.showMessage('success', response.data.message);
                        $form[0].reset();
                    } else {
                        NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                    }
                },
                error: function() {
                    NeoUmfrage.showMessage('error', neoUmfrageAjax.strings.error);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Отправить');
                }
            });
        },

        // Обработка изменения опций
        handleOptionChange: function() {
            const $option = $(this).closest('.neo-umfrage-option');
            const $question = $option.closest('.neo-umfrage-question');
            
            // Обновляем прогресс
            NeoUmfrage.updateProgress();
            
            // Добавляем анимацию
            $option.addClass('neo-umfrage-fade-in');
            setTimeout(() => $option.removeClass('neo-umfrage-fade-in'), 500);
        },

        // Обработка ввода текста
        handleTextInput: function() {
            const $input = $(this);
            const $question = $input.closest('.neo-umfrage-question');
            
            // Валидация в реальном времени
            NeoUmfrage.validateInput($input);
            
            // Обновляем прогресс
            NeoUmfrage.updateProgress();
        },

        // Обработка клика по кнопкам
        handleButtonClick: function(e) {
            const $btn = $(this);
            const action = $btn.data('action');
            
            switch(action) {
                case 'next':
                    NeoUmfrage.nextQuestion();
                    break;
                case 'prev':
                    NeoUmfrage.prevQuestion();
                    break;
                case 'submit':
                    NeoUmfrage.submitSurvey();
                    break;
                case 'reset':
                    NeoUmfrage.resetSurvey();
                    break;
            }
        },

        // Сбор данных формы
        collectFormData: function($form) {
            const data = {
                template_id: $form.data('template-id'),
                title: $form.find('[name="title"]').val(),
                description: $form.find('[name="description"]').val(),
                responses: {}
            };
            
            // Собираем ответы на вопросы
            $form.find('.neo-umfrage-question').each(function() {
                const $question = $(this);
                const questionId = $question.data('question-id');
                const questionType = $question.data('question-type');
                
                let answer = '';
                
                if (questionType === 'radio') {
                    answer = $question.find('input[type="radio"]:checked').val() || '';
                } else if (questionType === 'checkbox') {
                    const checked = $question.find('input[type="checkbox"]:checked');
                    answer = checked.map(function() { return $(this).val(); }).get();
                } else if (questionType === 'text' || questionType === 'textarea') {
                    answer = $question.find('input, textarea').val() || '';
                }
                
                data.responses[questionId] = answer;
            });
            
            return data;
        },

        // Валидация ввода
        validateInput: function($input) {
            const value = $input.val().trim();
            const required = $input.prop('required');
            const $errorMsg = $input.siblings('.neo-umfrage-error-message');
            
            // Удаляем предыдущие ошибки
            $input.removeClass('neo-umfrage-error');
            $errorMsg.remove();
            
            // Проверяем обязательные поля
            if (required && !value) {
                $input.addClass('neo-umfrage-error');
                $input.after('<div class="neo-umfrage-error-message">Это поле обязательно для заполнения</div>');
                return false;
            }
            
            // Дополнительные проверки в зависимости от типа поля
            if (value) {
                if ($input.attr('type') === 'email' && !NeoUmfrage.isValidEmail(value)) {
                    $input.addClass('neo-umfrage-error');
                    $input.after('<div class="neo-umfrage-error-message">Введите корректный email</div>');
                    return false;
                }
                
                if ($input.attr('minlength') && value.length < parseInt($input.attr('minlength'))) {
                    $input.addClass('neo-umfrage-error');
                    $input.after('<div class="neo-umfrage-error-message">Минимальная длина: ' + $input.attr('minlength') + ' символов</div>');
                    return false;
                }
            }
            
            return true;
        },

        // Проверка email
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        // Обновление прогресса
        updateProgress: function() {
            const $progressBar = $('.neo-umfrage-progress-bar');
            if (!$progressBar.length) return;
            
            const totalQuestions = $('.neo-umfrage-question').length;
            let answeredQuestions = 0;
            
            $('.neo-umfrage-question').each(function() {
                const $question = $(this);
                const questionType = $question.data('question-type');
                let isAnswered = false;
                
                if (questionType === 'radio') {
                    isAnswered = $question.find('input[type="radio"]:checked').length > 0;
                } else if (questionType === 'checkbox') {
                    isAnswered = $question.find('input[type="checkbox"]:checked').length > 0;
                } else if (questionType === 'text' || questionType === 'textarea') {
                    isAnswered = $question.find('input, textarea').val().trim() !== '';
                }
                
                if (isAnswered) answeredQuestions++;
            });
            
            const progress = (answeredQuestions / totalQuestions) * 100;
            $progressBar.css('width', progress + '%');
        },

        // Следующий вопрос
        nextQuestion: function() {
            const $current = $('.neo-umfrage-question.active');
            const $next = $current.next('.neo-umfrage-question');
            
            if ($next.length) {
                $current.removeClass('active').addClass('neo-umfrage-hidden');
                $next.removeClass('neo-umfrage-hidden').addClass('active neo-umfrage-fade-in');
                
                // Прокручиваем к следующему вопросу
                $('html, body').animate({
                    scrollTop: $next.offset().top - 100
                }, 500);
            }
        },

        // Предыдущий вопрос
        prevQuestion: function() {
            const $current = $('.neo-umfrage-question.active');
            const $prev = $current.prev('.neo-umfrage-question');
            
            if ($prev.length) {
                $current.removeClass('active').addClass('neo-umfrage-hidden');
                $prev.removeClass('neo-umfrage-hidden').addClass('active neo-umfrage-fade-in');
                
                // Прокручиваем к предыдущему вопросу
                $('html, body').animate({
                    scrollTop: $prev.offset().top - 100
                }, 500);
            }
        },

        // Отправка анкеты
        submitSurvey: function() {
            const $form = $('.neo-umfrage-form');
            if ($form.length) {
                $form.submit();
            }
        },

        // Сброс анкеты
        resetSurvey: function() {
            if (confirm('Вы уверены, что хотите сбросить все ответы?')) {
                $('.neo-umfrage-form')[0].reset();
                $('.neo-umfrage-progress-bar').css('width', '0%');
                $('.neo-umfrage-question').removeClass('active neo-umfrage-hidden').first().addClass('active');
                $('.neo-umfrage-error').removeClass('neo-umfrage-error');
                $('.neo-umfrage-error-message').remove();
            }
        },

        // Закрытие модального окна
        closeModal: function() {
            $('.neo-umfrage-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        },

        // Показ сообщения
        showMessage: function(type, message) {
            const $message = $(`
                <div class="neo-umfrage-message neo-umfrage-message-${type}">
                    ${message}
                </div>
            `);
            
            $('.neo-umfrage-container').prepend($message);
            
            // Автоматически скрываем сообщение через 5 секунд
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Загрузка статистики
        loadStatistics: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
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
        loadSurveys: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_surveys',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrage.renderSurveysList(response.data);
                    }
                }
            });
        },

        // Загрузка шаблонов
        loadTemplates: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrage.renderTemplatesList(response.data);
                    }
                }
            });
        },

        // Загрузка шаблонов для фильтра
        loadTemplatesForFilter: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $filter = $('#template-filter');
                        response.data.forEach(template => {
                            $filter.append(`<option value="${template.id}">${template.name}</option>`);
                        });
                    }
                }
            });
        },

        // Загрузка последних анкет
        loadRecentSurveys: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NeoUmfrage.renderRecentSurveys(response.data.recent_surveys);
                    }
                }
            });
        },

        // Отображение списка анкет
        renderSurveysList: function(surveys) {
            const $container = $('#surveys-list');
            
            if (surveys.length === 0) {
                $container.html('<p>Анкеты не найдены.</p>');
                return;
            }
            
            let html = '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Название</th><th>Шаблон</th><th>Дата создания</th><th>Действия</th></tr></thead>';
            html += '<tbody>';
            
            surveys.forEach(survey => {
                html += `
                    <tr>
                        <td>${survey.title}</td>
                        <td>${survey.template_name || 'Не указан'}</td>
                        <td>${new Date(survey.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrage.editSurvey(${survey.id})">Редактировать</button>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-danger" onclick="NeoUmfrage.deleteSurvey(${survey.id})">Удалить</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },

        // Отображение списка шаблонов
        renderTemplatesList: function(templates) {
            const $container = $('#templates-list');
            
            if (templates.length === 0) {
                $container.html('<p>Шаблоны не найдены.</p>');
                return;
            }
            
            let html = '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Название</th><th>Описание</th><th>Дата создания</th><th>Действия</th></tr></thead>';
            html += '<tbody>';
            
            templates.forEach(template => {
                html += `
                    <tr>
                        <td>${template.name}</td>
                        <td>${template.description || 'Нет описания'}</td>
                        <td>${new Date(template.created_at).toLocaleDateString()}</td>
                        <td>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-secondary" onclick="NeoUmfrage.editTemplate(${template.id})">Редактировать</button>
                            <button class="neo-umfrage-admin-button neo-umfrage-admin-button-danger" onclick="NeoUmfrage.deleteTemplate(${template.id})">Удалить</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },

        // Отображение последних анкет
        renderRecentSurveys: function(surveys) {
            const $container = $('#recent-surveys');
            
            if (surveys.length === 0) {
                $container.html('<p>Анкеты не найдены.</p>');
                return;
            }
            
            let html = '<table class="neo-umfrage-admin-table">';
            html += '<thead><tr><th>Название</th><th>Шаблон</th><th>Дата создания</th></tr></thead>';
            html += '<tbody>';
            
            surveys.forEach(survey => {
                html += `
                    <tr>
                        <td>${survey.title}</td>
                        <td>${survey.template_name || 'Не указан'}</td>
                        <td>${new Date(survey.created_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            $container.html(html);
        },

        // Редактирование анкеты
        editSurvey: function(surveyId) {
            // Здесь будет логика редактирования анкеты
            console.log('Редактирование анкеты:', surveyId);
        },

        // Удаление анкеты
        deleteSurvey: function(surveyId) {
            if (confirm(neoUmfrageAjax.strings.confirm_delete)) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_survey',
                        nonce: neoUmfrageAjax.nonce,
                        survey_id: surveyId
                    },
                    success: function(response) {
                        if (response.success) {
                            NeoUmfrage.showMessage('success', response.data.message);
                            NeoUmfrage.loadSurveys();
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        },

        // Редактирование шаблона
        editTemplate: function(templateId) {
            // Здесь будет логика редактирования шаблона
            console.log('Редактирование шаблона:', templateId);
        },

        // Удаление шаблона
        deleteTemplate: function(templateId) {
            if (confirm(neoUmfrageAjax.strings.confirm_delete)) {
                $.ajax({
                    url: neoUmfrageAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_umfrage_delete_template',
                        nonce: neoUmfrageAjax.nonce,
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            NeoUmfrage.showMessage('success', response.data.message);
                            NeoUmfrage.loadTemplates();
                        } else {
                            NeoUmfrage.showMessage('error', response.data.message || neoUmfrageAjax.strings.error);
                        }
                    }
                });
            }
        }
    };

    // Глобальные функции для вызова из HTML
    window.openAddSurveyModal = function() {
        // Здесь будет логика открытия модального окна добавления анкеты
        console.log('Открытие модального окна добавления анкеты');
    };

    window.openAddTemplateModal = function() {
        // Здесь будет логика открытия модального окна добавления шаблона
        console.log('Открытие модального окна добавления шаблона');
    };

    // Инициализация при загрузке документа
    $(document).ready(function() {
        NeoUmfrage.init();
    });

})(jQuery);
