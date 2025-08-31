/**
 * Neo Calendar - JavaScript
 * Hauptfunktionalität für Kalender
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        console.log('FullCalendar:', window.FullCalendar);
        // --- Kalender ---
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

                    // Öffne sofort das Bearbeitungsformular
                    if (props.can_manage || props.is_owner) {
                        editEvent(info.event);
                    } else {
                        alert('Sie haben keine Berechtigung, dieses Ereignis zu bearbeiten.');
                    }
                },
                dateClick: function (info) {
                    // Setze ausgewähltes Datum im work-date Feld
                    const workDateInput = document.getElementById('work-date');
                    if (workDateInput) {
                        workDateInput.value = info.dateStr;
                    }
                }
            });
            calendar.render();
            window.neoCalendar = calendar;
        }

        // Füge Event-Handler für Lösch-Button im Bearbeitungsformular hinzu
        const deleteEventBtn = document.getElementById('delete-event-btn');
        if (deleteEventBtn) {
            deleteEventBtn.addEventListener('click', function () {
                const eventId = document.getElementById('edit-event-id').value;
                if (eventId && confirm('Ereignis wirklich löschen?')) {
                    deleteEventFromDB(eventId);
                    // Schließe Modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                    modal.hide();
                }
            });
        }
    });

    // Funktion zur Initialisierung der Formular-Buttons
    function initializeFormButtons() {
        // Button zum Anzeigen des Urlaubsformulars
        const showVacationBtn = document.getElementById('show-vacation-form-btn');
        if (showVacationBtn) {
            showVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.showVacationForm();
            });
        }

        // Button zum Zurückkehren zum Arbeitszeitformular
        const backToWorkBtn = document.getElementById('back-to-work-form-btn');
        if (backToWorkBtn) {
            backToWorkBtn.addEventListener('click', function () {
                window.NeoCalendar.showWorkForm();
            });
        }

        // Button zum Anzeigen des Veranstaltungsformulars
        const showEventBtn = document.getElementById('show-event-form-btn');
        if (showEventBtn) {
            showEventBtn.addEventListener('click', function () {
                window.NeoCalendar.showEventForm();
            });
        }

        // Button zum Zurückkehren zum Arbeitszeitformular aus dem Veranstaltungsformular
        const backToWorkFromEventBtn = document.getElementById('back-to-work-from-event-btn');
        if (backToWorkFromEventBtn) {
            backToWorkFromEventBtn.addEventListener('click', function () {
                window.NeoCalendar.showWorkForm();
            });
        }

        // Button zum Hinzufügen von Arbeitszeit
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

        // Button zum Hinzufügen von Urlaub
        const addVacationBtn = document.getElementById('add-vacation-btn');
        if (addVacationBtn) {
            addVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.addVacation(
                    document.getElementById('vacation-date-from'),
                    document.getElementById('vacation-date-to')
                );
            });
        }

        // Button zum Hinzufügen von Veranstaltungen
        const addEventBtn = document.getElementById('add-event-btn');
        if (addEventBtn) {
            addEventBtn.addEventListener('click', function () {
                window.NeoCalendar.addEvent(
                    document.getElementById('event-date'),
                    document.getElementById('event-time'),
                    document.getElementById('event-title')
                );
            });
        }

        // Button zum Speichern von Änderungen im Bearbeitungsformular
        const saveEventChangesBtn = document.getElementById('save-event-changes-btn');
        if (saveEventChangesBtn) {
            saveEventChangesBtn.addEventListener('click', saveEventChanges);
        }
    }

    function loadEventsFromDB(start, end, successCallback) {
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
        // Fülle das Formular mit Ereignisdaten
        document.getElementById('edit-event-id').value = event.id;
        document.getElementById('edit-event-type').value = event.extendedProps.type || 'arbeitsstunde';
        document.getElementById('edit-event-title').value = event.title || '';
        document.getElementById('edit-event-description').value = event.extendedProps.description || '';

        // Konvertiere Daten in separate Felder
        const startDate = new Date(event.start);
        const endDate = event.end ? new Date(event.end) : null;

        // Fülle Datumsfelder
        document.getElementById('edit-event-start-date').value = startDate.toISOString().split('T')[0];
        if (endDate) {
            document.getElementById('edit-event-end-date').value = endDate.toISOString().split('T')[0];
        }

        // Fülle Zeitfelder
        document.getElementById('edit-event-start-time').value = startDate.toTimeString().slice(0, 5);
        if (endDate) {
            document.getElementById('edit-event-end-time').value = endDate.toTimeString().slice(0, 5);
        }



        // Zeige das Modal-Fenster
        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
        modal.show();

        // Initialisiere Time-Pickers für Zeitfelder
        if (window.NeoCalendar && window.NeoCalendar.initTimePicker) {
            window.NeoCalendar.initTimePicker('edit-event-start-time');
            window.NeoCalendar.initTimePicker('edit-event-end-time');
        }

        // Füge Event-Handler für Typänderung hinzu
        const typeSelect = document.getElementById('edit-event-type');
        typeSelect.addEventListener('change', handleEventTypeChange);

        // Initialisiere Felder basierend auf dem aktuellen Typ
        handleEventTypeChange();

        // Lade Benutzerliste, falls der Benutzer Berechtigungen hat
        loadUsersIfNeeded().then(() => {
            // Setze ausgewählten Mitarbeiter nach dem Laden der Liste
            const employeeSelect = document.getElementById('edit-event-employee');
            if (employeeSelect) {
                employeeSelect.value = event.extendedProps.user_id || '';
            }
        });

        // Zeige Lösch-Button für bestehendes Ereignis
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
            // Für Urlaub: nur Datum, keine Zeit
            startTimeInput.style.display = 'none';
            endTimeInput.style.display = 'none';
            titleContainer.style.display = 'none';
            if (employeeContainer) employeeContainer.style.display = 'none';
        } else if (type === 'veranstaltung') {
            // Für Veranstaltungen: Datum und Zeit + Pflichtfeld Titel
            startTimeInput.style.display = 'block';
            endTimeInput.style.display = 'block';
            titleContainer.style.display = 'block';
            titleInput.required = true;
            if (employeeContainer) employeeContainer.style.display = 'none';
        } else {
            // Für Arbeitszeit: Datum und Zeit, Titel versteckt
            startTimeInput.style.display = 'block';
            endTimeInput.style.display = 'block';
            titleContainer.style.display = 'none';
            titleInput.required = false;
            if (employeeContainer) employeeContainer.style.display = 'block';
        }
    }

    // Funktion zum Laden der Benutzerliste
    function loadUsersIfNeeded() {
        const employeeContainer = document.getElementById('edit-event-employee-container');
        const employeeSelect = document.getElementById('edit-event-employee');

        if (!employeeContainer || !employeeSelect) return Promise.resolve();

        // Prüfe, ob der Benutzer Berechtigungen zum Verwalten des Kalenders hat
        // Das kann man anhand der Anwesenheit bestimmter Elemente oder über AJAX feststellen
        const formData = new FormData();
        formData.append('action', 'neo_calendar_get_users');
        formData.append('nonce', neoCalendarAjax.nonce);

        return fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Leere vorhandene Optionen
                    employeeSelect.innerHTML = '';

                    // Füge Optionen für Benutzer hinzu
                    data.data.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        // Formuliere angezeigten Namen: Vorname + Nachname oder display_name als Fallback
                        let displayName = '';
                        if (user.first_name && user.last_name) {
                            displayName = user.first_name + ' ' + user.last_name;
                        } else if (user.first_name) {
                            displayName = user.first_name;
                        } else {
                            displayName = user.name; // Fallback auf display_name
                        }
                        option.textContent = displayName;
                        employeeSelect.appendChild(option);
                    });

                    // Zeige Container an, falls Benutzer vorhanden sind
                    if (data.data.length > 0) {
                        employeeContainer.style.display = 'block';

                        // Prüfe, ob der aktuelle Benutzer mitarbeiter ist
                        const currentUserId = neoCalendarAjax.current_user_id;
                        const currentUser = data.data.find(user => user.id == currentUserId);
                        const isMitarbeiter = currentUser && currentUser.role === 'neo_mitarbeiter';

                        if (isMitarbeiter) {
                            // Für mitarbeiter machen Sie das Feld nur zum Lesen
                            employeeSelect.disabled = true;
                            employeeSelect.style.backgroundColor = '#f8f9fa';

                            // Deaktiviere auch das Typ-Ereignisfeld
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
            alert('Bitte geben Sie ein Startdatum ein.');
            return;
        }

        // Formuliere vollständige Daten und Zeiten
        const start = startDate + (startTime ? 'T' + startTime + ':00' : 'T00:00:00');
        const end = endDate && endTime ? endDate + 'T' + endTime + ':00' : (endDate ? endDate + 'T23:59:59' : null);

        // Für Arbeitszeit speichern wir keinen Titel
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

        // Füge Mitarbeiter-ID hinzu, falls ausgewählt
        if (employeeSelect && employeeSelect.value) {
            formData.append('employee_id', employeeSelect.value);
        }

        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(isNewEvent ? 'Ereignis erfolgreich erstellt!' : 'Ereignis erfolgreich aktualisiert!');
                    // Schließe das Modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                    modal.hide();
                    // Aktualisiere Kalender
                    if (window.neoCalendar) {
                        window.neoCalendar.refetchEvents();
                    }
                } else {
                    alert((isNewEvent ? 'Fehler beim Erstellen' : 'Fehler beim Aktualisieren') + ' des Ereignisses: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Aktualisieren des Ereignisses');
            });
    }

    function deleteEventFromDB(eventId) {
        const event = window.neoCalendar.getEventById(eventId);
        if (!event || (!event.extendedProps.is_owner && !event.extendedProps.can_manage)) {
            alert('Sie können nur Ihre eigenen Ereignisse löschen.');
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
                    window.neoCalendar.refetchEvents();
                } else {
                    alert('Fehler beim Löschen des Ereignisses: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Fehler beim Löschen des Ereignisses');
            });
    }

    document.addEventListener("DOMContentLoaded", () => {
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

        // Füge Event-Handler für Buttons
        initializeFormButtons();
    });

    window.NeoCalendar = {
        ...window.NeoCalendar,
    };

})(jQuery);
