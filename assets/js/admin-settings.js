jQuery(function($) {
    'use strict';

    // --- Logika zakładek w formularzu ---
    const tabs = $('.nav-tab-wrapper .nav-tab[href^="#"]');
    const tabContents = $('.settings-tab-content');
    const activeTabInput = $('#rep_active_sub_tab_input');

    if (tabs.length) {
        tabs.on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).attr('href');
            
            tabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            tabContents.hide();
            $(targetId).show();

            activeTabInput.val(targetId.replace('#reader-engagement-pro-popup-', ''));
            localStorage.setItem('repActiveSubTab', targetId);
        });

        const activeSubTab = localStorage.getItem('repActiveSubTab');
        if (activeSubTab && $(activeSubTab).length) {
            tabs.filter(`[href="${activeSubTab}"]`).click();
        } else {
            tabs.first().click();
        }
    }

    // --- Logika podglądu na żywo ---
    const previewArea = $('#rep-popup-preview-area');
    if (previewArea.length) {
        const desktopBtn = $('#rep-preview-desktop-btn');
        const mobileBtn = $('#rep-preview-mobile-btn');
        const previewPopup = $('#rep-intelligent-popup__container');

        // Przełączanie widoku
        desktopBtn.on('click', function() {
            previewArea.removeClass('is-mobile-view').addClass('is-desktop-view');
            $(this).addClass('active');
            mobileBtn.removeClass('active');
        });

        mobileBtn.on('click', function() {
            previewArea.removeClass('is-desktop-view').addClass('is-mobile-view');
            $(this).addClass('active');
            desktopBtn.removeClass('active');
        });

        // Dynamiczna aktualizacja stylów
        const styleMap = {
            '#popup_padding_y': { variable: '--rep-popup-padding-y', unit: 'px' },
            '#popup_padding_x': { variable: '--rep-popup-padding-x', unit: 'px' },
            '#popup_margin_content_bottom': { variable: '--rep-content-margin-bottom', unit: 'px' },
            '#popup_gap_list_items': { variable: '--rep-list-item-gap', unit: 'px' },
            '#popup_gap_grid_items': { variable: '--rep-grid-item-gap', unit: 'px' },
            '#popup_grid_item_width': { variable: '--rep-grid-item-width', unit: 'px' },
            '#popup_max_width': { variable: '--rep-popup-max-width', unit: 'px' },
            '#popup_max_height': { variable: '--rep-popup-max-height', unit: 'vh' },
            '#popup_rec_thumb_margin_right': { variable: '--rep-rec-thumb-margin-right', unit: 'px' },
            '#popup_rec_thumb_width_horizontal': { variable: '--rep-rec-thumb-width-horizontal', unit: 'px' },
            '#popup_rec_thumb_width_list_vertical': { variable: '--rep-rec-thumb-width-list-vertical', unit: '%' },
            '#popup_rec_button_border_radius': { variable: '--rep-btn-border-radius', unit: 'px' },
            '#popup_rec_margin_meta_bottom': { variable: '--rep-rec-meta-margin-bottom', unit: 'px' },
            '#popup_rec_margin_title_bottom': { variable: '--rep-rec-title-margin-bottom', unit: 'px' },
            '#popup_rec_margin_excerpt_bottom': { variable: '--rep-rec-excerpt-margin-bottom', unit: 'px' },
            '#popup_rec_button_bg_color': { variable: '--rep-btn-bg' },
            '#popup_rec_button_text_color': { variable: '--rep-btn-text' },
            '#popup_rec_button_bg_hover_color': { variable: '--rep-btn-bg-hover' },
            '#popup_rec_button_text_hover_color': { variable: '--rep-btn-text-hover' },
        };

        const updateLivePreview = () => {
            for (const [selector, { variable, unit = '' }] of Object.entries(styleMap)) {
                const input = $(selector);
                let value;

                if (input.is(':checkbox')) {
                    // Dla checkboxów, wartość jest 'true' lub 'false'
                    value = input.is(':checked'); 
                    // Możesz potrzebować specyficznej logiki do obsługi true/false w CSS
                } else if (input.hasClass('wp-color-picker-field')) {
                    // Dla color pickera, wartość jest pobierana bezpośrednio
                    value = input.val();
                } else {
                    // Dla pozostałych inputów
                    value = input.val();
                }

                if (value !== null && value !== undefined) {
                    if (typeof value === 'string' && unit) {
                        previewPopup.css(variable, value + unit);
                    } else {
                        previewPopup.css(variable, value);
                    }
                }
            }
        };

        // Ulepszone nasłuchiwanie na wszystkie istotne zdarzenia w całym formularzu
        const form = $('.rep-settings-form-wrapper form');

        // Nasłuchuj na zdarzenia 'input' i 'change' dla standardowych pól
        form.on('input change', 'input[type="text"], input[type="number"], input[type="range"], input[type="hidden"], select, textarea', updateLivePreview);

        // Specjalne traktowanie dla checkboxów i radio buttonów
        form.on('change', 'input[type="checkbox"], input[type="radio"]', updateLivePreview);

        // Dla pól wyboru koloru, użyj dedykowanego zdarzenia 'change' z wpColorPicker
        $('.wp-color-picker-field').each(function() {
            $(this).wpColorPicker({
                change: function(event, ui) {
                    // Opóźnienie, aby zapewnić, że wartość pola jest zaktualizowana
                    setTimeout(() => {
                        updateLivePreview();
                    }, 50);
                }
            });
        });
        
        // Inicjalizacja przy załadowaniu strony
        updateLivePreview(); 
        updatePreviewOrderAndVisibility('desktop');
        updatePreviewOrderAndVisibility('mobile');
    }

    function updatePreviewOrderAndVisibility(view) {
        const builder = $(`#rep-layout-builder-${view}`);
        if (!builder.length) return;
    
        const previewList = $('#rep-popup-preview-area .rep-rec-content');
        if (!previewList.length) return;
    
        const componentMap = {
            'thumbnail': '.rep-rec-thumb-link',
            'meta': '.rep-rec-meta',
            'title': '.rep-rec-title',
            'excerpt': '.rep-rec-excerpt',
            'link': '.rep-rec-button'
        };
    
        // Zbieranie kolejności i widoczności z kontrolek
        const order = builder.find('input[type="hidden"]').map(function() {
            return $(this).val();
        }).get();
    
        const visibility = {};
        builder.find('input[type="checkbox"]').each(function() {
            const key = $(this).attr('id').replace(`v_`, '').replace(`_${view}`, '');
            visibility[key] = $(this).is(':checked');
        });
    
        // Aktualizacja podglądu
        previewList.each(function() {
            const content = $(this);
            // Zapisz oryginalne elementy, aby uniknąć ich utraty
            if (!content.data('original-order')) {
                const originalOrder = {};
                for (const key in componentMap) {
                    originalOrder[key] = content.find(componentMap[key]).detach();
                }
                content.data('original-order', originalOrder);
            }
    
            const originalElements = content.data('original-order');
            content.empty(); // Wyczyść kontener
    
            // Dodaj elementy w nowej kolejności i z uwzględnieniem widoczności
            order.forEach(key => {
                if (visibility[key] && originalElements[key]) {
                    content.append(originalElements[key]);
                }
            });
        });
    }

    // --- Logika sortowania ---
    const initSortable = (containerId, view) => {
        const container = $(`#${containerId}`);
        if (container.length) {
            container.sortable({
                handle: '.dashicons-menu',
                placeholder: 'rep-sortable-placeholder',
                forcePlaceholderSize: true,
                update: function() {
                    updatePreviewOrderAndVisibility(view);
                }
            }).disableSelection();

            // Nasłuchuj na zmiany checkboxów wewnątrz sortowalnego kontenera
            container.on('change', 'input[type="checkbox"]', function() {
                updatePreviewOrderAndVisibility(view);
            });
        }
    };

    initSortable('rep-layout-builder-desktop', 'desktop');
    initSortable('rep-layout-builder-mobile', 'mobile');
});