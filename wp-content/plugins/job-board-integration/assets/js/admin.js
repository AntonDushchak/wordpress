(function($) {
    'use strict';

    const JBI = {
        init: function() {
            this.bindEvents();
            this.loadStats();
        },

        bindEvents: function() {
            $(document).off('click', '#test-connection-btn').on('click', '#test-connection-btn', this.testConnection.bind(this));
            $(document).off('click', '#save-settings-btn').on('click', '#save-settings-btn', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.saveSettings(e);
            });
            $(document).off('click', '#manual-sync-btn').on('click', '#manual-sync-btn', this.manualSync.bind(this));
        },

        saveSettings: function(e) {
            if (e && e.preventDefault) {
                e.preventDefault();
                e.stopPropagation();
            }

            const selectedInterval = $('#sync_interval').val();

            const formData = {
                action: 'jbi_save_settings',
                nonce: jbiAjax.nonce,
                api_url: $('input[name="api_url"]').val(),
                api_key: $('input[name="api_key"]').val(),
                auto_send: $('#auto_send').is(':checked') ? 1 : 0,
                sync_interval: selectedInterval
            };

            $.post(jbiAjax.ajaxurl, formData, function(response) {
                if (response.success) {
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess(jbiAjax.strings.success + ': ' + response.data.message);
                    } else {
                        alert(jbiAjax.strings.success + ': ' + response.data.message);
                    }
                    
                    const intervalToSet = response.data.sync_interval || selectedInterval;
                    $('#sync_interval').val(intervalToSet);
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError(jbiAjax.strings.error + ': ' + response.data.message);
                    } else {
                        alert(jbiAjax.strings.error + ': ' + response.data.message);
                    }
                }
            }).fail(function(xhr, status, error) {
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Fehler beim Speichern: ' + error);
                } else {
                    alert('Fehler beim Speichern: ' + error);
                }
            });
            
            return false;
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

        manualSync: function() {
            const $btn = $('#manual-sync-btn');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Synchronisierung läuft...');

            $.post(jbiAjax.ajaxurl, {
                action: 'jbi_manual_sync',
                nonce: jbiAjax.nonce
            }, (response) => {
                $btn.prop('disabled', false).text(originalText);
                if (response.success) {
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError(response.data.message || 'Fehler bei der Synchronisierung');
                    } else {
                        alert(response.data.message || 'Fehler bei der Synchronisierung');
                    }
                }
            }).fail(function() {
                $btn.prop('disabled', false).text(originalText);
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Fehler bei der Synchronisierung');
                } else {
                    alert('Fehler bei der Synchronisierung');
                }
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
        JBI.init();
    });

})(jQuery);

