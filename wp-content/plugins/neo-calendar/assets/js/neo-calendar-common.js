(function ($) {
    'use strict';

    function saveEventToDB(type, title, start, end, description) {
        if (typeof neoCalendarAjax === 'undefined') { 
            if (window.NeoDash && window.NeoDash.toastError) {
                NeoDash.toastError('AJAX-Konfigurationsfehler - neoCalendarAjax ist nicht definiert');
            } else {
                alert('AJAX-Konfigurationsfehler - neoCalendarAjax ist nicht definiert');
            }
            return; 
        }
        const formData = new FormData();
        formData.append('action', 'neo_calendar_save_event');
        formData.append('nonce', neoCalendarAjax.nonce);
        formData.append('type', type);
        formData.append('title', title);
        formData.append('start', start);
        formData.append('end', end);
        formData.append('description', description);

        fetch(neoCalendarAjax.ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.neoCalendar) {
                        window.neoCalendar.refetchEvents();
                    }
                    if (window.NeoDash && window.NeoDash.toastSuccess) {
                        NeoDash.toastSuccess('Ereignis hinzugefügt!');
                    } else {
                        alert('Ereignis hinzugefügt!');
                    }
                } else {
                    if (window.NeoDash && window.NeoDash.toastError) {
                        NeoDash.toastError('Fehler beim Speichern: ' + data.data);
                    } else {
                        alert('Fehler beim Speichern: ' + data.data);
                    }
                }
            })
            .catch(err => {
                if (window.NeoDash && window.NeoDash.toastError) {
                    NeoDash.toastError('Fehler beim Speichern: ' + err.message);
                } else {
                    alert('Fehler beim Speichern: ' + err.message);
                }
            });
    }

    function addWorkTime(dateEl, fromEl, toEl) {
        const dateFormatted = dateEl.value;
        const timeFrom = fromEl.value;
        const timeTo = toEl.value;

        if (!dateFormatted || !timeFrom || !timeTo) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Bitte füllen Sie alle Felder aus!');
            } else {
                alert('Bitte füllen Sie alle Felder aus!');
            }
            return;
        }

        if (timeFrom >= timeTo) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Die Zeit "von" muss kleiner als die Zeit "bis" sein!');
            } else {
                alert('Die Zeit "von" muss kleiner als die Zeit "bis" sein!');
            }
            return;
        }

        function convertDateFormat(dateStr) {
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        }

        const date = convertDateFormat(dateFormatted);
        const startDateTime = date + 'T' + timeFrom + ':00';
        const endDateTime = date + 'T' + timeTo + ':00';

        saveEventToDB('arbeitsstunde', '', startDateTime, endDateTime, '');
    }

    function addVacation(dateRangeEl) {
        const dateRange = dateRangeEl.value;

        if (!dateRange) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Bitte wählen Sie einen Datumsbereich aus!');
            } else {
                alert('Bitte wählen Sie einen Datumsbereich aus!');
            }
            return;
        }

        const dates = dateRange.split(' до ');
        if (dates.length !== 2) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Bitte wählen Sie einen gültigen Datumsbereich aus!');
            } else {
                alert('Bitte wählen Sie einen gültigen Datumsbereich aus!');
            }
            return;
        }

        const dateFromFormatted = dates[0].trim();
        const dateToFormatted = dates[1].trim();

        function convertDateFormat(dateStr) {
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        }

        const dateFrom = convertDateFormat(dateFromFormatted);
        const dateTo = convertDateFormat(dateToFormatted);

        if (dateFrom > dateTo) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
            } else {
                alert('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
            }
            return;
        }

        const endDate = new Date(dateTo);
        endDate.setDate(endDate.getDate() + 1);
        const endDateStr = endDate.toISOString().split('T')[0];

        saveEventToDB('urlaub', '', dateFrom, endDateStr, '');
    }

    function addEvent(dateRangeEl, timeEl, titleEl) {
        const dateRange = dateRangeEl.value;
        const time = timeEl.value;
        const title = titleEl.value;

        if (!dateRange || !time || !title) {
            if (window.NeoDash && window.NeoDash.toastWarning) {
                NeoDash.toastWarning('Bitte füllen Sie alle Felder aus!');
            } else {
                alert('Bitte füllen Sie alle Felder aus!');
            }
            return;
        }

        function convertDateFormat(dateStr) {
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
        }

        if (dateRange.includes(' bis ')) {
            const dates = dateRange.split(' bis ');
            if (dates.length !== 2) {
                if (window.NeoDash && window.NeoDash.toastWarning) {
                    NeoDash.toastWarning('Bitte wählen Sie einen gültigen Datumsbereich aus!');
                } else {
                    alert('Bitte wählen Sie einen gültigen Datumsbereich aus!');
                }
                return;
            }

            const dateFromFormatted = dates[0].trim();
            const dateToFormatted = dates[1].trim();
            const dateFrom = convertDateFormat(dateFromFormatted);
            const dateTo = convertDateFormat(dateToFormatted);

            if (dateFrom > dateTo) {
                if (window.NeoDash && window.NeoDash.toastWarning) {
                    NeoDash.toastWarning('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
                } else {
                    alert('Das Datum "von" muss kleiner oder gleich dem Datum "bis" sein!');
                }
                return;
            }

            const startDateTime = dateFrom + 'T' + time + ':00';
            const endDateTime = dateTo + 'T' + time + ':00';
            
            saveEventToDB('veranstaltung', title, startDateTime, endDateTime, '');
        } else {
            const date = convertDateFormat(dateRange);
            const startDateTime = date + 'T' + time + ':00';
            const endDateTime = date + 'T' + time + ':00';
            
            saveEventToDB('veranstaltung', title, startDateTime, endDateTime, '');
        }
    }
    function toggleWidgetForms() {
        const workForm = document.getElementById('widget-work-form');
        const vacationForm = document.getElementById('widget-vacation-form');
        const addWorkBtn = document.getElementById('widget-add-work-time-btn');
        const addVacationBtn = document.getElementById('widget-add-vacation-btn');
        const toggleBtn = document.getElementById('widget-show-vacation-form-btn');
        
        if (workForm && vacationForm && addWorkBtn && addVacationBtn && toggleBtn) {
            if (workForm.style.display !== 'none') {
                workForm.style.display = 'none';
                vacationForm.style.display = 'flex';
                addWorkBtn.style.display = 'none';
                addVacationBtn.style.display = 'inline-block';
                toggleBtn.innerHTML = '<i class="bi bi-arrow-left"></i> Zurück';
            } else {
                workForm.style.display = 'flex';
                vacationForm.style.display = 'none';
                addWorkBtn.style.display = 'inline-block';
                addVacationBtn.style.display = 'none';
                toggleBtn.innerHTML = '<i class="bi bi-calendar-x"></i> Urlaub';
            }
        }
    }

    function showVacationForm() {
        const workForm = document.getElementById('work-time-form');
        const vacationForm = document.getElementById('vacation-form');
        const eventForm = document.getElementById('event-form');
        
        if (workForm && vacationForm && eventForm) {
            workForm.style.display = 'none';
            vacationForm.style.display = 'block';
            eventForm.style.display = 'none';
        }
    }

    function showWorkForm() {
        const workForm = document.getElementById('work-time-form');
        const vacationForm = document.getElementById('vacation-form');
        const eventForm = document.getElementById('event-form');
        
        if (workForm && vacationForm && eventForm) {
            workForm.style.display = 'block';
            vacationForm.style.display = 'none';
            eventForm.style.display = 'none';
        }
    }

    function showEventForm() {
        const workForm = document.getElementById('work-time-form');
        const vacationForm = document.getElementById('vacation-form');
        const eventForm = document.getElementById('event-form');
        
        if (workForm && vacationForm && eventForm) {
            workForm.style.display = 'none';
            vacationForm.style.display = 'none';
            eventForm.style.display = 'block';
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
        
        if (!el.parentNode) {
            return;
        }

        if (el.type === 'time' || el.type === 'date') {
            el.type = 'text';
        }

        const existingFlatpickr = el._flatpickr;

        if (existingFlatpickr) {
            try {
                existingFlatpickr.destroy();
            } catch (e) {
            }
            el._flatpickr = null;
            el.removeAttribute('data-flatpickr-initialized');
        }

        const isDarkTheme = isDarkThemeActive();

        if (isDarkTheme) {
            loadFlatpickrDarkTheme();
        } else {
            removeFlatpickrDarkTheme();
        }

        try {
            flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                minuteIncrement: 15,
                allowInput: false,
                theme: isDarkTheme ? "dark" : "light",
                locale: "de"
            });
            el.setAttribute('data-flatpickr-initialized', 'true');
        } catch (e) {
            console.warn('Ошибка инициализации time picker:', e);
        }
    }

    function loadFlatpickrDarkTheme() {
        const existingDarkTheme = document.querySelector('link[href*="flatpickr/dist/themes/dark.css"]');
        if (existingDarkTheme) {
            return;
        }
        
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css';
        link.id = 'flatpickr-dark-theme';
        document.head.appendChild(link);
    }

    function removeFlatpickrDarkTheme() {
        const darkThemeLink = document.querySelector('#flatpickr-dark-theme');
        if (darkThemeLink) {
            darkThemeLink.remove();
        }
    }

    function isDarkThemeActive() {
        if (document.body.classList.contains('dark-theme') || 
            document.documentElement.classList.contains('dark-theme')) {
            return true;
        }
        
        if (document.body.getAttribute('data-theme') === 'dark' ||
            document.documentElement.getAttribute('data-theme') === 'dark') {
            return true;
        }
        
        if (document.body.classList.contains('dark') || 
            document.documentElement.classList.contains('dark')) {
            return true;
        }
        
        if (document.body.classList.contains('neo-dark') || 
            document.documentElement.classList.contains('neo-dark') ||
            document.body.getAttribute('data-neo-theme') === 'dark' ||
            document.documentElement.getAttribute('data-neo-theme') === 'dark') {
            return true;
        }
        
        if (!document.body.getAttribute('data-theme') && 
            !document.documentElement.getAttribute('data-theme') &&
            window.matchMedia && 
            window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return true;
        }
        
        return false;
    }

    function initDatePicker(elementId) {
        if (typeof flatpickr === 'undefined') {
            console.error("Flatpickr not loaded");
            return;
        }
    
        const el = document.getElementById(elementId);
        if (!el) {
            console.warn(`Element with id "${elementId}" not found`);
            return;
        }
        
        if (!el.parentNode) {
            return;
        }

        if (el.type === 'date' || el.type === 'time') {
            el.type = 'text';
        }

        const existingFlatpickr = el._flatpickr;

        if (existingFlatpickr) {
            try {
                existingFlatpickr.destroy();
            } catch (e) {
            }
            el._flatpickr = null;
            el.removeAttribute('data-flatpickr-initialized');
        }

        const isDarkTheme = isDarkThemeActive();

        if (isDarkTheme) {
            loadFlatpickrDarkTheme();
        } else {
            removeFlatpickrDarkTheme();
        }

        try {
            flatpickr(el, {
                dateFormat: "d-m-Y",
                allowInput: false,
                theme: isDarkTheme ? "dark" : "light",
                locale: "de"
            });
            el.setAttribute('data-flatpickr-initialized', 'true');
        } catch (e) {
            console.warn('Ошибка инициализации date picker:', e);
        }
    }

    function initDateRangePicker(elementId) {
        if (typeof flatpickr === 'undefined') {
            console.error("Flatpickr not loaded");
            return;
        }
    
        const el = document.getElementById(elementId);
        if (!el) {
            console.warn(`Element with id "${elementId}" not found`);
            return;
        }
        
        if (!el.parentNode) {
            return;
        }

        if (el.type === 'date' || el.type === 'time') {
            el.type = 'text';
        }

        const existingFlatpickr = el._flatpickr;

        if (existingFlatpickr) {
            try {
                existingFlatpickr.destroy();
            } catch (e) {
            }
            el._flatpickr = null;
            el.removeAttribute('data-flatpickr-initialized');
        }

        const isDarkTheme = isDarkThemeActive();

        if (isDarkTheme) {
            loadFlatpickrDarkTheme();
        } else {
            removeFlatpickrDarkTheme();
        }

        try {
            flatpickr(el, {
                mode: "range",
                dateFormat: "d-m-Y",
                allowInput: false,
                theme: isDarkTheme ? "dark" : "light",
                locale: "de"
            });
            el.setAttribute('data-flatpickr-initialized', 'true');
        } catch (e) {
            console.warn('Ошибка инициализации date range picker:', e);
        }
    }

    function watchThemeChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'class' || 
                     mutation.attributeName === 'data-theme' ||
                     mutation.attributeName === 'data-neo-theme')) {
                    updateFlatpickrTheme();
                }
            });
        });

        observer.observe(document.body, { 
            attributes: true, 
            attributeFilter: ['class', 'data-theme', 'data-neo-theme'] 
        });
        observer.observe(document.documentElement, { 
            attributes: true, 
            attributeFilter: ['class', 'data-theme', 'data-neo-theme'] 
        });

        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addListener(() => {
                updateFlatpickrTheme();
            });
        }
        
        document.addEventListener('neo-theme-changed', (e) => {
            setTimeout(updateFlatpickrTheme, 100);
        });
        
        document.addEventListener('themeChanged', updateFlatpickrTheme);
        document.addEventListener('theme-update', updateFlatpickrTheme);
        window.addEventListener('neo-dashboard-theme-changed', updateFlatpickrTheme);
    }

    function updateFlatpickrTheme() {
        const isDarkTheme = isDarkThemeActive();
        
        if (isDarkTheme) {
            loadFlatpickrDarkTheme();
        } else {
            removeFlatpickrDarkTheme();
        }

        document.querySelectorAll('[data-flatpickr-initialized]').forEach(el => {
            if (el._flatpickr) {
                el._flatpickr.set('theme', isDarkTheme ? 'dark' : 'light');
            }
        });
    }

    function debugTheme() {
        const isDark = isDarkThemeActive();
        const darkCSS = document.querySelector('#flatpickr-dark-theme');
        
        if (isDark) {
            loadFlatpickrDarkTheme();
        } else {
            removeFlatpickrDarkTheme();
        }
        
        updateFlatpickrTheme();
    }

    function reinitAllPickers() {
        const allPickers = document.querySelectorAll('[data-flatpickr-initialized], .date-picker, .date-range-picker, input[id*="time"], input[id*="date"]');
        
        allPickers.forEach(el => {
            if (!el.id && !el.hasAttribute('data-flatpickr-initialized') && !el.classList.contains('date-picker') && !el.classList.contains('date-range-picker')) {
                return;
            }

            const elementId = el.id;
            if (!elementId) return;

            if (el.type === 'date' || el.type === 'time') {
                el.type = 'text';
            }

            const existingFlatpickr = el._flatpickr;
            if (existingFlatpickr) {
                try {
                    existingFlatpickr.destroy();
                } catch (e) {
                }
                el._flatpickr = null;
                el.removeAttribute('data-flatpickr-initialized');
            }

            if (el.classList.contains('date-range-picker') || el.hasAttribute('data-range-picker') || elementId.includes('range')) {
                if (window.NeoCalendar && window.NeoCalendar.initDateRangePicker) {
                    window.NeoCalendar.initDateRangePicker(elementId);
                }
            } else if (el.classList.contains('time-picker') || elementId.includes('time')) {
                if (window.NeoCalendar && window.NeoCalendar.initTimePicker) {
                    window.NeoCalendar.initTimePicker(elementId);
                }
            } else if (el.classList.contains('date-picker') || elementId.includes('date')) {
                if (window.NeoCalendar && window.NeoCalendar.initDatePicker) {
                    window.NeoCalendar.initDatePicker(elementId);
                }
            }
        });
    }

    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            reinitAllPickers();
        }, 250);
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchThemeChanges);
    } else {
        watchThemeChanges();
    }

    window.NeoCalendar = {
        saveEventToDB,
        addWorkTime,
        addVacation,
        addEvent,
        toggleWidgetForms,
        showVacationForm,
        showWorkForm,
        showEventForm,
        initTimePicker,
        initDatePicker,
        initDateRangePicker,
        updateFlatpickrTheme,
        reinitAllPickers
    };
})(jQuery);