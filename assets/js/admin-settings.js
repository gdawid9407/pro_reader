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
                const value = $(selector).val();
                if (value) {
                    previewPopup.css(variable, value + unit);
                }
            }
        };

        $('.rep-settings-form-wrapper').on('input change', 'input, select', updateLivePreview);
        $('.wp-color-picker-field').wpColorPicker({ change: updateLivePreview });
        updateLivePreview(); // Inicjalizacja
    }
});