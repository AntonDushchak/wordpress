/**
 * Neo Dashboard Examples - JavaScript
 * 
 * Дополнительная функциональность для examples плагина
 */

(function() {
    'use strict';
    
    // Ждем загрузки DOM и готовности dashboard
    document.addEventListener('neoDashboardReady', function() {
        console.log('Neo Dashboard Examples: DOM ready');
        
        // Инициализация examples функциональности
        initExamples();
    });
    
    // Fallback если neoDashboardReady не сработал
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Neo Dashboard Examples: DOM loaded');
        
        // Проверяем, готов ли dashboard
        if (typeof bootstrap !== 'undefined') {
            initExamples();
        } else {
            // Ждем загрузки Bootstrap
            setTimeout(initExamples, 1000);
        }
    });
    
    function initExamples() {
        console.log('Neo Dashboard Examples: Initializing...');
        
        // Анимация для карточек
        animateCards();
        
        // Инициализация tooltips
        initTooltips();
        
        // Инициализация счетчиков
        initCounters();
        
        // Добавление интерактивности
        addInteractivity();
    }
    
    function animateCards() {
        const cards = document.querySelectorAll('.welcome-section .card');
        
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
    
    function initTooltips() {
        // Инициализация tooltips для иконок
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                new bootstrap.Tooltip(element);
            }
        });
    }
    
    function initCounters() {
        // Анимированные счетчики для статистики
        const counterElements = document.querySelectorAll('.counter');
        
        counterElements.forEach(element => {
            const target = parseInt(element.getAttribute('data-target') || '0');
            const duration = 2000; // 2 секунды
            const step = target / (duration / 16); // 60 FPS
            let current = 0;
            
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 16);
        });
    }
    
    function addInteractivity() {
        // Hover эффекты для карточек
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)';
            });
        });
        
        // Клик по карточкам
        cards.forEach(card => {
            card.addEventListener('click', function() {
                // Добавляем класс active
                cards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                
                // Показываем уведомление
                showNotification('Карточка "' + this.querySelector('.card-title')?.textContent + '" выбрана!', 'info');
            });
        });
    }
    
    function showNotification(message, type = 'info') {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Добавляем в body
        document.body.appendChild(notification);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    // Экспортируем функции для использования в других скриптах
    window.NeoDashboardExamples = {
        showNotification: showNotification,
        initExamples: initExamples
    };
    
})();

