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
                editable: true,
                selectable: true,
                eventClick: function(info) {
                    if (confirm('Ereignis "' + info.event.title + '" löschen?')) {
                        deleteEventFromDB(info.event.id);
                    }
                }
            });
            calendar.render();
            window.neoCalendar = calendar;
        }

        // --- Виджет (работает всегда) ---
        initWidgetHandlers();
    });

    function initWidgetHandlers() {
        // Кнопка добавления рабочего времени в виджете
        $(document).on('click', '#widget-add-work-time-btn', function(e) {
            e.preventDefault();
            const date = $('#widget-work-date').val();
            const timeFrom = $('#widget-work-time-from').val();
            const timeTo = $('#widget-work-time-to').val();
            if (!date || !timeFrom || !timeTo) { alert('Bitte füllen Sie alle Felder aus!'); return; }
            if (timeFrom >= timeTo) { alert('Die Zeit "von" muss kleiner als die Zeit "bis" sein!'); return; }
            saveEventToDB('arbeitsstunde', '', date+'T'+timeFrom+':00', date+'T'+timeTo+':00', '');
            $('#widget-work-date').val(new Date().toISOString().split('T')[0]);
            $('#widget-work-time-from').val('09:00');
            $('#widget-work-time-to').val('18:00');
        });

        // Кнопка показа формы отпуска в виджете
        $(document).on('click', '#widget-show-vacation-form-btn', function(e) {
            e.preventDefault();
            alert('Für die Eingabe von Urlaub gehen Sie bitte in den Kalender-Bereich.');
        });
    }

    function saveEventToDB(type, title, start, end, meta) {
        if (typeof neoCalendarAjax === 'undefined') { alert('AJAX configuration error'); return; }
        const formData = new FormData();
        formData.append('action', 'neo_calendar_save_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('type', type);
        formData.append('title', title);
        formData.append('start', start);
        formData.append('end', end);
        formData.append('meta', meta);

        fetch(neoCalendarAjax.ajaxurl, { method:'POST', body: formData })
        .then(r=>r.json())
        .then(data=>{
            if (data.success) {
                if (window.neoCalendar) {
                    window.neoCalendar.refetchEvents();
                }
                alert('Ereignis hinzugefügt!');
            } else {
                alert('Fehler beim Speichern: '+data.data);
            }
        })
        .catch(err => alert('Fehler beim Speichern: '+err.message));
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
        const formData = new FormData();
        formData.append('action','neo_calendar_delete_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('event_id', eventId);
        fetch(neoCalendarAjax.ajaxurl, {method:'POST', body:formData})
            .then(r=>r.json())
            .then(data=>{
                if(data.success && window.neoCalendar){
                    window.neoCalendar.getEventById(eventId)?.remove();
                }
            });
    }
    /**
     * Функция добавления рабочего времени
     */
    function addWorkTime() {
        const date = document.getElementById('work-date').value;
        const timeFrom = document.getElementById('work-time-from').value;
        const timeTo = document.getElementById('work-time-to').value;

        if (!date || !timeFrom || !timeTo) {
            alert('Bitte füllen Sie alle Felder aus!');
            return;
        }

        if (timeFrom >= timeTo) {
            alert('Die Zeit "von" muss kleiner als die Zeit "bis" sein!');
            return;
        }

        const startDateTime = date + 'T' + timeFrom + ':00';
        const endDateTime = date + 'T' + timeTo + ':00';

        // Сохраняем в базу данных
        saveEventToDB('arbeitsstunde', '', startDateTime, endDateTime, '');
    }

    /**
     * Функция добавления отпуска
     */
    function addVacation() {
        const dateFrom = document.getElementById('vacation-date-from').value;
        const dateTo = document.getElementById('vacation-date-to').value;

        if (!dateFrom || !dateTo) {
            alert('Bitte füllen Sie alle Felder aus!');
            return;
        }

        if (dateFrom > dateTo) {
            alert('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
            return;
        }

        // Для отпуска end дата должна быть +1 день, так как FullCalendar не включает последний день
        const endDate = new Date(dateTo);
        endDate.setDate(endDate.getDate() + 1);
        const endDateStr = endDate.toISOString().split('T')[0];

        // Сохраняем в базу данных
        saveEventToDB('urlaub', '', dateFrom, endDateStr, '');
    }

    /**
     * Функция сохранения события в базу данных
     */
    function saveEventToDB(type, title, start, end, meta) {
        if (typeof neoCalendarAjax === 'undefined') {
            alert('AJAX configuration error. Please refresh the page.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'neo_calendar_save_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('type', type);
        formData.append('title', title);
        formData.append('start', start);
        formData.append('end', end);
        formData.append('meta', meta);

        fetch(neoCalendarAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Обновляем календарь
                window.neoCalendar.refetchEvents();

                // Обновляем сообщение о пустом календаре
                setTimeout(() => {
                    updateNoEventsMessage(window.neoCalendar);
                }, 500);

                // Очищаем форму
                if (type === 'arbeitsstunde') {
                    document.getElementById('work-date').value = new Date().toISOString().split('T')[0];
                    document.getElementById('work-time-from').value = '09:00';
                    document.getElementById('work-time-to').value = '18:00';
                } else if (type === 'urlaub') {
                    document.getElementById('vacation-date-from').value = new Date().toISOString().split('T')[0];
                    document.getElementById('vacation-date-to').value = new Date().toISOString().split('T')[0];
                    // Возвращаемся к форме рабочего времени
                    document.getElementById('vacation-form').style.display = 'none';
                    document.getElementById('work-time-form').style.display = 'block';
                }

                alert('Ereignis hinzugefügt!');
            } else {
                alert('Fehler beim Speichern: ' + data.data);
            }
        })
        .catch(error => {
            alert('Fehler beim Speichern des Ereignisses: ' + error.message);
        });
    }



    // Экспортируем функции для глобального использования
    window.NeoCalendar = {
        refreshWidget,
        updateWidgetData,
        initCalendar,
        initWidgetHandlers,
        addWorkTime,
        addVacation,
        saveEventToDB,
        deleteEventFromDB,
        loadEventsFromDB,
        updateNoEventsMessage
    };

})(jQuery);

// Альтернативная инициализация для случаев, когда jQuery загружается позже
if (typeof jQuery === 'undefined') {
    // Ждем загрузки jQuery
    function waitForJQuery() {
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery загружен, инициализируем Neo Calendar');
            // Перезапускаем основной скрипт
            jQuery(document).ready(function() {
                if (typeof window.NeoCalendar !== 'undefined') {
                    console.log('Neo Calendar уже инициализирован');
                } else {
                    console.log('Переинициализируем Neo Calendar');
                    // Здесь можно добавить повторную инициализацию если нужно
                }
            });
        } else {
            setTimeout(waitForJQuery, 100);
        }
    }
    waitForJQuery();
}
