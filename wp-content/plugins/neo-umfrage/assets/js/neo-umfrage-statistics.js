(function ($) {
    'use strict';
    
    window.NeoUmfrageStatistics = {
        init: function() {
            this.loadTemplatesForSelect();
            this.initEventHandlers();
        },

        initEventHandlers: function() {
            $(document).on('change', '#statistics-template-select', function() {
                const templateId = $(this).val();
                if (templateId) {
                    NeoUmfrageStatistics.loadTemplateStatistics(templateId);
                } else {
                    $('#template-statistics-container').html('<p class="neo-umfrage-info">Bitte wählen Sie eine Vorlage aus, um die Statistik anzuzeigen.</p>');
                }
            });
        },

        loadTemplatesForSelect: function() {
            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_templates',
                    nonce: neoUmfrageAjax.nonce
                },
                success: function (response) {
                    if (response && response.success && response.data && response.data.templates) {
                        const $select = $('#statistics-template-select');
                        $select.find('option:not(:first)').remove();
                        
                        response.data.templates.forEach(template => {
                            if (template.is_active == 1) {
                                $select.append(`<option value="${template.id}">${template.name}</option>`);
                            }
                        });
                    }
                }
            });
        },

        loadTemplateStatistics: function(templateId) {
            const $container = $('#template-statistics-container');
            $container.html('<div class="neo-umfrage-loading"></div>');

            $.ajax({
                url: neoUmfrageAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'neo_umfrage_get_template_statistics',
                    nonce: neoUmfrageAjax.nonce,
                    template_id: templateId
                },
                success: function (response) {
                    if (response && response.success) {
                        NeoUmfrageStatistics.renderTemplateStatistics(response.data);
                    } else {
                        $container.html('<p class="neo-umfrage-error">Fehler beim Laden der Statistik.</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="neo-umfrage-error">Fehler beim Laden der Statistik.</p>');
                }
            });
        },

        renderTemplateStatistics: function (data) {
            const $container = $('#template-statistics-container');
            
            if (!data.fields || data.fields.length === 0) {
                $container.html('<p class="neo-umfrage-info">Keine Statistikdaten verfügbar.</p>');
                return;
            }

            let html = '<div class="neo-umfrage-statistics-results">';
            html += '<h3>Statistik für Vorlage: ' + data.template_name + '</h3>';
            html += '<p class="neo-umfrage-info">Anzahl der Umfragen: ' + data.total_responses + '</p>';

            data.fields.forEach(field => {
                html += '<div class="neo-umfrage-stat-field-card">';
                html += '<h4>' + field.label + '</h4>';
                html += '<div class="neo-umfrage-stat-field-content">';
                
                if (field.type === 'text') {
                    html += NeoUmfrageStatistics.renderTextStatistics(field);
                } else if (field.type === 'number') {
                    html += NeoUmfrageStatistics.renderNumberStatistics(field);
                } else if (['radio', 'checkbox', 'select'].includes(field.type)) {
                    html += NeoUmfrageStatistics.renderChoiceStatistics(field);
                }
                
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            $container.html(html);
        },

        renderTextStatistics: function(field) {
            if (!field.statistics || field.statistics.length === 0) {
                return '<p class="neo-umfrage-muted">Keine Antworten vorhanden.</p>';
            }

            const mostCommon = field.statistics[0];
            let html = '<div class="neo-umfrage-stat-text">';
            html += '<p><strong>Häufigste Antwort:</strong> ' + mostCommon.value + '</p>';
            html += '<p><strong>Anzahl:</strong> ' + mostCommon.count + ' (' + mostCommon.percentage + '%)</p>';
            html += '</div>';
            return html;
        },

        renderNumberStatistics: function(field) {
            if (!field.statistics) {
                return '<p class="neo-umfrage-muted">Keine Antworten vorhanden.</p>';
            }

            let html = '<div class="neo-umfrage-stat-numbers">';
            html += '<div class="neo-umfrage-stat-number-row">';
            html += '<span class="neo-umfrage-stat-label">Niedrigste Zahl:</span>';
            html += '<span class="neo-umfrage-stat-value">' + (field.statistics.min || 'N/A') + '</span>';
            html += '</div>';
            html += '<div class="neo-umfrage-stat-number-row">';
            html += '<span class="neo-umfrage-stat-label">Durchschnitt:</span>';
            html += '<span class="neo-umfrage-stat-value">' + (field.statistics.avg || 'N/A') + '</span>';
            html += '</div>';
            html += '<div class="neo-umfrage-stat-number-row">';
            html += '<span class="neo-umfrage-stat-label">Höchste Zahl:</span>';
            html += '<span class="neo-umfrage-stat-value">' + (field.statistics.max || 'N/A') + '</span>';
            html += '</div>';
            html += '</div>';
            return html;
        },

        renderChoiceStatistics: function(field) {
            if (!field.statistics || field.statistics.length === 0) {
                return '<p class="neo-umfrage-muted">Keine Antworten vorhanden.</p>';
            }

            let html = '<div class="neo-umfrage-stat-choices">';
            
            field.statistics.forEach(stat => {
                html += '<div class="neo-umfrage-stat-choice-row">';
                html += '<div class="neo-umfrage-stat-choice-label">' + stat.value + '</div>';
                html += '<div class="neo-umfrage-stat-choice-bar-container">';
                html += '<div class="neo-umfrage-stat-choice-bar" style="width: ' + stat.percentage + '%"></div>';
                html += '</div>';
                html += '<div class="neo-umfrage-stat-choice-percentage">' + stat.percentage + '% (' + stat.count + ')</div>';
                html += '</div>';
            });
            
            html += '</div>';
            return html;
        },

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

        renderStatistics: function (data) {
            const $container = $('#statistics-stats');
            
            if (!data) {
                return;
            }

            $('#stats-total-surveys').text(data.total_surveys || 0);
            $('#stats-total-templates').text(data.total_templates || 0);
            $('#stats-total-responses').text(data.total_responses || 0);
        },

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
                html += '<button class="neo-umfrage-button neo-umfrage-button-icon" onclick="NeoUmfrage.viewSurvey(' + survey.response_id + ')" title="Anzeigen"><i class="bi bi-eye"></i></button>';
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            
            $container.html(html);
        }
    };
})(jQuery);