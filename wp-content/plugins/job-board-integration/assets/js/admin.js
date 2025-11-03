(function($) {
    'use strict';

    const JBI = {
        init: function() {
            this.bindEvents();
            this.loadStats();
        },

        bindEvents: function() {
            $('#jbi-settings-form').on('submit', this.saveSettings.bind(this));
            $('#test-connection-btn').on('click', this.testConnection.bind(this));
        },

        saveSettings: function(e) {
            e.preventDefault();

            const formData = {
                action: 'jbi_save_settings',
                nonce: jbiAjax.nonce,
                api_url: $('input[name="api_url"]').val(),
                api_key: $('input[name="api_key"]').val(),
                auto_send: $('#auto_send').is(':checked') ? 1 : 0
            };

            $.post(jbiAjax.ajaxurl, formData, function(response) {
                if (response.success) {
                    alert(jbiAjax.strings.success + ': ' + response.data.message);
                } else {
                    alert(jbiAjax.strings.error + ': ' + response.data.message);
                }
            });
        },

        testConnection: function() {
            const api_url = $('input[name="api_url"]').val();
            const api_key = $('input[name="api_key"]').val();
            
            if (!api_url || !api_key) {
                const $result = $('#connection-result');
                $result.removeClass('success').addClass('error').text('Bitte füllen Sie API URL und API Key aus');
                return;
            }

            const $btn = $('#test-connection-btn');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text(jbiAjax.strings.loading);

            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_save_settings',
                nonce: jbiAjax.nonce,
                api_url: api_url,
                api_key: api_key,
                auto_send: $('#auto_send').is(':checked') ? 1 : 0
            }, () => {
                $.post(jbiAjax.ajaxurl, {
                    action: 'jbi_test_connection',
                    nonce: jbiAjax.nonce
                }, function(response) {
                    $btn.prop('disabled', false).text(originalText);

                    const $result = $('#connection-result');
                    $result.removeClass('success error').show();

                    if (response.success) {
                        let message = response.data.message;
                        if (response.data.url) {
                            message += '<br><small>URL: ' + response.data.url + '</small>';
                        }
                        $result.addClass('success').html(message);
                    } else {
                        let message = response.data.message || jbiAjax.strings.connectionError;
                        if (response.data.url) {
                            message += '<br><small>URL: ' + response.data.url + '</small>';
                        }
                        if (response.data.debug) {
                            message += '<br><small>Debug: ' + JSON.stringify(response.data.debug) + '</small>';
                        }
                        $result.addClass('error').html(message);
                    }
                });
            });
        },

        loadStats: function() {
            
        },

        loadLogs: function() {
            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_get_logs',
                nonce: jbiAjax.nonce
            }, function(response) {
                if (response.success) {
                    this.renderLogs(response.data.logs);
                }
            }.bind(this));
        },

        renderLogs: function(logs) {
            let html = '<table class="table"><thead><tr><th>Datum</th><th>Typ</th><th>Status</th><th>Nachricht</th></tr></thead><tbody>';

            logs.forEach(function(log) {
                html += '<tr>';
                html += '<td>' + log.created_at + '</td>';
                html += '<td>' + log.type + '</td>';
                html += '<td><span class="badge badge-' + log.status + '">' + log.status + '</span></td>';
                html += '<td>' + log.message + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $('#jbi-logs-list').html(html);
        }
    };

    $(document).ready(function() {
        JBI.bindEvents();
    });

})(jQuery);

