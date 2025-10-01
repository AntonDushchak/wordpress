/**
 * Neo Job Board - Profession Autocomplete
 * Автоподсказки профессий для полей ввода
 * Version: 1.0.0
 */

(function ($) {
    'use strict';
    
    window.NeoProfessionAutocomplete = {
        professions: [],
        initialized: false,
        
        // Инициализация
        init: function() {
            if (this.initialized) {
                return;
            }
            
            this.loadProfessions();
            this.bindEvents();
            this.initialized = true;
        },

        // Загрузка списка профессий
        loadProfessions: function() {
            const self = this;
            
            $.ajax({
                url: neoJobBoardAjax.pluginUrl + 'assets/data/professions.json',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    self.professions = data;
                },
                error: function(xhr, status, error) {
                    // Профессии не загружены
                }
            });
        },

        // Привязка событий
        bindEvents: function() {
            const self = this;
            
            // Автоматически подключаем автоподсказки к полям при их создании
            $(document).on('focus', 'input[data-autocomplete="professions"]', function() {
                self.initAutocomplete($(this));
            });
            
            // Для существующих полей
            $(document).on('DOMNodeInserted', function(e) {
                $(e.target).find('input[data-autocomplete="professions"]').each(function() {
                    self.initAutocomplete($(this));
                });
            });
        },

        // Инициализация автоподсказок для конкретного поля
        initAutocomplete: function($input) {
            if ($input.data('autocomplete-initialized')) {
                return;
            }
            
            const self = this;
            const inputId = $input.attr('id') || 'autocomplete-' + Date.now();
            $input.attr('id', inputId);
            
            // Создаем контейнер для подсказок
            const $autocompleteContainer = $(`
                <div class="neo-autocomplete-container" id="${inputId}-autocomplete">
                    <ul class="neo-autocomplete-list"></ul>
                </div>
            `);
            
            $input.after($autocompleteContainer);
            $input.data('autocomplete-initialized', true);
            
            let timeout;
            
            // Обработчик ввода
            $input.on('input', function() {
                const query = $(this).val().trim();
                
                clearTimeout(timeout);
                
                if (query.length < 2) {
                    $autocompleteContainer.hide();
                    return;
                }
                
                timeout = setTimeout(function() {
                    self.showSuggestions($input, $autocompleteContainer, query);
                }, 150);
            });
            
            // Скрытие при потере фокуса
            $input.on('blur', function() {
                setTimeout(function() {
                    $autocompleteContainer.hide();
                }, 200);
            });
            
            // Обработка клавиш навигации
            $input.on('keydown', function(e) {
                const $list = $autocompleteContainer.find('.neo-autocomplete-list');
                const $active = $list.find('.neo-autocomplete-item.active');
                
                switch(e.keyCode) {
                    case 38: // Стрелка вверх
                        e.preventDefault();
                        if ($active.length) {
                            const $prev = $active.removeClass('active').prev();
                            if ($prev.length) {
                                $prev.addClass('active');
                            } else {
                                $list.children().last().addClass('active');
                            }
                        } else {
                            $list.children().last().addClass('active');
                        }
                        break;
                        
                    case 40: // Стрелка вниз
                        e.preventDefault();
                        if ($active.length) {
                            const $next = $active.removeClass('active').next();
                            if ($next.length) {
                                $next.addClass('active');
                            } else {
                                $list.children().first().addClass('active');
                            }
                        } else {
                            $list.children().first().addClass('active');
                        }
                        break;
                        
                    case 13: // Enter
                        if ($active.length) {
                            e.preventDefault();
                            $active.click();
                        }
                        break;
                        
                    case 27: // Escape
                        $autocompleteContainer.hide();
                        break;
                }
            });
        },

        // Показ подсказок
        showSuggestions: function($input, $container, query) {
            const suggestions = this.searchProfessions(query);
            const $list = $container.find('.neo-autocomplete-list');
            
            if (suggestions.length === 0) {
                $container.hide();
                return;
            }
            
            $list.empty();
            
            suggestions.slice(0, 10).forEach(function(profession) {
                const $item = $(`
                    <li class="neo-autocomplete-item">
                        <span class="profession-name">${profession.highlighted}</span>
                    </li>
                `);
                
                $item.on('click', function() {
                    $input.val(profession.original).trigger('change');
                    $container.hide();
                });
                
                $item.on('mouseenter', function() {
                    $list.find('.neo-autocomplete-item').removeClass('active');
                    $(this).addClass('active');
                });
                
                $list.append($item);
            });
            
            $container.show();
        },

        // Поиск профессий
        searchProfessions: function(query) {
            const queryLower = query.toLowerCase();
            const results = [];
            
            this.professions.forEach(function(profession) {
                const professionLower = profession.toLowerCase();
                
                // Точное совпадение в начале
                if (professionLower.startsWith(queryLower)) {
                    results.push({
                        original: profession,
                        highlighted: profession.replace(new RegExp(`(${query})`, 'gi'), '<strong>$1</strong>'),
                        score: 100
                    });
                }
                // Совпадение в любом месте
                else if (professionLower.includes(queryLower)) {
                    results.push({
                        original: profession,
                        highlighted: profession.replace(new RegExp(`(${query})`, 'gi'), '<strong>$1</strong>'),
                        score: 50
                    });
                }
                // Fuzzy search - совпадение отдельных слов
                else {
                    const words = queryLower.split(' ');
                    let matchCount = 0;
                    
                    words.forEach(function(word) {
                        if (word.length > 1 && professionLower.includes(word)) {
                            matchCount++;
                        }
                    });
                    
                    if (matchCount > 0) {
                        let highlighted = profession;
                        words.forEach(function(word) {
                            if (word.length > 1) {
                                highlighted = highlighted.replace(new RegExp(`(${word})`, 'gi'), '<strong>$1</strong>');
                            }
                        });
                        
                        results.push({
                            original: profession,
                            highlighted: highlighted,
                            score: (matchCount / words.length) * 25
                        });
                    }
                }
            });
            
            // Сортировка по релевантности
            return results.sort((a, b) => b.score - a.score);
        },

        // Применение автоподсказок к существующим полям
        applyToFields: function() {
            const self = this;
            
            // Поля для которых нужны автоподсказки
            const fieldSelectors = [
                'input[name*="position"]',           // Желаемая позиция
                'input[name*="job_title"]',          // Должность
                'input[name*="experience"]',         // Опыт работы
                'input[name*="education"]',          // Образование
                'input[name*="profession"]',         // Профессия
                'input[name*="occupation"]',         // Род занятий
                'textarea[name*="experience"]',      // Опыт работы (textarea)
                'textarea[name*="education"]'        // Образование (textarea)
            ];
            
            // Автоматически применяем к подходящим полям
            fieldSelectors.forEach(function(selector) {
                $(selector).each(function() {
                    if (!$(this).data('autocomplete-initialized')) {
                        $(this).attr('data-autocomplete', 'professions');
                        self.initAutocomplete($(this));
                    }
                });
            });
        }
    };

    // Инициализация при загрузке DOM
    $(document).ready(function() {
        if (typeof neoJobBoardAjax !== 'undefined') {
            NeoProfessionAutocomplete.init();
            
            // Применяем к существующим полям через небольшую задержку
            setTimeout(function() {
                NeoProfessionAutocomplete.applyToFields();
            }, 500);
        }
    });

})(jQuery);