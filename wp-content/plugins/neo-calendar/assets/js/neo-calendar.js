/**
 * Neo Calendar - JavaScript
 * Обработчики событий и AJAX функционал
 */

(function($) {
    'use strict';

    // Ждем загрузки DOM
    $(document).ready(function() {
        
        // Инициализация плагина
        initNeoCalendar();
        
        // Обработчик формы настроек
        $('#neo-calendar-settings-form').on('submit', handleSettingsSubmit);
        
        // Обновление preview в реальном времени
        $('#plugin-name, #plugin-description, #plugin-version').on('input', updatePreview);
        
        // Обработчик кнопки обновления виджета
        $(document).on('click', '.refresh-btn', refreshWidget);
        
        // Анимация появления элементов
        animateElements();
        
        // Инициализация tooltips
        initTooltips();
    });

    /**
     * Инициализация плагина
     */
    function initNeoCalendar() {
        console.log('Neo Calendar initialized! 🚀');
        console.log('Document ready state:', document.readyState);
        console.log('jQuery version:', $.fn.jquery);
        
        // Показываем приветственное сообщение
        showWelcomeMessage();
        
        // Добавляем классы для анимации
        $('.card').addClass('fade-in-up');
        
        // FullCalendar теперь загружается напрямую в HTML
        console.log('Neo Calendar initialized! FullCalendar will be loaded directly in HTML.');
    }

    /**
     * Обработка отправки формы настроек
     */
    function handleSettingsSubmit(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.text();
        
        // Показываем состояние загрузки
        submitBtn.prop('disabled', true).text('Сохранение...');
        
        // Собираем данные формы
        const formData = {
            action: 'neo_calendar_action',
            nonce: neoCalendarAjax.nonce,
            message: 'Settings saved successfully!',
            settings: {
                name: $('#plugin-name').val(),
                description: $('#plugin-description').val(),
                version: $('#plugin-version').val()
            }
        };
        
        // Отправляем AJAX запрос
        $.post(neoCalendarAjax.ajaxurl, formData)
            .done(function(response) {
                if (response.success) {
                    showNotification('Настройки успешно сохранены!', 'success');
                    
                    // Обновляем заголовок страницы
                    $('.card-header h3').text('Настройки - ' + formData.settings.name);
                    
                    // Анимация успеха
                    form.addClass('saved-success');
                    setTimeout(() => form.removeClass('saved-success'), 2000);
                } else {
                    showNotification('Ошибка при сохранении: ' + response.data, 'error');
                }
            })
            .fail(function(xhr, status, error) {
                showNotification('Ошибка соединения: ' + error, 'error');
            })
            .always(function() {
                // Восстанавливаем кнопку
                submitBtn.prop('disabled', false).text(originalText);
            });
    }

    /**
     * Обновление виджета
     */
    function refreshWidget() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        // Показываем состояние загрузки
        btn.prop('disabled', true).html('<i class="bi bi-arrow-clockwise spin"></i> Обновление...');
        
        // Имитируем задержку обновления
        setTimeout(function() {
            // Обновляем данные виджета
            updateWidgetData();
            
            // Восстанавливаем кнопку
            btn.prop('disabled', false).html(originalHtml);
            
            // Показываем уведомление
            showNotification('Виджет обновлен!', 'success');
        }, 1500);
    }

    /**
     * Обновление данных виджета
     */
    function updateWidgetData() {
        // Здесь можно добавить логику обновления данных
        // Например, обновить статистику, счетчики и т.д.
        
        // Анимация обновления
        $('.widget-stats').addClass('updated');
        setTimeout(() => $('.widget-stats').removeClass('updated'), 1000);
    }

    /**
     * Обновление preview в реальном времени
     */
    function updatePreview() {
        const name = $('#plugin-name').val() || 'Название плагина';
        const description = $('#plugin-description').val() || 'Описание плагина';
        const version = $('#plugin-version').val() || '1.0.0';
        
        // Обновляем preview
        $('#preview-name').text(name);
        $('#preview-description').text(description);
        $('#preview-version').text('v' + version);
        
        // Анимация обновления
        $('#preview-card').addClass('preview-updated');
        setTimeout(() => $('#preview-card').removeClass('preview-updated'), 500);
    }

    /**
     * Анимация появления элементов
     */
    function animateElements() {
        // Анимация для карточек
        $('.card').each(function(index) {
            const card = $(this);
            setTimeout(() => {
                card.addClass('fade-in-up');
            }, index * 100);
        });
        
        // Анимация для статистики
        $('.widget-stats h4').each(function() {
            const number = $(this);
            const finalValue = parseInt(number.text());
            animateNumber(number, 0, finalValue, 1000);
        });
    }

    /**
     * Анимация чисел
     */
    function animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (end - start) * progress);
            element.text(current);
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        }
        
        requestAnimationFrame(updateNumber);
    }

    /**
     * Инициализация tooltips
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            const element = $(this);
            const tooltip = element.attr('data-tooltip');
            
            // Добавляем класс для tooltip
            element.addClass('tooltip');
        });
    }

    /**
     * Показ приветственного сообщения
     */
    function showWelcomeMessage() {
        const welcomeHtml = `
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                Добро пожаловать в Neo Calendar! 🎉
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.card-body').first().prepend(welcomeHtml);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    /**
     * Показ уведомлений
     */
    function showNotification(message, type = 'info') {
        // Удаляем существующие уведомления
        $('.notification').remove();
        
        const notification = $(`
            <div class="notification ${type}">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        // Показываем уведомление
        setTimeout(() => notification.addClass('show'), 100);
        
        // Скрываем через 3 секунды
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Открытие модального окна
     */
    function openModal(content) {
        const modal = $(`
            <div class="modal-overlay">
                <div class="modal-content">
                    ${content}
                    <button class="btn btn-secondary mt-3" onclick="closeModal()">Закрыть</button>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        setTimeout(() => modal.addClass('show'), 100);
    }

    /**
     * Закрытие модального окна
     */
    function closeModal() {
        $('.modal-overlay').removeClass('show');
        setTimeout(() => $('.modal-overlay').remove(), 300);
    }

    /**
     * Экспорт данных
     */
    function exportData(format = 'json') {
        const data = {
            plugin: 'Neo Calendar',
            version: '1.0.0',
            exportDate: new Date().toISOString(),
            settings: {
                name: $('#plugin-name').val(),
                description: $('#plugin-description').val(),
                version: $('#plugin-version').val()
            }
        };
        
        if (format === 'json') {
            downloadFile(JSON.stringify(data, null, 2), 'neo-calendar-settings.json', 'application/json');
        } else if (format === 'csv') {
            const csv = convertToCSV(data);
            downloadFile(csv, 'neo-calendar-settings.csv', 'text/csv');
        }
    }

    /**
     * Конвертация в CSV
     */
    function convertToCSV(data) {
        const rows = [];
        
        // Добавляем заголовки
        rows.push(['Key', 'Value']);
        
        // Добавляем данные
        Object.entries(data).forEach(([key, value]) => {
            if (typeof value === 'object') {
                Object.entries(value).forEach(([subKey, subValue]) => {
                    rows.push([`${key}.${subKey}`, subValue]);
                });
            } else {
                rows.push([key, value]);
            }
        });
        
        return rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    }

    /**
     * Скачивание файла
     */
    function downloadFile(content, filename, contentType) {
        const blob = new Blob([content], { type: contentType });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    }

    /**
     * Импорт данных
     */
    function importData(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                
                // Проверяем структуру данных
                if (data.plugin === 'Neo Calendar' && data.settings) {
                    // Заполняем форму
                    $('#plugin-name').val(data.settings.name || '');
                    $('#plugin-description').val(data.settings.description || '');
                    $('#plugin-version').val(data.settings.version || '');
                    
                    // Обновляем preview
                    updatePreview();
                    
                    showNotification('Данные успешно импортированы!', 'success');
                } else {
                    showNotification('Неверный формат файла!', 'error');
                }
            } catch (error) {
                showNotification('Ошибка при чтении файла: ' + error.message, 'error');
            }
        };
        
        reader.readAsText(file);
    }

    /**
     * Сброс настроек
     */
    function resetSettings() {
        if (confirm('Вы уверены, что хотите сбросить все настройки?')) {
            // Сбрасываем форму
            $('#neo-calendar-settings-form')[0].reset();
            
            // Обновляем preview
            updatePreview();
            
            showNotification('Настройки сброшены!', 'info');
        }
    }

    /**
     * Поиск по настройкам
     */
    function searchSettings(query) {
        const searchTerm = query.toLowerCase();
        
        $('.form-group').each(function() {
            const group = $(this);
            const label = group.find('label').text().toLowerCase();
            const input = group.find('input, textarea, select');
            
            if (label.includes(searchTerm) || input.val().toLowerCase().includes(searchTerm)) {
                group.show();
                group.addClass('search-highlight');
            } else {
                group.hide();
                group.removeClass('search-highlight');
            }
        });
        
        // Убираем подсветку через 2 секунды
        setTimeout(() => {
            $('.search-highlight').removeClass('search-highlight');
        }, 2000);
    }

    /**
     * Валидация формы
     */
    function validateForm() {
        let isValid = true;
        const errors = [];
        
        // Проверяем обязательные поля
        const requiredFields = ['plugin-name', 'plugin-description', 'plugin-version'];
        
        requiredFields.forEach(fieldId => {
            const field = $('#' + fieldId);
            const value = field.val().trim();
            
            if (!value) {
                field.addClass('is-invalid');
                isValid = false;
                errors.push(`Поле "${field.prev('label').text()}" обязательно для заполнения`);
            } else {
                field.removeClass('is-invalid');
            }
        });
        
        // Проверяем версию
        const version = $('#plugin-version').val();
        if (version && !/^\d+\.\d+\.\d+$/.test(version)) {
            $('#plugin-version').addClass('is-invalid');
            isValid = false;
            errors.push('Версия должна быть в формате X.Y.Z');
        }
        
        return { isValid, errors };
    }



    // Экспортируем функции для глобального использования
    window.NeoCalendar = {
        openModal,
        closeModal,
        exportData,
        importData,
        resetSettings,
        searchSettings,
        validateForm
    };

})(jQuery);
