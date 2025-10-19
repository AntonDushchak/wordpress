/**
 * Neo Umfrage - Статистика
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoUmfrageStatistics = {
        
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
                        NeoUmfrageStatistics.renderStatistics(response.data);
                    }
                }
            });
        },

        // Отображение статистики
        renderStatistics: function (data) {
            const $container = $('#statistics-stats');
            
            if (!data) {
                $container.html('<p>Статистика недоступна.</p>');
                return;
            }

            let html = '<div class="neo-umfrage-stats-grid">';
            
            // Общее количество анкет
            html += '<div class="neo-umfrage-stat-card">';
            html += '<h3>Всего анкет</h3>';
            html += '<div class="neo-umfrage-stat-number">' + (data.total_surveys || 0) + '</div>';
            html += '</div>';

            // Анкеты за сегодня
            html += '<div class="neo-umfrage-stat-card">';
            html += '<h3>Сегодня</h3>';
            html += '<div class="neo-umfrage-stat-number">' + (data.today_surveys || 0) + '</div>';
            html += '</div>';

            // Анкеты за неделю
            html += '<div class="neo-umfrage-stat-card">';
            html += '<h3>За неделю</h3>';
            html += '<div class="neo-umfrage-stat-number">' + (data.week_surveys || 0) + '</div>';
            html += '</div>';

            // Анкеты за месяц
            html += '<div class="neo-umfrage-stat-card">';
            html += '<h3>За месяц</h3>';
            html += '<div class="neo-umfrage-stat-number">' + (data.month_surveys || 0) + '</div>';
            html += '</div>';

            html += '</div>';
            
            $container.html(html);
        },

        // Загрузка последних анкет
        loadRecentSurveys: function () {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_statistics',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        NeoUmfrageStatistics.renderRecentSurveys(response.data.recent_surveys);
                    }
                }
            });
        },

        // Отображение последних анкет
        renderRecentSurveys: function (surveys) {
            const $container = $('#recent-surveys');
            
            if (!surveys || surveys.length === 0) {
                $container.html('<p>Нет недавних анкет.</p>');
                return;
            }

            let html = '<div class="neo-umfrage-recent-surveys">';
            
            surveys.forEach(function(survey) {
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

                html += '<div class="neo-umfrage-recent-survey">';
                html += '<div class="neo-umfrage-recent-survey-info">';
                html += '<strong>' + name + '</strong><br>';
                html += '<span class="neo-umfrage-recent-survey-phone">' + phone + '</span><br>';
                html += '<span class="neo-umfrage-recent-survey-date">' + submittedDate + '</span>';
                html += '</div>';
                html += '<div class="neo-umfrage-recent-survey-actions">';
                html += '<button class="neo-umfrage-button" onclick="NeoUmfrage.viewSurvey(' + survey.response_id + ')">Anzeigen</button>';
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            
            $container.html(html);
        }
    };
})(jQuery);