<?php

namespace NeoJobBoard;

if (!defined('ABSPATH')) {
    exit;
}

use NeoJobBoard\DataSanitizer;

class Jobs {
    
    public static function render_page() {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-list-ul"></i> Bewerbungen</h2>
                        </div>
                        <div class="card-body">
                            <div id="applications-list">
                                <div class="d-flex justify-content-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Laden...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal für Bewerbungsdetails -->
        <div class="modal fade" id="applicationModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bewerbungsdetails</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="applicationModalBody">
                        <!-- Inhalt wird über AJAX geladen -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
        // Создаем neoJobBoardAjax если его нет
        if (typeof neoJobBoardAjax === 'undefined') {
            window.neoJobBoardAjax = {
                ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('neo_job_board_nonce'); ?>',
                pluginUrl: '<?php echo plugin_dir_url(dirname(__FILE__)); ?>'
            };
        }
        
        // Ждем jQuery и инициализируем
        function waitForjQuery() {
            if (typeof jQuery !== 'undefined') {
                initJobsPage();
            } else {
                setTimeout(waitForjQuery, 100);
            }
        }
        
        function initJobsPage() {
            jQuery(document).ready(function($) {
            loadApplicationsList();

            function loadApplicationsList() {
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_get_applications',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderApplicationsList(response.data);
                        } else {
                            $('#applications-list').html('<div class="alert alert-danger">Fehler beim Laden der Bewerbungen: ' + (response.data || 'Unbekannter Fehler') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#applications-list').html('<div class="alert alert-danger">Verbindungsfehler: ' + error + '</div>');
                    }
                });
            }

            function renderApplicationsList(applications) {
                if (applications.length === 0) {
                    $('#applications-list').html('<div class="alert alert-info">Keine Bewerbungen gefunden.</div>');
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Bewerber</th>
                                    <th>Verantwortlicher</th>
                                    <th>Vorlage</th>
                                    <th>Status</th>
                                    <th>Eingangsdatum</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                applications.forEach(function(app) {
                    const statusClass = app.is_active == 1 ? 'bg-success' : 'bg-secondary';
                    const statusText = app.is_active == 1 ? 'Aktiv' : 'Inaktiv';

                    html += `
                        <tr>
                            <td><strong>#${app.id}</strong></td>
                            <td>${app.first_name} ${app.last_name}</td>
                            <td>${app.responsible_employee || '-'}</td>
                            <td>${app.template_name || 'Gelöschte Vorlage'}</td>
                            <td><span class="badge ${statusClass}">${statusText}</span></td>
                            <td>${new Date(app.created_at).toLocaleDateString('de-DE')}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="loadApplicationDetails(${app.id}, '${app.hash_id}')" title="Anzeigen">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-${app.is_active == 1 ? 'warning' : 'success'} btn-sm" onclick="toggleApplicationStatus(${app.id}, '${app.hash_id}')" title="${app.is_active == 1 ? 'Deaktivieren' : 'Aktivieren'}">
                                        <i class="bi bi-${app.is_active == 1 ? 'pause' : 'play'}-circle"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteApplication(${app.id}, '${app.hash_id}')" title="Löschen">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#applications-list').html(html);
            }

            window.loadApplicationDetails = function(applicationId, hashId) {
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_get_application_details',
                        nonce: neoJobBoardAjax.nonce,
                        application_id: applicationId,
                        hash_id: hashId
                    },
                    success: function(response) {
                        if (response.success) {
                            renderApplicationDetails(response.data);
                            const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
                            modal.show();
                        } else {
                            alert('Fehler beim Laden der Bewerbungsdetails: ' + (response.data || 'Unbekannter Fehler'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Verbindungsfehler: ' + error);
                    }
                });
            };

            function renderApplicationDetails(data) {
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Основная информация</h6>
                            <table class="table table-sm">
                                <tr><td><strong>ID заявки:</strong></td><td>#${data.application.id}</td></tr>
                                <tr><td><strong>Имя:</strong></td><td>${data.application.first_name}</td></tr>
                                <tr><td><strong>Фамилия:</strong></td><td>${data.application.last_name}</td></tr>
                                <tr><td><strong>Ответственный:</strong></td><td>
                                    <span id="responsible-display">${data.application.responsible_employee || 'Не назначен'}</span>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="editResponsible(${data.application.id})" title="Изменить">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td></tr>
                                <tr><td><strong>Статус:</strong></td><td><span class="badge bg-primary">${data.application.status}</span></td></tr>
                                <tr><td><strong>Дата подачи:</strong></td><td>${new Date(data.application.created_at).toLocaleString('ru-RU')}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Шаблон</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Название:</strong></td><td>${data.template ? data.template.name : 'Удаленный шаблон'}</td></tr>
                                <tr><td><strong>Описание:</strong></td><td>${data.template ? (data.template.description || '-') : '-'}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Примечание:</strong> Детальные данные заявки отправлены в API и не хранятся в WordPress. 
                            Для просмотра полной информации обратитесь к администратору.
                        </div>
                    </div>
                `;

                $('#applicationModalBody').html(html);
            }

            window.deleteApplication = function(applicationId, hashId) {
                if (!confirm('Sind Sie sicher, dass Sie diese Bewerbung löschen möchten?')) {
                    return;
                }

                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_delete_application',
                        nonce: neoJobBoardAjax.nonce,
                        application_id: applicationId,
                        hash_id: hashId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Bewerbung erfolgreich gelöscht');
                            loadApplicationsList();
                        } else {
                            alert('Fehler beim Löschen der Bewerbung: ' + (response.data || 'Unbekannter Fehler'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Verbindungsfehler: ' + error);
                    }
                });
            };

            window.editResponsible = function(applicationId) {
                const currentResponsible = $('#responsible-display').text();
                const newResponsible = prompt('Введите имя ответственного сотрудника:', currentResponsible === 'Не назначен' ? '' : currentResponsible);
                
                if (newResponsible !== null) {
                    $.ajax({
                        url: neoJobBoardAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'neo_job_board_update_responsible',
                            nonce: neoJobBoardAjax.nonce,
                            application_id: applicationId,
                            responsible_employee: newResponsible
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#responsible-display').text(newResponsible || 'Не назначен');
                                alert('Ответственный сотрудник обновлен');
                            } else {
                                alert('Ошибка обновления: ' + (response.data || 'Неизвестная ошибка'));
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Verbindungsfehler: ' + error);
                        }
                    });
                }
            };

            window.toggleApplicationStatus = function(applicationId, hashId) {
                if (!confirm('Sind Sie sicher, dass Sie den Status dieser Bewerbung ändern möchten?')) {
                    return;
                }

                // Определяем новый статус на основе текущего
                const currentButton = event.target.closest('button');
                const isCurrentlyActive = currentButton.classList.contains('btn-outline-warning');
                const newStatus = !isCurrentlyActive;

                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_toggle_application_status',
                        nonce: neoJobBoardAjax.nonce,
                        application_id: applicationId,
                        hash_id: hashId,
                        is_active: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Status der Bewerbung erfolgreich geändert');
                            loadApplicationsList(); // Перезагружаем список
                        } else {
                            alert('Fehler beim Ändern des Status: ' + (response.data || 'Unbekannter Fehler'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Verbindungsfehler: ' + error);
                    }
                });
            };
            });
        }
        
        // Начинаем ждать jQuery
        waitForjQuery();
        </script>
        <?php
    }

    public static function render_new_page() {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-plus-circle"></i> Neue Bewerbung erstellen</h2>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="template-select" class="form-label">Vorlage wählen:</label>
                                    <select id="template-select" class="form-select">
                                        <option value="">Vorlagen werden geladen...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div id="template-fields-container" style="display: none;">
                                <hr>
                                <h5>Поля анкеты</h5>
                                <form id="jobApplicationForm" onsubmit="return false;">
                                    <div id="dynamic-fields"></div>
                                    <button type="button" class="btn btn-primary mt-3" id="submit-application-btn">
                                        <i class="bi bi-check-circle"></i> Создать заявку
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
        // Создаем neoJobBoardAjax если его нет
        if (typeof neoJobBoardAjax === 'undefined') {
            window.neoJobBoardAjax = {
                ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('neo_job_board_nonce'); ?>',
                pluginUrl: '<?php echo plugin_dir_url(dirname(__FILE__)); ?>'
            };
        }
        
        // Ждем jQuery и инициализируем
        function waitForjQueryNew() {
            if (typeof jQuery !== 'undefined') {
                initJobsNewPage();
            } else {
                setTimeout(waitForjQueryNew, 100);
            }
        }
        
        function initJobsNewPage() {
            jQuery(document).ready(function($) {
            loadActiveTemplates();

            function loadActiveTemplates() {
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_get_active_templates',
                        nonce: neoJobBoardAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            renderTemplateSelect(response.data);
                        } else {
                            $('#template-select').html('<option value="">Fehler beim Laden der Vorlagen</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#template-select').html('<option value="">Verbindungsfehler</option>');
                    }
                });
            }

            function renderTemplateSelect(templates) {
                let html = '<option value="">Vorlage wählen...</option>';
                
                templates.forEach(function(template) {
                    html += `<option value="${template.id}">${template.name}</option>`;
                });
                
                $('#template-select').html(html);
            }

            $('#template-select').change(function() {
                const templateId = $(this).val();
                
                if (templateId) {
                    loadTemplateFields(templateId);
                } else {
                    $('#template-fields-container').hide();
                }
            });

            function loadTemplateFields(templateId) {
                $.ajax({
                    url: neoJobBoardAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'neo_job_board_get_template_fields',
                        nonce: neoJobBoardAjax.nonce,
                        template_id: templateId
                    },
                    success: function(response) {
                        if (response.success) {
                            renderTemplateFields(response.data);
                            $('#template-fields-container').show();
                        } else {
                            alert('Fehler beim Laden der Vorlagenfelder: ' + (response.data || 'Unbekannter Fehler'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Verbindungsfehler: ' + error);
                    }
                });
            }

            function renderTemplateFields(fields) {
                let html = '';
                
                fields.forEach(function(field, index) {
                    const required = field.required ? 'required' : '';
                    const requiredLabel = field.required ? '<span class="text-danger">*</span>' : '';
                    
                    console.log('🔍 Processing field:', {
                        label: field.label,
                        type: field.type,
                        required: field.required,
                        options: field.options,
                        index: index
                    });
                    
                    // Функция для преобразования опций в массив
                    const getOptionsArray = function(options) {
                        if (!options) return [];
                        if (Array.isArray(options)) return options;
                        if (typeof options === 'string') {
                            return options.split('\n').map(opt => opt.trim()).filter(opt => opt.length > 0);
                        }
                        return [];
                    };
                    
                    // Маппинг полей шаблона на имена полей для бэкенда (JavaScript версия)
                    const fieldMapping = {
                        'Name': 'full_name',
                        'Vorname': 'first_name', 
                        'Nachname': 'last_name',
                        'E-Mail': 'email',
                        'Email': 'email',
                        'Telefon': 'phone',
                        'Adresse': 'address',
                        'Gewünschte Position': 'desired_position',
                        'Verfügbarkeit': 'availability_type',
                        'Verfügbarkeitsdatum': 'availability_date',
                    };
                    
                    // Используем name поля если есть, иначе fieldMapping или генерируем по label
                    let fieldName = fieldMapping[field.label];
                    if (!fieldName) {
                        if (field.name) {
                            fieldName = field.name;
                        } else {
                            // Нормализуем немецкие символы перед генерацией имени
                            let normalizedLabel = field.label.toLowerCase()
                                .replace(/ä/g, 'ae')
                                .replace(/ö/g, 'oe')
                                .replace(/ü/g, 'ue')
                                .replace(/ß/g, 'ss');
                            
                            // Генерируем имя на основе нормализованного label
                            fieldName = normalizedLabel
                                .replace(/[^a-z0-9]/gi, '_')
                                .replace(/_+/g, '_')
                                .replace(/^_|_$/g, '');

                            
                        }
                    }
                    
                    html += `<div class="mb-3">`;
                    html += `<label class="form-label">${field.label} ${requiredLabel}</label>`;
                    
                    switch (field.type) {
                        case 'text':
                        case 'email':
                        case 'tel':
                            html += `<input type="${field.type}" class="form-control" name="${fieldName}" ${required}>`;
                            break;
                        case 'textarea':
                            html += `<textarea class="form-control" name="${fieldName}" rows="3" ${required}></textarea>`;
                            break;
                        case 'number':
                            html += `<input type="number" class="form-control" name="${fieldName}" ${required}>`;
                            break;
                        case 'date':
                            html += `<input type="date" class="form-control" name="${fieldName}" ${required}>`;
                            break;
                        case 'select':
                            html += `<select class="form-select" name="${fieldName}" ${required}>`;
                            html += `<option value="">Выберите...</option>`;
                            const selectOptions = getOptionsArray(field.options);
                            selectOptions.forEach(function(option) {
                                html += `<option value="${option}">${option}</option>`;
                            });
                            html += `</select>`;
                            break;
                        case 'radio':
                            const radioOptions = getOptionsArray(field.options);
                            radioOptions.forEach(function(option, optIndex) {
                                html += `
                                    <div class="form-check">
                                        <input type="radio" class="form-check-input" name="${fieldName}" value="${option}" id="${fieldName}_${optIndex}" ${required}>
                                        <label class="form-check-label" for="${fieldName}_${optIndex}">${option}</label>
                                    </div>`;
                            });
                            break;
                        case 'checkbox':
                            const checkboxOptions = getOptionsArray(field.options);
                            checkboxOptions.forEach(function(option, optIndex) {
                                html += `
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="${fieldName}[]" value="${option}" id="${fieldName}_${optIndex}">
                                        <label class="form-check-label" for="${fieldName}_${optIndex}">${option}</label>
                                    </div>`;
                            });
                            break;
                        case 'file':
                            html += `<input type="file" class="form-control" name="${fieldName}" ${required}>`;
                            break;
                        
                        // Специальные типы полей
                        case 'position':
                            html += `
                                <div class="positions-container">
                                    <div class="position-entry" data-index="0">
                                        <div class="row">
                                            <div class="col-md-10">
                                                <input type="text" class="form-control profession-autocomplete" 
                                                       name="${fieldName}[0][position]" 
                                                       data-autocomplete="professions"
                                                       placeholder="Gewünschte Position eingeben..." 
                                                       autocomplete="off" ${required}>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Priorität</label>
                                                <select class="form-select" name="${fieldName}[0][priority]">
                                                    <option value="1">1. Wahl</option>
                                                    <option value="2">2. Wahl</option>
                                                    <option value="3">3. Wahl</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2 add-position-btn" data-field-name="${fieldName}">
                                    <i class="bi bi-plus"></i> Weitere Position hinzufügen
                                </button>
                            `;
                            break;
                            
                        case 'bildung':
                            html += `
                                <div class="education-container">
                                    <div class="education-entry" data-index="0">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Bildungseinrichtung</label>
                                                <input type="text" class="form-control" name="${fieldName}[0][institution]" placeholder="Schule/Universität">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Abschluss/Grad</label>
                                                <input type="text" class="form-control" name="${fieldName}[0][degree]" placeholder="z.B. Bachelor, Master">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Von *</label>
                                                <input type="date" class="form-control" name="${fieldName}[0][start_date]" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Bis *</label>
                                                <input type="date" class="form-control" name="${fieldName}[0][end_date]" required>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="${fieldName}[0][is_current]" value="1">
                                                    <label class="form-check-label">Studiere/lerne noch</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2 add-education-btn" data-field-name="${fieldName}">
                                    <i class="bi bi-plus"></i> Weitere Bildung hinzufügen
                                </button>
                            `;
                            break;
                            
                        case 'berufserfahrung':
                            html += `
                                <div class="experience-container">
                                    <div class="experience-entry" data-index="0">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Position</label>
                                                <input type="text" class="form-control" name="${fieldName}[0][position]" placeholder="Berufsbezeichnung">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Unternehmen</label>
                                                <input type="text" class="form-control" name="${fieldName}[0][company]" placeholder="Firmenname">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Von *</label>
                                                <input type="date" class="form-control" name="${fieldName}[0][start_date]" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Bis *</label>
                                                <input type="date" class="form-control" name="${fieldName}[0][end_date]" required>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="${fieldName}[0][is_current]" value="1">
                                                    <label class="form-check-label">Arbeite noch dort</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2 add-experience-btn" data-field-name="${fieldName}">
                                    <i class="bi bi-plus"></i> Weitere Berufserfahrung hinzufügen
                                </button>
                            `;
                            break;
                            
                        case 'sprachkenntnisse':
                            html += `
                                <div class="languages-container">
                                    <div class="language-entry" data-index="0">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Sprache</label>
                                                <select class="form-select" name="${fieldName}[0][language]">
                                                    <option value="">Sprache wählen...</option>
                                                    <option value="Deutsch">Deutsch</option>
                                                    <option value="Englisch">Englisch</option>
                                                    <option value="Französisch">Französisch</option>
                                                    <option value="Spanisch">Spanisch</option>
                                                    <option value="Italienisch">Italienisch</option>
                                                    <option value="Russisch">Russisch</option>
                                                    <option value="Türkisch">Türkisch</option>
                                                    <option value="Polnisch">Polnisch</option>
                                                    <option value="Niederländisch">Niederländisch</option>
                                                    <option value="Portugiesisch">Portugiesisch</option>
                                                    <option value="Chinesisch">Chinesisch</option>
                                                    <option value="Japanisch">Japanisch</option>
                                                    <option value="Arabisch">Arabisch</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Niveau</label>
                                                <select class="form-select" name="${fieldName}[0][level]">
                                                    <option value="">Niveau wählen...</option>
                                                    <option value="A1">A1 - Anfänger</option>
                                                    <option value="A2">A2 - Grundkenntnisse</option>
                                                    <option value="B1">B1 - Mittlere Kenntnisse</option>
                                                    <option value="B2">B2 - Gute Kenntnisse</option>
                                                    <option value="C1">C1 - Sehr gute Kenntnisse</option>
                                                    <option value="C2">C2 - Muttersprachlich</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2 add-language-btn" data-field-name="${fieldName}">
                                    <i class="bi bi-plus"></i> Weitere Sprache hinzufügen
                                </button>
                            `;
                            break;
                            
                        case 'fuehrerschein':
                            const fuehrerscheinOptions = getOptionsArray(field.options);
                            fuehrerscheinOptions.forEach(function(option, optIndex) {
                                html += `
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="${fieldName}[]" value="${option}" id="${fieldName}_${optIndex}">
                                        <label class="form-check-label" for="${fieldName}_${optIndex}">${option}</label>
                                    </div>`;
                            });
                            break;
                            
                        case 'arbeitszeit':
                            const arbeitszeitOptions = getOptionsArray(field.options);
                            arbeitszeitOptions.forEach(function(option, optIndex) {
                                html += `
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="${fieldName}[]" value="${option}" id="${fieldName}_${optIndex}">
                                        <label class="form-check-label" for="${fieldName}_${optIndex}">${option}</label>
                                    </div>`;
                            });
                            break;
                            
                        case 'liste':
                            html += `
                                <textarea class="form-control" name="${fieldName}" rows="4" placeholder="Geben Sie hier Ihre Liste ein, ein Element pro Zeile..." ${required}></textarea>
                                <small class="form-text text-muted">Jede Zeile wird als separates Element behandelt</small>
                            `;
                            break;
                            
                        default:
                            html += `<input type="text" class="form-control" name="${fieldName}" ${required}>`;
                    }
                    
                    html += `</div>`;
                });
                
                $('#dynamic-fields').html(html);
                
                // Добавляем обработчики для специальных полей
                setTimeout(function() {
                    initSpecialFieldHandlers();
                }, 100);
            }
            
            // Инициализация обработчиков для специальных полей
            function initSpecialFieldHandlers() {
                // Обработчик для добавления образования
                $(document).off('click', '.add-education-btn').on('click', '.add-education-btn', function() {
                    const fieldName = $(this).data('field-name');
                    const container = $(this).prev('.education-container');
                    const index = container.find('.education-entry').length;
                    
                    const newEntry = `
                        <div class="education-entry mt-3" data-index="${index}">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Bildungseinrichtung</label>
                                    <input type="text" class="form-control" name="${fieldName}[${index}][institution]" placeholder="Schule/Universität">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Abschluss/Grad</label>
                                    <input type="text" class="form-control" name="${fieldName}[${index}][degree]" placeholder="z.B. Bachelor, Master">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Von *</label>
                                    <input type="date" class="form-control" name="${fieldName}[${index}][start_date]" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Bis *</label>
                                    <input type="date" class="form-control" name="${fieldName}[${index}][end_date]" required>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="${fieldName}[${index}][is_current]" value="1">
                                        <label class="form-check-label">Studiere/lerne noch</label>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.append(newEntry);
                });
                
                // Обработчик для добавления опыта работы
                $(document).off('click', '.add-experience-btn').on('click', '.add-experience-btn', function() {
                    const fieldName = $(this).data('field-name');
                    const container = $(this).prev('.experience-container');
                    const index = container.find('.experience-entry').length;
                    
                    const newEntry = `
                        <div class="experience-entry mt-3" data-index="${index}">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="${fieldName}[${index}][position]" placeholder="Berufsbezeichnung">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unternehmen</label>
                                    <input type="text" class="form-control" name="${fieldName}[${index}][company]" placeholder="Firmenname">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Von *</label>
                                    <input type="date" class="form-control" name="${fieldName}[${index}][start_date]" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Bis *</label>
                                    <input type="date" class="form-control" name="${fieldName}[${index}][end_date]" required>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-10">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="${fieldName}[${index}][is_current]" value="1">
                                        <label class="form-check-label">Arbeite noch dort</label>
                                    </div>
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.append(newEntry);
                });
                
                // Обработчик для добавления языков
                $(document).off('click', '.add-language-btn').on('click', '.add-language-btn', function() {
                    const fieldName = $(this).data('field-name');
                    const container = $(this).prev('.languages-container');
                    const index = container.find('.language-entry').length;
                    
                    const newEntry = `
                        <div class="language-entry mt-3" data-index="${index}">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Sprache</label>
                                    <select class="form-select" name="${fieldName}[${index}][language]">
                                        <option value="">Sprache wählen...</option>
                                        <option value="Deutsch">Deutsch</option>
                                        <option value="Englisch">Englisch</option>
                                        <option value="Französisch">Französisch</option>
                                        <option value="Spanisch">Spanisch</option>
                                        <option value="Italienisch">Italienisch</option>
                                        <option value="Russisch">Russisch</option>
                                        <option value="Türkisch">Türkisch</option>
                                        <option value="Polnisch">Polnisch</option>
                                        <option value="Niederländisch">Niederländisch</option>
                                        <option value="Portugiesisch">Portugiesisch</option>
                                        <option value="Chinesisch">Chinesisch</option>
                                        <option value="Japanisch">Japanisch</option>
                                        <option value="Arabisch">Arabisch</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Niveau</label>
                                    <select class="form-select" name="${fieldName}[${index}][level]">
                                        <option value="">Niveau wählen...</option>
                                        <option value="A1">A1 - Anfänger</option>
                                        <option value="A2">A2 - Grundkenntnisse</option>
                                        <option value="B1">B1 - Mittlere Kenntnisse</option>
                                        <option value="B2">B2 - Gute Kenntnisse</option>
                                        <option value="C1">C1 - Sehr gute Kenntnisse</option>
                                        <option value="C2">C2 - Muttersprachlich</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.append(newEntry);
                });
                
                // Обработчик для добавления позиций
                $(document).off('click', '.add-position-btn').on('click', '.add-position-btn', function() {
                    const fieldName = $(this).data('field-name');
                    const container = $(this).prev('.positions-container');
                    const index = container.find('.position-entry').length;
                    
                    const newEntry = `
                        <div class="position-entry mt-3" data-index="${index}">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control profession-autocomplete" 
                                           name="${fieldName}[${index}][position]" 
                                           data-autocomplete="professions"
                                           placeholder="Weitere gewünschte Position eingeben..." 
                                           autocomplete="off">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Priorität</label>
                                    <select class="form-select" name="${fieldName}[${index}][priority]">
                                        <option value="1">1. Wahl</option>
                                        <option value="2">2. Wahl</option>
                                        <option value="3">3. Wahl</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-entry-btn mt-4">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.append(newEntry);
                    
                    // Инициализируем автозаполнение для нового поля
                    const newInput = container.find(`input[name="${fieldName}[${index}][position]"]`);
                    if (window.NeoProfessionAutocomplete) {
                        window.NeoProfessionAutocomplete.initAutocomplete(newInput);
                    }
                });
                
                // Обработчик для удаления записей
                $(document).off('click', '.remove-entry-btn').on('click', '.remove-entry-btn', function() {
                    $(this).closest('.education-entry, .experience-entry, .language-entry, .position-entry').remove();
                });
                
                // Инициализация автозаполнения профессий
                if (window.NeoProfessionAutocomplete && typeof window.NeoProfessionAutocomplete.init === 'function') {
                    window.NeoProfessionAutocomplete.init();
                }
                
                // Принудительно инициализируем автозаполнение для всех полей профессий
                $('input[data-autocomplete="professions"]').each(function() {
                    if (window.NeoProfessionAutocomplete) {
                        window.NeoProfessionAutocomplete.initAutocomplete($(this));
                    }
                });
                
                // Обработчики для чекбоксов "Studiere/lerne noch" и "Arbeite noch dort"
                $(document).off('change', 'input[name$="[is_current]"]').on('change', 'input[name$="[is_current]"]', function() {
                    const isChecked = $(this).is(':checked');
                    const entry = $(this).closest('.education-entry, .experience-entry');
                    const endDateField = entry.find('input[name$="[end_date]"]');
                    const endDateCol = endDateField.closest('.col-md-2');
                    
                    if (isChecked) {
                        // Скрываем поле "Bis" и убираем обязательность
                        endDateCol.hide();
                        endDateField.prop('required', false);
                        endDateField.val(''); // Очищаем значение
                    } else {
                        // Показываем поле "Bis" и делаем его обязательным
                        endDateCol.show();
                        endDateField.prop('required', true);
                    }
                });
            }
            });
        }
        
        // Начинаем ждать jQuery
        waitForjQueryNew();
        </script>
        <?php
    }

    /**
     * Получение всех заявок
     */
    public static function get_applications() {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        $templates_table = $wpdb->prefix . 'neo_job_board_templates';
        
        $applications = $wpdb->get_results("
            SELECT a.*, t.name as template_name 
            FROM $applications_table a 
            LEFT JOIN $templates_table t ON a.template_id = t.id 
            ORDER BY a.created_at DESC
        ");
        
        return $applications;
    }

    /**
     * Получение деталей заявки
     */
    public static function get_application_details($application_id, $hash_id) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        $templates_table = $wpdb->prefix . 'neo_job_board_templates';
        
        // Получаем основную информацию о заявке
        $application = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $applications_table 
            WHERE id = %d AND hash_id = %s
        ", $application_id, $hash_id));
        
        if (!$application) {
            return false;
        }
        
        // Получаем шаблон
        $template = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $templates_table 
            WHERE id = %d
        ", $application->template_id));
        
        // Получаем дополнительные поля заявки из application_data
        $fields = [];
        if (!empty($application->application_data)) {
            $fields_data = json_decode($application->application_data, true);
            if (is_array($fields_data)) {
                // Денормализуем данные для отображения пользователю
                $denormalized_data = DataSanitizer::prepare_for_display($fields_data);
                foreach ($denormalized_data as $key => $value) {
                    $fields[] = [
                        'field_name' => $key,
                        'field_value' => $value
                    ];
                }
            }
        }
        
        return [
            'application' => $application,
            'template' => $template,
            'fields' => $fields
        ];
    }

    /**
     * Удаление заявки
     */
    public static function delete_application($application_id, $hash_id) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        // Проверяем существование заявки
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $applications_table 
            WHERE id = %d AND hash_id = %s
        ", $application_id, $hash_id));
        
        if (!$exists) {
            return false;
        }
        
        // Удаляем заявку (связанные данные удалятся автоматически через FOREIGN KEY CASCADE)
        $result = $wpdb->delete(
            $applications_table,
            [
                'id' => $application_id,
                'hash_id' => $hash_id
            ]
        );
        
        return $result !== false;
    }

    /**
     * Обновление ответственного сотрудника
     */
    public static function update_responsible_employee($application_id, $responsible_employee) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        // Проверяем существование заявки
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $applications_table 
            WHERE id = %d
        ", $application_id));
        
        if (!$exists) {
            return false;
        }
        
        // Обновляем ответственного сотрудника
        $result = $wpdb->update(
            $applications_table,
            [
                'responsible_employee' => sanitize_text_field($responsible_employee),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $application_id]
        );
        
        return $result !== false;
    }

    /**
     * Получение заявки по ID
     */
    public static function get_application_by_id($application_id) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        $templates_table = $wpdb->prefix . 'neo_job_board_templates';
        
        $application = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, t.name as template_name
            FROM $applications_table a
            LEFT JOIN $templates_table t ON a.template_id = t.id
            WHERE a.id = %d
        ", $application_id));
        
        return $application;
    }

    /**
     * Обновление заявки
     */
    public static function update_application($application_id, $data) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        // Проверяем существование заявки
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $applications_table 
            WHERE id = %d
        ", $application_id));
        
        if (!$exists) {
            return false;
        }
        
        // Подготавливаем данные для обновления
        $update_data = [
            'updated_at' => current_time('mysql')
        ];
        
        // Обновляем только разрешенные поля
        $allowed_fields = ['first_name', 'last_name', 'responsible_employee', 'status'];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }

        // Обновляем application_data если есть
        if (isset($data['application_data'])) {
            $update_data['application_data'] = json_encode($data['application_data']);
        }
        
        // Обновляем заявку
        $result = $wpdb->update(
            $applications_table,
            $update_data,
            ['id' => $application_id]
        );
        
        return $result !== false;
    }

    /**
     * Обновление статуса заявки
     */
    public static function update_application_status($application_id, $status) {
        global $wpdb;
        
        $applications_table = $wpdb->prefix . 'neo_job_board_applications';
        
        // Проверяем существование заявки
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $applications_table 
            WHERE id = %d
        ", $application_id));
        
        if (!$exists) {
            return false;
        }
        
        // Обновляем статус
        $result = $wpdb->update(
            $applications_table,
            [
                'status' => sanitize_text_field($status),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $application_id]
        );
        
        return $result !== false;
    }
}