(function ($) {
    'use strict';

    $(document).ready(function () {
        // Кнопка добавления рабочего времени
        const addWorkTimeBtn = document.getElementById('widget-add-work-time-btn');
        if (addWorkTimeBtn) {
            addWorkTimeBtn.addEventListener('click', function () {
                window.NeoCalendar.addWorkTime(
                    document.getElementById('widget-work-date'),
                    document.getElementById('widget-work-time-from'),
                    document.getElementById('widget-work-time-to')
                );
            });
        }

        // Кнопка добавления отпуска
        const addVacationBtn = document.getElementById('widget-add-vacation-btn');
        if (addVacationBtn) {
            addVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.addVacation(
                    document.getElementById('widget-vacation-date-from'),
                    document.getElementById('widget-vacation-date-to')
                );
            });
        }

        // Кнопка переключения на форму отпуска
        const showVacationBtn = document.getElementById('widget-show-vacation-form-btn');
        if (showVacationBtn) {
            showVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.toggleWidgetForms();
            });
        }

        // Инициализация time picker'ов для виджета
        const from = document.getElementById("widget-work-time-from");
        const to = document.getElementById("widget-work-time-to");
    
        if (from && to) {
            window.NeoCalendar.initTimePicker("widget-work-time-from");
            window.NeoCalendar.initTimePicker("widget-work-time-to");
        } else {
            console.log("Элементы time picker'а не найдены:", { from, to });
        }
    });

    window.NeoCalendar = {
        ...window.NeoCalendar,
    };

})(jQuery);