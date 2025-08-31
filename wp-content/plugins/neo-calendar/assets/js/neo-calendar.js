/**
 * Neo Calendar - JavaScript
 * Основной функционал для календаря
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('FullCalendar:', window.FullCalendar);
        // --- Календарь ---
        if ($('#calendar').length && typeof FullCalendar !== 'undefined') {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: function(fetchInfo, successCallback) {
                    loadEventsFromDB(fetchInfo.start, fetchInfo.end, successCallback);
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                moreLinkText: function(num) {
                    return '+ ' + num + ' weitere';
                },
                height: 1000,
                dayMaxEvents: true,
                editable: true,
                displayEventTime: false,
                selectable: true,
                eventClick: function(info) {
                    if (info.event.extendedProps.is_owner) {
                        if (confirm('Ereignis "' + info.event.title + '" löschen?')) {
                            deleteEventFromDB(info.event.id);
                        }
                    }
                }
            });
            calendar.render();
            window.neoCalendar = calendar;
        }

        // Добавляем обработчики событий для кнопок
        initializeFormButtons();
    });

    // Функция инициализации кнопок форм
    function initializeFormButtons() {
        // Кнопка показа формы отпуска
        const showVacationBtn = document.getElementById('show-vacation-form-btn');
        if (showVacationBtn) {
            showVacationBtn.addEventListener('click', function() {
                window.NeoCalendar.showVacationForm();
            });
        }

        // Кнопка возврата к форме рабочего времени
        const backToWorkBtn = document.getElementById('back-to-work-form-btn');
        if (backToWorkBtn) {
            backToWorkBtn.addEventListener('click', function() {
                window.NeoCalendar.showWorkForm();
            });
        }

        // Кнопка добавления рабочего времени
        const addWorkTimeBtn = document.getElementById('add-work-time-btn');
        if (addWorkTimeBtn) {
            addWorkTimeBtn.addEventListener('click', function() {
                window.NeoCalendar.addWorkTime(
                    document.getElementById('work-date'),
                    document.getElementById('work-time-from'),
                    document.getElementById('work-time-to')
                );
            });
        }

        // Кнопка добавления отпуска
        const addVacationBtn = document.getElementById('add-vacation-btn');
        if (addVacationBtn) {
            addVacationBtn.addEventListener('click', function() {
                window.NeoCalendar.addVacation(
                    document.getElementById('vacation-date-from'),
                    document.getElementById('vacation-date-to')
                );
            });
        }
    }

    function loadEventsFromDB(start, end, successCallback) {
        const formData = new FormData();
        formData.append('action','neo_calendar_get_events');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('start', start.toISOString());
        formData.append('end', end.toISOString());
        fetch(neoCalendarAjax.ajaxurl, {method:'POST', body:formData})
            .then(r=>r.json())
            .then(data => successCallback(data.success ? data.data : []))
            .catch(()=>successCallback([]));
    }

    function deleteEventFromDB(eventId) {
        // Дополнительная проверка безопасности на клиенте
        const event = window.neoCalendar.getEventById(eventId);
        if (!event || !event.extendedProps.is_owner) {
            alert('Sie können nur Ihre eigenen Ereignisse löschen.');
            return;
        }

        const formData = new FormData();
        formData.append('action','neo_calendar_delete_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('event_id', eventId);
        fetch(neoCalendarAjax.ajaxurl, {method:'POST', body:formData})
            .then(r=>r.json())
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
    
        if (from && to) {
            window.NeoCalendar.initTimePicker("work-time-from");
            window.NeoCalendar.initTimePicker("work-time-to");
        }
    });

    // Добавляем методы в глобальный объект NeoCalendar
    window.NeoCalendar = {
        ...window.NeoCalendar,
    };

})(jQuery);
