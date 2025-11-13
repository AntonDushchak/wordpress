(function ($) {
    'use strict';

    $(document).ready(function () {
        setTimeout(function() {
            if (typeof neoCalendarAjax === 'undefined') {
                window.neoCalendarAjax = {
                    ajaxurl: window.location.origin + '/wp-admin/admin-ajax.php',
                    nonce: 'fallback_nonce',
                    current_user_id: 0
                };
                
                const metaNonce = document.querySelector('meta[name="neo-calendar-nonce"]');
                if (metaNonce) {
                    window.neoCalendarAjax.nonce = metaNonce.getAttribute('content');
                }
            }
            
            initializeCalendar();
        }, 100);
    });
    
    function initializeCalendar() {
        if ($('#calendar').length && typeof FullCalendar !== 'undefined') {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'de',
                events: function (fetchInfo, successCallback) {
                    loadEventsFromDB(fetchInfo.start, fetchInfo.end, successCallback);
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridDay'
                },
                moreLinkText: function (num) {
                    return '+ ' + num + ' weitere';
                },
                height: 1000,
                dayMaxEvents: true,
                displayEventTime: false,
                selectable: true,
                editable: false,
                droppable: false,
                eventClick: function (info) {
                    const props = info.event.extendedProps;

                    if (props.can_manage || props.is_owner) {
                        editEvent(info.event);
                    } else {
                        if (window.NeoDash && window.NeoDash.toastError) {
                            NeoDash.toastError('Sie haben keine Berechtigung, dieses Ereignis zu bearbeiten.');
                        } else {
                            alert('Sie haben keine Berechtigung, dieses Ereignis zu bearbeiten.');
                        }
                    }
                },
                dateClick: function (info) {
                    const clickedDate = new Date(info.dateStr);
                    const formattedDate = clickedDate.getDate().toString().padStart(2, '0') + '-' + 
                                        (clickedDate.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                                        clickedDate.getFullYear();

                    const workForm = document.getElementById('work-time-form');
                    const vacationForm = document.getElementById('vacation-form');
                    const eventForm = document.getElementById('event-form');

                    if (workForm && workForm.style.display !== 'none') {
                        const workDateInput = document.getElementById('work-date');
                        if (workDateInput && window.NeoCalendar && window.NeoCalendar.initDatePicker) {
                            if (workDateInput._flatpickr) {
                                workDateInput._flatpickr.setDate(formattedDate);
                            } else {
                                workDateInput.value = formattedDate;
                            }
                        }
                    } else if (vacationForm && vacationForm.style.display !== 'none') {
                        const vacationDateInput = document.getElementById('vacation-date-range');
                        if (vacationDateInput && window.NeoCalendar && window.NeoCalendar.initDateRangePicker) {
                            if (vacationDateInput._flatpickr) {
                                vacationDateInput._flatpickr.setDate(formattedDate);
                            } else {
                                vacationDateInput.value = formattedDate;
                            }
                        }
                    } else if (eventForm && eventForm.style.display !== 'none') {
                        const eventDateInput = document.getElementById('event-date-range');
                        if (eventDateInput && window.NeoCalendar && window.NeoCalendar.initDateRangePicker) {
                            if (eventDateInput._flatpickr) {
                                eventDateInput._flatpickr.setDate(formattedDate);
                            } else {
                                eventDateInput.value = formattedDate;
                            }
                        }
                    }
                }
            });
            calendar.render();
            window.neoCalendar = calendar;
        }
        
        const deleteEventBtn = document.getElementById('delete-event-btn');
        if (deleteEventBtn) {
            deleteEventBtn.addEventListener('click', function () {
                const eventId = document.getElementById('edit-event-id').value;
                if (eventId) {
                    if (window.NeoDash && window.NeoDash.confirm) {
                        NeoDash.confirm('Ereignis wirklich löschen?', {
                            type: 'danger',
                            title: 'Bestätigung des Löschens',
                            confirmText: 'Löschen',
                            cancelText: 'Abbrechen'
                        }).then((confirmed) => {
                            if (confirmed) {
                                deleteEventFromDB(eventId);
                                const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                                modal.hide();
                            }
                        });
                    } else {
                        if (confirm('Ereignis wirklich löschen?')) {
                            deleteEventFromDB(eventId);
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                            modal.hide();
                        }
                    }
                }
            });
        }
    }

    function initializeFormButtons() {
        const showVacationBtn = document.getElementById('show-vacation-form-btn');
        if (showVacationBtn) {
            showVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.showVacationForm();
            });
        }

        const backToWorkBtn = document.getElementById('back-to-work-form-btn');
        if (backToWorkBtn) {
            backToWorkBtn.addEventListener('click', function () {
                window.NeoCalendar.showWorkForm();
            });
        }

        const showEventBtn = document.getElementById('show-event-form-btn');
        if (showEventBtn) {
            showEventBtn.addEventListener('click', function () {
                window.NeoCalendar.showEventForm();
            });
        }

        const backToWorkFromEventBtn = document.getElementById('back-to-work-from-event-btn');
        if (backToWorkFromEventBtn) {
            backToWorkFromEventBtn.addEventListener('click', function () {
                window.NeoCalendar.showWorkForm();
            });
        }

        const addWorkTimeBtn = document.getElementById('add-work-time-btn');
        if (addWorkTimeBtn) {
            addWorkTimeBtn.addEventListener('click', function () {
                window.NeoCalendar.addWorkTime(
                    document.getElementById('work-date'),
                    document.getElementById('work-time-from'),
                    document.getElementById('work-time-to')
                );
            });
        }

        const addVacationBtn = document.getElementById('add-vacation-btn');
        if (addVacationBtn) {
            addVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.addVacation(
                    document.getElementById('vacation-date-range')
                );
            });
        }

        const addEventBtn = document.getElementById('add-event-btn');
        if (addEventBtn) {
            addEventBtn.addEventListener('click', function () {
                window.NeoCalendar.addEvent(
                    document.getElementById('event-date-range'),
                    document.getElementById('event-time'),
                    document.getElementById('event-title')
                );
            });
        }

        const saveEventChangesBtn = document.getElementById('save-event-changes-btn');
        if (saveEventChangesBtn) {
            saveEventChangesBtn.addEventListener('click', saveEventChanges);
        }
    }

    function loadEventsFromDB(start, end, successCallback) {
        if (typeof neoCalendarAjax === 'undefined') {
            console.error('neoCalendarAjax is not defined');
            successCallback([]);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'neo_calendar_get_events');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('start', start.toISOString());
        formData.append('end', end.toISOString());
        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => successCallback(data.success ? data.data : []))
            .catch(() => successCallback([]));
    }

    function editEvent(event) {
        document.getElementById('edit-event-id').value = event.id;
        document.getElementById('edit-event-type').value = event.extendedProps.type || 'arbeitsstunde';
        document.getElementById('edit-event-title').value = event.title || '';
        document.getElementById('edit-event-description').value = event.extendedProps.description || '';

        const startDate = new Date(event.start);
        const endDate = event.end ? new Date(event.end) : null;

        document.getElementById('edit-event-start-date').value = startDate.toISOString().split('T')[0];
        if (endDate) {
            document.getElementById('edit-event-end-date').value = endDate.toISOString().split('T')[0];
        }

        document.getElementById('edit-event-start-time').value = startDate.toTimeString().slice(0, 5);
        if (endDate) {
            document.getElementById('edit-event-end-time').value = endDate.toTimeString().slice(0, 5);
        }



        let modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
        if (!modal) {
            modal = new bootstrap.Modal(document.getElementById('editEventModal'), {
                backdrop: 'static',
                keyboard: false
            });
        }
        modal.show();

        setTimeout(() => {
            if (window.NeoCalendar && window.NeoCalendar.initTimePicker) {
                window.NeoCalendar.initTimePicker('edit-event-start-time');
                window.NeoCalendar.initTimePicker('edit-event-end-time');
            }
            if (window.NeoCalendar && window.NeoCalendar.initDatePicker) {
                window.NeoCalendar.initDatePicker('edit-event-start-date');
                window.NeoCalendar.initDatePicker('edit-event-end-date');
            }
        }, 100);

        const typeSelect = document.getElementById('edit-event-type');
        typeSelect.addEventListener('change', handleEventTypeChange);

        handleEventTypeChange();

        loadUsersIfNeeded().then(() => {
            const employeeSelect = document.getElementById('edit-event-employee');
            if (employeeSelect) {
                employeeSelect.value = event.extendedProps.user_id || '';
            }
        });

        const deleteBtn = document.getElementById('delete-event-btn');
        if (deleteBtn) {
            deleteBtn.style.display = 'block';
        }
    }

    function handleEventTypeChange() {
        const type = document.getElementById('edit-event-type').value;
        const startDateInput = document.getElementById('edit-event-start-date');
        const startTimeInput = document.getElementById('edit-event-start-time');
        const endDateInput = document.getElementById('edit-event-end-date');
        const endTimeInput = document.getElementById('edit-event-end-time');
        const titleContainer = document.getElementById('edit-event-title-container');
        const titleInput = document.getElementById('edit-event-title');
        const employeeContainer = document.getElementById('edit-event-employee-container');

        if (type === 'urlaub') {
            startTimeInput.style.display = 'none';
            endTimeInput.style.display = 'none';
            titleContainer.style.display = 'none';
            if (employeeContainer) employeeContainer.style.display = 'none';
        } else if (type === 'veranstaltung') {
            startTimeInput.style.display = 'block';
            endTimeInput.style.display = 'block';
            titleContainer.style.display = 'block';
            titleInput.required = true;
            if (employeeContainer) employeeContainer.style.display = 'none';
        } else {
            startTimeInput.style.display = 'block';
            endTimeInput.style.display = 'block';
            titleContainer.style.display = 'none';
            titleInput.required = false;
            if (employeeContainer) employeeContainer.style.display = 'block';
        }
    }

    function loadUsersIfNeeded() {
        const employeeContainer = document.getElementById('edit-event-employee-container');
        const employeeSelect = document.getElementById('edit-event-employee');

        if (!employeeContainer || !employeeSelect) return Promise.resolve();

        const formData = new FormData();
        formData.append('action', 'neo_calendar_get_users');
        formData.append('nonce', neoCalendarAjax.nonce);

        return fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    employeeSelect.innerHTML = '';

                    data.data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        let displayName = '';
                        if (user.first_name && user.last_name) {
                            displayName = user.first_name + ' ' + user.last_name;
                        } else if (user.first_name) {
                            displayName = user.first_name;
                        } else {
                            displayName = user.name;
                        }
                        option.textContent = displayName;
                        employeeSelect.appendChild(option);
                    });

                    if (data.data.length > 0) {
                        employeeContainer.style.display = 'block';

                        const currentUserId = neoCalendarAjax.current_user_id;
                        const currentUser = data.data.find(user => user.id == currentUserId);
                        const isMitarbeiter = currentUser && currentUser.role === 'neo_mitarbeiter';

                        if (isMitarbeiter) {
                            employeeSelect.disabled = true;
                            employeeSelect.style.backgroundColor = '#f8f9fa';

                            const eventTypeSelect = document.getElementById('edit-event-type');
                            if (eventTypeSelect) {
                                eventTypeSelect.disabled = true;
                                eventTypeSelect.style.backgroundColor = '#f8f9fa';
                            }
                        }
                    }
                }
                return data;
            })
            .catch(error => {
                console.error('Error loading users:', error);
                return { success: false };
            });
    }

    function saveEventChanges() {
        const eventId = document.getElementById('edit-event-id').value;
        const isNewEvent = !eventId || eventId === '';
        const type = document.getElementById('edit-event-type').value;
        const title = document.getElementById('edit-event-title').value;
        const startDate = document.getElementById('edit-event-start-date').value;
        const startTime = document.getElementById('edit-event-start-time').value;
        const endDate = document.getElementById('edit-event-end-date').value;
        const endTime = document.getElementById('edit-event-end-time').value;
        const employeeSelect = document.getElementById('edit-event-employee');
        const description = document.getElementById('edit-event-description').value;

        if (!startDate) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Bitte geben Sie ein Startdatum ein.');
            } else {
                alert('Bitte geben Sie ein Startdatum ein.');
            }
            return;
        }

        const start = startDate + (startTime ? 'T' + startTime + ':00' : 'T00:00:00');
        const end = endDate && endTime ? endDate + 'T' + endTime + ':00' : (endDate ? endDate + 'T23:59:59' : null);

        const finalTitle = type === 'arbeitsstunde' ? '' : title;

        const formData = new FormData();
        formData.append('action', isNewEvent ? 'neo_calendar_save_event' : 'neo_calendar_update_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        if (!isNewEvent) {
            formData.append('event_id', eventId);
        }
        formData.append('type', type);
        formData.append('title', finalTitle);
        formData.append('start', start);
        formData.append('end', end);
        formData.append('description', description);

        if (employeeSelect && employeeSelect.value) {
            formData.append('employee_id', employeeSelect.value);
        }

        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess(isNewEvent ? 'Ereignis erfolgreich erstellt!' : 'Ereignis erfolgreich aktualisiert!');
                    } else {
                        alert(isNewEvent ? 'Ereignis erfolgreich erstellt!' : 'Ereignis erfolgreich aktualisiert!');
                    }
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                    modal.hide();
                    if (window.neoCalendar) {
                        window.neoCalendar.refetchEvents();
                    }
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError((isNewEvent ? 'Fehler beim Erstellen' : 'Fehler beim Aktualisieren') + ' des Ereignisses: ' + data.data);
                    } else {
                        alert((isNewEvent ? 'Fehler beim Erstellen' : 'Fehler beim Aktualisieren') + ' des Ereignisses: ' + data.data);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Fehler beim Aktualisieren des Ereignisses');
                } else {
                    alert('Fehler beim Aktualisieren des Ereignisses');
                }
            });
    }

    function deleteEventFromDB(eventId) {
        const event = window.neoCalendar.getEventById(eventId);
        if (!event || (!event.extendedProps.is_owner && !event.extendedProps.can_manage)) {
            if (window.NeoDash && window.NeoDash.toastError) {
                NeoDash.toastError('Sie können nur Ihre eigenen Ereignisse löschen.');
            } else {
                alert('Sie können nur Ihre eigenen Ereignisse löschen.');
            }
            return;
        }

        const formData = new FormData();
        formData.append('action', 'neo_calendar_delete_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('event_id', eventId);
        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess('Ereignis erfolgreich gelöscht');
                    }
                    window.neoCalendar.refetchEvents();
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError('Fehler beim Löschen des Ereignisses: ' + data.data);
                    } else {
                        alert('Fehler beim Löschen des Ereignisses: ' + data.data);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Fehler beim Löschen des Ereignisses');
                } else {
                    alert('Fehler beim Löschen des Ereignisses');
                }
            });
    }

    function initCalendarPickers() {
        const from = document.getElementById("work-time-from");
        const to = document.getElementById("work-time-to");
        const eventTime = document.getElementById("event-time");

        if (from && to) {
            window.NeoCalendar.initTimePicker("work-time-from");
            window.NeoCalendar.initTimePicker("work-time-to");
        }

        if (eventTime) {
            window.NeoCalendar.initTimePicker("event-time");
        }

        if (window.NeoCalendar && window.NeoCalendar.initDatePicker) {
            window.NeoCalendar.initDatePicker("work-date");
            window.NeoCalendar.initDatePicker("edit-event-start-date");
            window.NeoCalendar.initDatePicker("edit-event-end-date");
        }

        if (window.NeoCalendar && window.NeoCalendar.initDateRangePicker) {
            window.NeoCalendar.initDateRangePicker("vacation-date-range");
            window.NeoCalendar.initDateRangePicker("event-date-range");
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initCalendarPickers();
        initializeFormButtons();
    });

    const editEventModal = document.getElementById('editEventModal');
    if (editEventModal) {
        editEventModal.addEventListener('shown.bs.modal', () => {
            setTimeout(() => {
                initCalendarPickers();
            }, 100);
        });
    }

    window.NeoCalendar = {
        ...window.NeoCalendar,
    };

})(jQuery);