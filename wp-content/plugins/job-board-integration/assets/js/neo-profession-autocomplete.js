(function ($) {
    'use strict';
    
    window.NeoProfessionAutocomplete = {
        professions: [],
        initialized: false,
        
        init: function() {
            if (this.initialized) {
                return;
            }
            
            this.loadProfessions();
            this.bindEvents();
            this.initialized = true;
        },

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
                }
            });
        },

        bindEvents: function() {
            const self = this;
            
            $(document).on('focus', 'input[data-autocomplete="professions"]', function() {
                self.initAutocomplete($(this));
            });
            
            $(document).on('DOMNodeInserted', function(e) {
                $(e.target).find('input[data-autocomplete="professions"]').each(function() {
                    self.initAutocomplete($(this));
                });
            });
        },

        initAutocomplete: function($input) {
            if ($input.data('autocomplete-initialized')) {
                return;
            }
            
            const self = this;
            const inputId = $input.attr('id') || 'autocomplete-' + Date.now();
            $input.attr('id', inputId);
            
            const $autocompleteContainer = $(`
                <div class="neo-autocomplete-container" id="${inputId}-autocomplete">
                    <ul class="neo-autocomplete-list"></ul>
                </div>
            `);
            
            $input.after($autocompleteContainer);
            $input.data('autocomplete-initialized', true);
            
            let timeout;
            
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
            
            $input.on('blur', function() {
                setTimeout(function() {
                    $autocompleteContainer.hide();
                }, 200);
            });
            
            $input.on('keydown', function(e) {
                const $list = $autocompleteContainer.find('.neo-autocomplete-list');
                const $active = $list.find('.neo-autocomplete-item.active');
                
                switch(e.keyCode) {
                    case 38:
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
                        
                    case 40:
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
                        
                    case 13:
                        if ($active.length) {
                            e.preventDefault();
                            $active.click();
                        }
                        break;
                        
                    case 27:
                        $autocompleteContainer.hide();
                        break;
                }
            });
        },

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

        searchProfessions: function(query) {
            const queryLower = query.toLowerCase();
            const results = [];
            
            this.professions.forEach(function(profession) {
                const professionLower = profession.toLowerCase();
                
                if (professionLower.startsWith(queryLower)) {
                    results.push({
                        original: profession,
                        highlighted: profession.replace(new RegExp(`(${query})`, 'gi'), '<strong>$1</strong>'),
                        score: 100
                    });
                }
                else if (professionLower.includes(queryLower)) {
                    results.push({
                        original: profession,
                        highlighted: profession.replace(new RegExp(`(${query})`, 'gi'), '<strong>$1</strong>'),
                        score: 50
                    });
                }
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
            
            return results.sort((a, b) => b.score - a.score);
        },

        applyToFields: function() {
            const self = this;

            const fieldSelectors = [
                'input[name*="position"]',
                'input[name*="job_title"]',
                'input[name*="experience"]',
                'input[name*="education"]',
                'input[name*="profession"]',
                'input[name*="occupation"]',
                'textarea[name*="experience"]',     
                'textarea[name*="education"]'       
            ];
            
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

    $(document).ready(function() {
        if (typeof neoJobBoardAjax !== 'undefined') {
            NeoProfessionAutocomplete.init();
            
            setTimeout(function() {
                NeoProfessionAutocomplete.applyToFields();
            }, 500);
        }
    });

})(jQuery);