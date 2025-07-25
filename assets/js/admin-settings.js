jQuery(function($) {
    'use strict';

    if (typeof REP_Admin_Settings === 'undefined') {
        console.error('REP Admin Settings object not found.');
        return;
    }

    const optionPrefix = REP_Admin_Settings.option_name_attr;
    const $layoutBuilder = $('#rep-layout-builder');
    const $settingsForm = $('#rep-settings-form');

    if ($layoutBuilder.length) {
        $layoutBuilder.sortable({
            axis: 'y',
            cursor: 'move',
            placeholder: 'ui-sortable-placeholder',
            helper: 'clone',
            opacity: 0.8,
            update: function() {
                $(this).trigger('sortupdate');
            }
        });
    }

    // --- Logika ukrywania/pokazywania opcji w formularzu ---
    const mainPopupEnableCheckbox = $('#popup_enable');
    if (mainPopupEnableCheckbox.length) {
        const dependentPopupOptions = mainPopupEnableCheckbox.closest('form').find('tr').not(mainPopupEnableCheckbox.closest('tr'));
        function togglePopupOptionsVisibility() {
            const isChecked = mainPopupEnableCheckbox.is(':checked');
            dependentPopupOptions.toggle(isChecked);
            if (isChecked) {
                $('#popup_trigger_scroll_percent_enable').trigger('change');
            }
        }
        mainPopupEnableCheckbox.on('change', togglePopupOptionsVisibility).trigger('change');
    }

    const nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
    if (nestedCheckbox.length) {
        const targetRow = $('#popup_trigger_scroll_percent').closest('tr');
        function toggleNestedVisibility() {
            targetRow.toggle(nestedCheckbox.is(':checked') && mainPopupEnableCheckbox.is(':checked'));
        }
        nestedCheckbox.on('change', toggleNestedVisibility);
    }

    const limitTypeRadios = $('input[name="' + optionPrefix + '[popup_rec_excerpt_limit_type]"]');
    if (limitTypeRadios.length) {
        const wordsRow = $('#popup_rec_excerpt_length').closest('tr');
        const linesRow = $('#popup_rec_excerpt_lines').closest('tr');
        function toggleExcerptLimitFields() {
            const selectedType = limitTypeRadios.filter(':checked').val();
            wordsRow.toggle(selectedType === 'words');
            linesRow.toggle(selectedType === 'lines');
        }
        limitTypeRadios.on('change', toggleExcerptLimitFields).trigger('change');
    }

    // --- Obsługa przycisku reindeksowania ---
    $('#rep-reindex-button').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $status = $('#rep-reindex-status');
        if ($button.is('.disabled')) return;

        $button.addClass('disabled').text(REP_Admin_Settings.reindex_text_running);
        $status.html('<span class="spinner is-active" style="float:left; margin-right:5px;"></span>' + REP_Admin_Settings.reindex_text_wait).css('color', '');

        $.post(ajaxurl, { action: 'rep_reindex_posts', nonce: REP_Admin_Settings.reindex_nonce })
            .done(response => $status.text(response.success ? response.data.message : 'Błąd: ' + (response.data.message || 'Unknown error')).css('color', response.success ? 'green' : 'red'))
            .fail(() => $status.text(REP_Admin_Settings.reindex_text_error).css('color', 'red'))
            .always(() => {
                $button.removeClass('disabled').text(REP_Admin_Settings.reindex_text_default);
                $status.find('.spinner').remove();
            });
    });

    // --- Inicjalizacja pól koloru ---
    $('.wp-color-picker-field').wpColorPicker();

    // --- LOGIKA PODGLĄDU NA ŻYWO ---
    const $previewWrapper = $('#rep-live-preview-wrapper');
    if ($previewWrapper.length && $previewWrapper.is(':visible')) {
        const $previewContainer = $('#rep-intelligent-popup__container');
        const $previewList = $previewContainer.find('#rep-intelligent-popup__list');

        // --- POCZĄTEK ZMIANY ---
        // Mapa prostych pól (1 pole -> 1 zmienna CSS)
        const styleInputsMap = {
            'popup_max_width': { variable: '--rep-popup-max-width', unit: 'px' },
            'popup_max_height': { variable: '--rep-popup-max-height', unit: 'vh' },
            'popup_margin_content_bottom': { variable: '--rep-content-margin-bottom', unit: 'px' },
            'popup_gap_list_items': { variable: '--rep-list-item-gap', unit: 'px' },
            'popup_gap_grid_items': { variable: '--rep-grid-item-gap', unit: 'px' },
            'popup_rec_thumb_margin_right': { variable: '--rep-rec-thumb-margin-right', unit: 'px' }, // <-- DODANE POLE
            'popup_max_width_mobile': { variable: '--rep-popup-width-mobile', unit: 'vw' },
            'popup_padding_container_mobile': { variable: '--rep-popup-padding-mobile', unit: 'px' }
        };
        // --- KONIEC ZMIANY ---

        $.each(styleInputsMap, function(inputId, data) {
            const $input = $('#' + inputId);
            if ($input.length) {
                $input.on('input change', function() {
                    const value = $(this).val();
                    $previewContainer.css(data.variable, value + data.unit);
                }).trigger('change');
            }
        });

        const $paddingYInput = $('#popup_padding_y_desktop');
        const $paddingXInput = $('#popup_padding_x_desktop');

        function updatePreviewPadding() {
            if ($paddingYInput.length && $paddingXInput.length) {
                const paddingY = $paddingYInput.val() || '24';
                const paddingX = $paddingXInput.val() || '32';
                $previewContainer.css('--rep-popup-padding', paddingY + 'px ' + paddingX + 'px');
            }
        }
        $settingsForm.on('input change', '#popup_padding_y_desktop, #popup_padding_x_desktop', updatePreviewPadding);
        updatePreviewPadding();

        function updateButtonStyles() {
            const $buttons = $previewContainer.find('.rep-rec-button');
            const bgColor = $('input[name="' + optionPrefix + '[popup_rec_button_bg_color]"]').val();
            const textColor = $('input[name="' + optionPrefix + '[popup_rec_button_text_color]"]').val();
            const borderRadius = $('input[name="' + optionPrefix + '[popup_rec_button_border_radius]"]').val();
            $buttons.css({ 'background-color': bgColor, 'color': textColor, 'border-radius': borderRadius + 'px' });
        }
        $settingsForm.on('input wpcolorpickerchange', 'input[name*="[popup_rec_button_"]', updateButtonStyles);
        
        if($('input[name="' + optionPrefix + '[popup_rec_button_bg_color]"]').length) {
             updateButtonStyles();
        }

        $('select[name="' + optionPrefix + '[popup_recommendations_layout]"]').on('change', function() {
            $previewList.removeClass('layout-list layout-grid').addClass('layout-' + $(this).val());
        }).trigger('change');

        $('input[name="' + optionPrefix + '[popup_rec_item_layout]"]').on('change', function() {
            const layout = $(this).filter(':checked').val();
            $previewList.find('.rep-rec-item').removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
        }).filter(':checked').trigger('change');

        if ($layoutBuilder.length) {
            function updateComponentVisibilityAndOrder() {
                const $item = $previewList.find('.rep-rec-item').first();
                const $contentWrapper = $item.find('.rep-rec-content');
                const $components = {
                    'thumbnail': $item.children('.rep-rec-thumb-link'),
                    'meta': $contentWrapper.children('.rep-rec-meta'),
                    'title': $contentWrapper.children('.rep-rec-title'),
                    'excerpt': $contentWrapper.children('.rep-rec-excerpt'),
                    'link': $contentWrapper.children('.rep-rec-button')
                };
                
                $layoutBuilder.find('li').each(function() {
                    const $li = $(this);
                    const key = $li.find('input[type=hidden]').val();
                    const isVisible = $li.find('input[type=checkbox]').is(':checked');
                    
                    if ($components[key]) {
                        $components[key].toggle(isVisible);
                        if(isVisible && key !== 'thumbnail') {
                            $contentWrapper.append($components[key]);
                        }
                    }
                });
            }
            $layoutBuilder.on('sortupdate change', 'input', updateComponentVisibilityAndOrder).trigger('change');
        }

        const $countInput = $('#popup_recommendations_count');
        if($countInput.length) {
            function updatePreviewPostCount() {
                const newCount = parseInt($countInput.val(), 10) || 0;
                const $items = $previewList.children('.rep-rec-item');
                const currentCount = $items.length;

                if (newCount > currentCount) {
                    if(currentCount > 0){
                        const $template = $items.first().clone();
                        for (let i = 0; i < newCount - currentCount; i++) $previewList.append($template.clone());
                    }
                } else if (newCount < currentCount) {
                    $items.slice(newCount).remove();
                }
            }
            $countInput.on('input change', updatePreviewPostCount).trigger('change');
        }
        
        // --- POCZĄTEK NOWEGO KODU: Obsługa podglądu miniaturki ---
        const $aspectRatioSelect = $('#popup_rec_thumb_aspect_ratio');
        if ($aspectRatioSelect.length) {
            $aspectRatioSelect.on('change', function() {
                const ratio = $(this).val();
                const $thumbLinks = $previewContainer.find('.rep-rec-thumb-link');
                if (ratio === 'auto') {
                    $thumbLinks.css('aspect-ratio', '');
                } else {
                    $thumbLinks.css('aspect-ratio', ratio.replace(':', ' / '));
                }
            }).trigger('change');
        }

        const $thumbFitSelect = $('#popup_rec_thumb_fit');
        if ($thumbFitSelect.length) {
            $thumbFitSelect.on('change', function() {
                const fit = $(this).val();
                const $thumbs = $previewContainer.find('.rep-rec-thumb');
                $thumbs.removeClass('thumb-fit-cover thumb-fit-contain').addClass('thumb-fit-' + fit);
            }).trigger('change');
        }
        // --- KONIEC NOWEGO KODU ---

        $('#rep-spacing-reset-button').on('click', function(e) {
            e.preventDefault();
            // --- POCZĄTEK ZMIANY ---
            const defaultSpacings = {
                '#popup_padding_y_desktop': '24',
                '#popup_padding_x_desktop': '32',
                '#popup_margin_content_bottom': '20',
                '#popup_gap_list_items': '16',
                '#popup_gap_grid_items': '24',
                '#popup_rec_thumb_margin_right': '16' // <-- DODANE POLE
            };
            // --- KONIEC ZMIANY ---
            $.each(defaultSpacings, (selector, value) => $(selector).val(value).trigger('input'));
        });
    }
});