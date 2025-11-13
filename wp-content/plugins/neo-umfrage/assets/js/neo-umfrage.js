(function ($) {
    'use strict';

    window.NeoUmfrage = {

        init: function () {
            this.bindEvents();
            this.loadInitialData();
        },

        bindEvents: function () {
            $(document).on('submit', '.neo-umfrage-form', function(e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleFormSubmit) {
                    NeoUmfrageModals.handleFormSubmit(e, $(this));
                }
            });


            $(document).on('click', 'button[onclick*="openAddTemplateModal"]', function (e) {
                e.preventDefault();
                if (window.NeoUmfrageModals && NeoUmfrageModals.openAddTemplateModal) {
                    NeoUmfrageModals.openAddTemplateModal();
                }
            });

            $(document).on('click', '.neo-umfrage-modal-close', this.closeModal);
            $(document).on('click', '.neo-umfrage-modal', function (e) {
                if (e.target === this) {
                    NeoUmfrage.closeModal();
                }
            });

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

            $(document).on('change', '.field-type-select', function (e) {
                if (window.NeoUmfrageModals && NeoUmfrageModals.changeFieldType) {
                    NeoUmfrageModals.changeFieldType.call(this);
                }
            });

            $(document).on('change', '#survey-template-select', function (e) {
                if (window.NeoUmfrageModals && NeoUmfrageModals.handleTemplateChange) {
                    NeoUmfrageModals.handleTemplateChange.call(this);
                }
            });
        },

        loadInitialData: function () {
            if ($('#main-stats').length) {
                this.loadStatistics();
            }

            if ($('#surveys-list').length) {
                this.loadSurveys();
                this.loadTemplatesForFilter();
            }

            if ($('#templates-list').length) {
                this.loadTemplates();
            }

        if ($('#statistics-stats').length) {
            this.loadStatistics();
            if (window.NeoUmfrageStatistics && NeoUmfrageStatistics.init) {
                NeoUmfrageStatistics.init();
            }
        }
        },

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

            if (objectUserId && currentUserId && objectUserId == currentUserId) {
                return true;
            }

            return false;
        },

        canDelete: function (objectUserId) {
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

            if (objectUserId && currentUserId && objectUserId == currentUserId) {
                return true;
            }

            return false;
        },
        
        closeModal: function () {
            if (window.NeoUmfrageModals && NeoUmfrageModals.closeModal) {
                NeoUmfrageModals.closeModal();
            }
        },

        showMessage: function (type, message) {
            let inlineStyles = '';
            switch(type) {
                case 'success':
                    inlineStyles = 'background-color: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 12px 16px; margin: 10px 0; border-radius: 4px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);';
                    break;
                case 'error':
                    inlineStyles = 'background-color: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 12px 16px; margin: 10px 0; border-radius: 4px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);';
                    break;
                case 'warning':
                    inlineStyles = 'background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404; padding: 12px 16px; margin: 10px 0; border-radius: 4px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);';
                    break;
                case 'info':
                    inlineStyles = 'background-color: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; padding: 12px 16px; margin: 10px 0; border-radius: 4px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);';
                    break;
                default:
                    inlineStyles = 'background-color: #f8f9fa; border-left: 4px solid #6c757d; color: #495057; padding: 12px 16px; margin: 10px 0; border-radius: 4px; font-size: 14px; line-height: 1.4; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);';
            }

            const $message = $(`
                <div class="neo-umfrage-message neo-umfrage-message-${type}" style="${inlineStyles}">
                    ${message}
                </div>
            `);

            const $container = $('.neo-umfrage-container');
            
            if ($container.length > 0) {
                $container.prepend($message);
            } else {
                $('body').prepend($message);
            }

            setTimeout(() => {
                $message.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);
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
                        const stats = response.data;
                        $('#total-surveys, #stats-total-surveys').text(stats.total_surveys);
                        $('#total-templates, #stats-total-templates').text(stats.total_templates);
                        $('#total-responses, #stats-total-responses').text(stats.total_responses);
                    }
                }
            });
        },

        loadSurveys: function () {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.loadSurveys) {
                NeoUmfrageSurveys.loadSurveys();
            }
        },

        loadTemplates: function () {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplates) {
                NeoUmfrageTemplates.loadTemplates();
            }
        },

        loadTemplatesForFilter: function () {
            if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.loadTemplatesForFilter) {
                NeoUmfrageTemplates.loadTemplatesForFilter();
            }
        },

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
                            <button class="neo-umfrage-button neo-umfrage-button-secondary neo-umfrage-button-icon" onclick="NeoUmfrage.viewTemplate(${template.id})" title="Ansehen"><i class="bi bi-eye"></i></button>
                            <button class="neo-umfrage-button neo-umfrage-button-danger neo-umfrage-button-icon" onclick="NeoUmfrage.deleteTemplate(${template.id})" title="Löschen"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            $container.html(html);
        },

        renderStatistics: function (stats) {
            const $container = $('#recent-surveys');

            let html = '<div class="neo-umfrage-stats">';
            html += `<div class="stat-item"><strong>Всего анкет:</strong><span>${stats.total_surveys || 0}</span></div>`;
            html += `<div class="stat-item"><strong>Всего шаблонов:</strong><span>${stats.total_templates || 0}</span></div>`;
            html += `<div class="stat-item"><strong>Всего ответов:</strong><span>${stats.total_responses || 0}</span></div>`;
            html += '</div>';

            $container.html(html);
        },

        editSurvey: function (surveyId, userId) {
            if (!this.canEdit(userId)) {
                this.showMessage('error', 'Sie haben keine Berechtigung, diese Umfrage zu bearbeiten');
                return;
            }

            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.editSurvey) {
                NeoUmfrageSurveys.editSurvey(surveyId);
            }
        },

        deleteSurvey: function (surveyId, userId) {
            if (!this.canDelete(userId)) {
                this.showMessage('error', 'Sie haben keine Berechtigung, diese Umfrage zu löschen');
                return;
            }

            const self = this;
            if (window.NeoDash && window.NeoDash.confirm) {
                NeoDash.confirm(neoUmfrageAjax.strings.confirm_delete, {
                    type: 'danger',
                    title: 'Bestätigung des Löschens',
                    confirmText: 'Löschen',
                    cancelText: 'Abbrechen'
                }).then((confirmed) => {
                    if (!confirmed) return;
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
                });
            } else {
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
            }
        },


        viewSurvey: function (surveyId) {
            if (window.NeoUmfrageSurveys && NeoUmfrageSurveys.createViewSurveyModal) {
                NeoUmfrageSurveys.createViewSurveyModal(surveyId);
            }
        }
    };

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


    window.viewTemplate = function (templateId) {
        if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.viewTemplate) {
            NeoUmfrageTemplates.viewTemplate(templateId);
        }
    };

    window.deleteTemplate = function (templateId, userId) {
        if (!NeoUmfrage.canDelete(userId)) {
            NeoUmfrage.showMessage('error', 'Sie haben keine Berechtigung, diese Vorlage zu löschen');
            return;
        }

        if (window.NeoUmfrageTemplates && NeoUmfrageTemplates.deleteTemplate) {
            NeoUmfrageTemplates.deleteTemplate(templateId);
        }
    };

    $(document).ready(function () {
        NeoUmfrage.init();
    });

})(jQuery);