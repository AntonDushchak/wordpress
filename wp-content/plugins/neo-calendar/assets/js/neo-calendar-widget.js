(function ($) {
    'use strict';

    $(document).ready(function () {
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

        const addVacationBtn = document.getElementById('widget-add-vacation-btn');
        if (addVacationBtn) {
            addVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.addVacation(
                    document.getElementById('widget-vacation-date-range')
                );
            });
        }

        const showVacationBtn = document.getElementById('widget-show-vacation-form-btn');
        if (showVacationBtn) {
            showVacationBtn.addEventListener('click', function () {
                window.NeoCalendar.toggleWidgetForms();
            });
        }

        const from = document.getElementById("widget-work-time-from");
        const to = document.getElementById("widget-work-time-to");
    
        if (from && to) {
            window.NeoCalendar.initTimePicker("widget-work-time-from");
            window.NeoCalendar.initTimePicker("widget-work-time-to");
        } else {
            console.log("Time-Picker-Elemente nicht gefunden:", { from, to });
        }

        if (window.NeoCalendar && window.NeoCalendar.initDatePicker) {
            window.NeoCalendar.initDatePicker("widget-work-date");
        }

        if (window.NeoCalendar && window.NeoCalendar.initDateRangePicker) {
            window.NeoCalendar.initDateRangePicker("widget-vacation-date-range");
        }
    });

    window.NeoCalendar = {
        ...window.NeoCalendar,
    };

})(jQuery);