jQuery(function($) {
    'use strict';

    // Sprawdź, czy obiekt z ustawieniami z PHP istnieje. Jeśli nie, zakończ, aby uniknąć błędów.
    if (typeof REP_Admin_Settings === 'undefined') {
        console.error('REP Admin Settings object not found.');
        return;
    }

    const optionPrefix = REP_Admin_Settings.option_name_attr;

    // Inicjalizacja sortowania dla konstruktora układu
    $('#rep-layout-builder').sortable({
        axis: 'y',
        cursor: 'move',
        placeholder: 'ui-sortable-placeholder',
        helper: 'clone',
        opacity: 0.8,
        update: function() {
            $(this).trigger('sortupdate');
        }
    });

    // Logika ukrywania/pokazywania opcji zależnych od głównego włącznika popupa
    const mainPopupEnableCheckbox = $('#popup_enable');
    if (mainPopupEnableCheckbox.length) {
        const dependentPopupOptions = mainPopupEnableCheckbox.closest('tr').siblings();

        function togglePopupOptionsVisibility() {
            const isChecked = mainPopupEnableCheckbox.is(':checked');
            dependentPopupOptions.toggle(isChecked);
            if (isChecked) {
                $('#popup_trigger_scroll_percent_enable').trigger('change');
            }
        }
        mainPopupEnableCheckbox.on('change', togglePopupOptionsVisibility).trigger('change');
    }

    // Logika ukrywania/pokazywania opcji zależnej od włącznika "procent przewinięcia"
    const nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
    if (nestedCheckbox.length) {
        const targetRow = $('#popup_trigger_scroll_percent').closest('tr');

        function toggleNestedVisibility() {
            targetRow.toggle(nestedCheckbox.is(':checked') && mainPopupEnableCheckbox.is(':checked'));
        }
        nestedCheckbox.on('change', toggleNestedVisibility);
    }

    // Logika przełączania pól dla limitu zajawki (słowa vs. linie)
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

    // Obsługa przycisku ręcznego reindeksowania (AJAX)
    $('#rep-reindex-button').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $status = $('#rep-reindex-status');
        if ($button.is('.disabled')) {
            return;
        }

        $button.addClass('disabled').text(REP_Admin_Settings.reindex_text_running);
        $status.html('<span class="spinner is-active" style="float:left; margin-right:5px;"></span>' + REP_Admin_Settings.reindex_text_wait).css('color', '');

        $.post(ajaxurl, {
            action: 'rep_reindex_posts',
            nonce: REP_Admin_Settings.reindex_nonce,
        }).done(function(response) {
            if (response.success) {
                $status.text(response.data.message).css('color', 'green');
            } else {
                $status.text('Błąd: ' + (response.data.message || 'Unknown error')).css('color', 'red');
            }
        }).fail(function() {
            $status.text(REP_Admin_Settings.reindex_text_error).css('color', 'red');
        }).always(function() {
            $button.removeClass('disabled').text(REP_Admin_Settings.reindex_text_default);
            $status.find('.spinner').remove();
        });
    });

    // Inicjalizacja pól wyboru koloru
    $('.wp-color-picker-field').wpColorPicker();

    // --- LOGIKA PODGLĄDU NA ŻYWO ---
    const $previewWrapper = $('#rep-live-preview-wrapper');
    if ($previewWrapper.length && $previewWrapper.is(':visible')) {
        const $previewContainer = $('#rep-intelligent-popup__container');
        const $previewContent = $previewContainer.find('#rep-intelligent-popup__custom-content');
        const $previewList = $previewContainer.find('#rep-intelligent-popup__list');

        // Aktualizacja podglądu z edytorów TinyMCE
        if (typeof tinymce !== 'undefined') {
            const contentEditor = tinymce.get('popup_content_main_editor');
            if (contentEditor) {
                contentEditor.on('keyup change', function() {
                    $previewContent.html(this.getContent());
                });
            }
            const linkEditor = tinymce.get('popup_recommendations_link_text_editor');
            if (linkEditor) {
                linkEditor.on('keyup change', function() {
                    $previewContainer.find('.rep-rec-button').html(this.getContent());
                });
            }
        }

        // Aktualizacja stylów przycisku "Czytaj dalej"
        function updateButtonStyles() {
            const $buttons = $previewContainer.find('.rep-rec-button');
            const bgColor = $('input[name="' + optionPrefix + '[popup_rec_button_bg_color]"]').val();
            const textColor = $('input[name="' + optionPrefix + '[popup_rec_button_text_color]"]').val();
            const borderRadius = $('input[name="' + optionPrefix + '[popup_rec_button_border_radius]"]').val();
            $buttons.css({
                'background-color': bgColor,
                'color': textColor,
                'border-radius': borderRadius + 'px'
            });
        }
        $('input[name*="[popup_rec_button_"]').on('input', updateButtonStyles);
        $('.wp-color-picker-field[name*="[popup_rec_button_"]').on('wpcolorpickerchange', updateButtonStyles);
        updateButtonStyles();

        // Aktualizacja ogólnego układu (lista vs siatka)
        $('select[name="' + optionPrefix + '[popup_recommendations_layout]"]').on('change', function() {
            $previewList.removeClass('layout-list layout-grid').addClass('layout-' + $(this).val());
        }).trigger('change');

        // Aktualizacja struktury pojedynczego elementu (wertykalny vs horyzontalny)
        $('input[name="' + optionPrefix + '[popup_rec_item_layout]"]').on('change', function() {
            const layout = $(this).filter(':checked').val();
            $previewList.find('.rep-rec-item').removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
        }).filter(':checked').trigger('change');

        // --- POCZĄTEK ZMIAN ---
        // Dodana obsługa aktualizacji podglądu miniaturki
        
        // Aktualizacja proporcji miniaturki (aspect-ratio)
        $('select[name="' + optionPrefix + '[popup_rec_thumb_aspect_ratio]"]').on('change', function() {
            const ratio = $(this).val();
            const cssRatio = (ratio === 'auto') ? 'auto' : ratio.replace(':', ' / ');
            $previewList.find('.rep-rec-thumb-link').css('aspect-ratio', cssRatio);
        }).trigger('change');

        // Aktualizacja dopasowania miniaturki (object-fit)
        $('select[name="' + optionPrefix + '[popup_rec_thumb_fit]"]').on('change', function() {
            const fitClass = 'thumb-fit-' + $(this).val();
            $previewList.find('.rep-rec-thumb')
                .removeClass('thumb-fit-cover thumb-fit-contain')
                .addClass(fitClass);
        }).trigger('change');

        // --- KONIEC ZMIAN ---

        // Aktualizacja widoczności i kolejności komponentów
        function updateComponentVisibilityAndOrder() {
            $previewList.find('.rep-rec-item').each(function() {
                const $item = $(this);
                const $contentWrapper = $item.find('.rep-rec-content');
                const $components = {
                    'thumbnail': $item.find('.rep-rec-thumb-link'),
                    'meta': $item.find('.rep-rec-meta'),
                    'title': $item.find('.rep-rec-title'),
                    'excerpt': $item.find('.rep-rec-excerpt'),
                    'link': $item.find('.rep-rec-button')
                };

                // Upewnij się, że miniaturka jest wewnątrz kontenera treści, aby mogła być sortowana z innymi elementami.
                if ($components.thumbnail.parent().is($item)) {
                    $contentWrapper.prepend($components.thumbnail);
                }

                Object.keys($components).forEach(key => {
                    $components[key].toggle($('#v_' + key).is(':checked'));
                });

                $('#rep-layout-builder li').each(function() {
                    const key = $(this).find('input[type=hidden]').val();
                    if ($components[key]) {
                        // Dołącz komponent do kontenera treści zgodnie z nową kolejnością
                        $contentWrapper.append($components[key]);
                    }
                });
            });
        }
        $('#rep-layout-builder').on('sortupdate change', updateComponentVisibilityAndOrder);
        updateComponentVisibilityAndOrder();

        const $excerpt = $previewList.find('.rep-rec-excerpt');
        function updateExcerptClamp() {
            if ($('input[name="' + optionPrefix + '[popup_rec_excerpt_limit_type]"]:checked').val() === 'lines') {
                $excerpt.css('-webkit-line-clamp', $('#popup_rec_excerpt_lines').val());
            } else {
                $excerpt.css('-webkit-line-clamp', 'unset');
            }
        }
        $('input[name="' + optionPrefix + '[popup_rec_excerpt_limit_type]"]').on('change', updateExcerptClamp);
        $('#popup_rec_excerpt_lines').on('input change', updateExcerptClamp);
        updateExcerptClamp();

        const $countInput = $('#popup_recommendations_count');
        function updatePreviewPostCount() {
            const newCount = parseInt($countInput.val(), 10) || 0;
            const $items = $previewList.find('.rep-rec-item');
            const currentCount = $items.length;

            if (newCount > currentCount) {
                const $template = $items.first().clone();
                for (let i = 0; i < newCount - currentCount; i++) {
                    $previewList.append($template.clone());
                }
            } else if (newCount < currentCount) {
                $items.filter(':gt(' + (newCount - 1) + ')').remove();
            }
        }
        $countInput.on('input change', updatePreviewPostCount);
        
        const styleInputs = {
            '#popup_margin_content_bottom': { variable: '--rep-content-margin-bottom', unit: 'px' },
            '#popup_gap_list_items': { variable: '--rep-list-item-gap', unit: 'px' },
            '#popup_gap_grid_items': { variable: '--rep-grid-item-gap', unit: 'px' },
            '#popup_max_width': { variable: '--rep-popup-max-width', unit: 'px' },
            '#popup_max_height': { variable: '--rep-popup-max-height', unit: 'vh' },
            '#popup_rec_thumb_margin_right': [
                { variable: '--rep-rec-thumb-margin-right', unit: 'px' },
                { variable: '--rep-rec-thumb-margin-bottom', unit: 'px' }
            ],
            '#popup_max_width_mobile': { variable: '--rep-popup-width-mobile', unit: 'vw' },
            '#popup_padding_container_mobile': { variable: '--rep-popup-padding-mobile', unit: 'px' }
        };

        function updateDesktopPadding() {
            const paddingY = $('#popup_padding_y_desktop').val() || '24';
            const paddingX = $('#popup_padding_x_desktop').val() || '32';
            $previewContainer.css('--rep-popup-padding', `${paddingY}px ${paddingX}px`);
        }

        $.each(styleInputs, function(selector, data) {
            const $input = $(selector);
            if ($input.length) {
                function updateStylePreview() {
                    const value = $input.val();
                    if (Array.isArray(data)) {
                        data.forEach(function(style) {
                            $previewContainer.css(style.variable, value + style.unit);
                        });
                    } else {
                        $previewContainer.css(data.variable, value + data.unit);
                    }
                }
                $input.on('input change', updateStylePreview);
                updateStylePreview(); 
            }
        });
        
        $('#popup_padding_y_desktop, #popup_padding_x_desktop').on('input change', updateDesktopPadding);
        updateDesktopPadding();

        $('#rep-spacing-reset-button').on('click', function(e) {
            e.preventDefault();
            const defaultSpacings = {
                '#popup_padding_y_desktop': '24',
                '#popup_padding_x_desktop': '32',
                '#popup_margin_content_bottom': '20',
                '#popup_gap_list_items': '16',
                '#popup_gap_grid_items': '24',
                '#popup_rec_thumb_margin_right': '16'
            };
            $.each(defaultSpacings, function(selector, value) {
                $(selector).val(value).trigger('change');
            });
        });
    }
});