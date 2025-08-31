(function ($) {
    'use strict';

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

        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.neoCalendar) {
                        window.neoCalendar.refetchEvents();
                    }
                    alert('Ereignis hinzugefügt!');
                } else {
                    alert('Fehler beim Speichern: ' + data.data);
                }
            })
            .catch(err => alert('Fehler beim Speichern: ' + err.message));
    }


    function addWorkTime(dateEl, fromEl, toEl) {
        const date = dateEl.value;
        const timeFrom = fromEl.value;
        const timeTo = toEl.value;

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

        saveEventToDB('arbeitsstunde', '', startDateTime, endDateTime, '');
    }


    function addVacation(dateFromEl, dateToEl) {
        const dateFrom = dateFromEl.value;
        const dateTo = dateToEl.value;

        if (!dateFrom || !dateTo) {
            alert('Bitte füllen Sie alle Felder aus!');
            return;
        }

        if (dateFrom > dateTo) {
            alert('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
            return;
        }

        const endDate = new Date(dateTo);
        endDate.setDate(endDate.getDate() + 1);
        const endDateStr = endDate.toISOString().split('T')[0];

        saveEventToDB('urlaub', '', dateFrom, endDateStr, '');
    }

    // Функция для переключения форм в виджете
    function toggleWidgetForms() {
        const workForm = document.getElementById('widget-work-form');
        const vacationForm = document.getElementById('widget-vacation-form');
        const addWorkBtn = document.getElementById('widget-add-work-time-btn');
        const addVacationBtn = document.getElementById('widget-add-vacation-btn');
        const toggleBtn = document.getElementById('widget-show-vacation-form-btn');
        
        if (workForm && vacationForm && addWorkBtn && addVacationBtn && toggleBtn) {
            if (workForm.style.display !== 'none') {
                // Показываем форму отпуска
                workForm.style.display = 'none';
                vacationForm.style.display = 'flex';
                addWorkBtn.style.display = 'none';
                addVacationBtn.style.display = 'inline-block';
                toggleBtn.innerHTML = '<i class="bi bi-arrow-left"></i> Zurück';
            } else {
                // Показываем форму рабочего времени
                workForm.style.display = 'flex';
                vacationForm.style.display = 'none';
                addWorkBtn.style.display = 'inline-block';
                addVacationBtn.style.display = 'none';
                toggleBtn.innerHTML = '<i class="bi bi-calendar-x"></i> Urlaub';
            }
        }
    }

    // Функция для переключения на форму отпуска в основной форме
    function showVacationForm() {
        const workForm = document.getElementById('work-time-form');
        const vacationForm = document.getElementById('vacation-form');
        
        if (workForm && vacationForm) {
            workForm.style.display = 'none';
            vacationForm.style.display = 'block';
        }
    }

    // Функция для возврата к форме рабочего времени в основной форме
    function showWorkForm() {
        const workForm = document.getElementById('work-time-form');
        const vacationForm = document.getElementById('vacation-form');
        
        if (workForm && vacationForm) {
            workForm.style.display = 'block';
            vacationForm.style.display = 'none';
        }
    }

    function initTimePicker(elementId) {
        if (typeof flatpickr === 'undefined') {
            console.error("Flatpickr not loaded");
            return;
        }
    
        const el = document.getElementById(elementId);
        if (!el) {
            console.warn(`Element with id "${elementId}" not found`);
            return;
        }
    
        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
        });
    }

    window.NeoCalendar = {
        saveEventToDB,
        addWorkTime,
        addVacation,
        toggleWidgetForms,
        showVacationForm,
        showWorkForm,
        initTimePicker
    };
})(jQuery);