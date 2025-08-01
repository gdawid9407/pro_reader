jQuery(function($) {
    'use strict';

    if (typeof REP_Admin_Settings === 'undefined') {
        console.error('REP Admin Settings object not found.');
        return;
    }

    const optionPrefix = REP_Admin_Settings.option_name_attr;

    // Inicjalizacja sortowania dla obu konstruktorów układu
    $('.rep-layout-builder').sortable({
        axis: 'y',
        cursor: 'move',
        placeholder: 'ui-sortable-placeholder',
        helper: 'clone',
        opacity: 0.8,
        update: function() {
            $(this).trigger('sortupdate');
        }
    });

    // --- Logika zakładek ---
    const tabs = $('.nav-tab-wrapper .nav-tab[href^="#"]');
    const tabContents = $('.settings-tab-content');
    const activeTabInput = $('#rep_active_sub_tab_input');

    tabs.on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).attr('href');
        const target = $(targetId);

        tabs.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        tabContents.hide();
        target.show();

        // Zaktualizuj ukryte pole, aby serwer wiedział, co zapisuje
        const tabName = targetId.replace('#reader-engagement-pro-popup-', '');
        activeTabInput.val(tabName);

        // Zapisz aktywną zakładkę
        localStorage.setItem('repActiveSubTab', targetId);
    });

    // Przywróć ostatnio aktywną zakładkę
    const activeSubTab = localStorage.getItem('repActiveSubTab');
    if (activeSubTab && $(activeSubTab).length) {
        tabs.filter('[href="' + activeSubTab + '"]').click();
    } else {
        tabs.first().click();
    }


    // --- Logika pól zależnych (globalne) ---
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

    const nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
    if (nestedCheckbox.length) {
        const targetRow = $('#popup_trigger_scroll_percent').closest('tr');

        function toggleNestedVisibility() {
            targetRow.toggle(nestedCheckbox.is(':checked') && mainPopupEnableCheckbox.is(':checked'));
        }
        nestedCheckbox.on('change', toggleNestedVisibility);
    }

    // --- Logika dla obu zakładek (Desktop i Mobile) ---
    ['desktop', 'mobile'].forEach(device => {
        const $tab = $('#reader-engagement-pro-popup-' + device);
        if (!$tab.length) return;

        // Logika przełączania pól dla limitu zajawki
        const limitTypeRadios = $tab.find('input[name*="[popup_rec_excerpt_limit_type]"]');
        if (limitTypeRadios.length) {
            const wordsRow = $tab.find('#popup_rec_excerpt_length_' + device).closest('tr');
            const linesRow = $tab.find('#popup_rec_excerpt_lines_' + device).closest('tr');

            function toggleExcerptLimitFields() {
                const selectedType = limitTypeRadios.filter(':checked').val();
                wordsRow.toggle(selectedType === 'words');
                linesRow.toggle(selectedType === 'lines');
            }
            limitTypeRadios.on('change', toggleExcerptLimitFields).trigger('change');
        }
    });


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

    // --- PRZYCISKI RESETOWANIA ---
    $('#rep-spacing-reset-button-desktop').on('click', function(e) {
        e.preventDefault();
        const defaults = {
            '#popup_padding_y_desktop': '24',
            '#popup_padding_x_desktop': '40',
            '#popup_margin_content_bottom_desktop': '20',
            '#popup_gap_list_items_desktop': '50',
            '#popup_gap_grid_items_desktop': '45',
            '#popup_grid_item_width_desktop': '234',
            '#popup_rec_thumb_margin_right_desktop': '25',
            '#popup_rec_thumb_width_horizontal_desktop': '200',
            '#popup_rec_thumb_width_list_vertical_desktop': '100',
            '#popup_rec_margin_meta_bottom_desktop': '8',
            '#popup_rec_margin_title_bottom_desktop': '12',
            '#popup_rec_margin_excerpt_bottom_desktop': '12',
            '#popup_max_width_desktop': '670',
            '#popup_max_height_desktop': '81'
        };
        $.each(defaults, (selector, value) => $(selector).val(value).trigger('change'));
        $('#popup_recommendations_layout_desktop').val('grid').trigger('change');
        $('input[name*="[desktop][popup_rec_item_layout]"][value="vertical"]').prop('checked', true).trigger('change');
    });

    $('#rep-spacing-reset-button-mobile').on('click', function(e) {
        e.preventDefault();
        const defaults = {
            '#popup_padding_y_mobile': '20',
            '#popup_padding_x_mobile': '20',
            '#popup_margin_content_bottom_mobile': '15',
            '#popup_gap_list_items_mobile': '30',
            '#popup_gap_grid_items_mobile': '20',
            '#popup_grid_item_width_mobile': '150',
            '#popup_rec_thumb_margin_right_mobile': '15',
            '#popup_rec_thumb_width_horizontal_mobile': '120',
            '#popup_rec_thumb_width_list_vertical_mobile': '100',
            '#popup_rec_margin_meta_bottom_mobile': '5',
            '#popup_rec_margin_title_bottom_mobile': '8',
            '#popup_rec_margin_excerpt_bottom_mobile': '8',
            '#popup_max_width_mobile': '360',
            '#popup_max_height_mobile': '85'
        };
        $.each(defaults, (selector, value) => $(selector).val(value).trigger('change'));
        $('#popup_recommendations_layout_mobile').val('list').trigger('change');
        $('input[name*="[mobile][popup_rec_item_layout]"][value="horizontal"]').prop('checked', true).trigger('change');
    });


    // Logika zapisywania szablonów
    $('.rep-save-template-btn').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const templateId = $button.data('template-id');
        const device = $button.closest('.settings-tab-content').attr('id').includes('mobile') ? 'mobile' : 'desktop';
        const $feedback = $('#save-template-' + templateId + '-feedback-' + device);
        const $form = $button.closest('form');
        const settingsString = $form.serialize();

        $button.prop('disabled', true);
        $feedback.html('<span class="spinner is-active" style="float:left; margin-right:5px;"></span>Zapisywanie...').css('color', '').show();

        $.post(ajaxurl, {
            action: 'save_popup_template',
            nonce: REP_Admin_Settings.admin_nonce,
            template_id: templateId,
            settings_string: settingsString,
            device_type: device
        }).done(function(response) {
            if (response.success) {
                $feedback.text(response.data.message).css('color', 'green');
            } else {
                $feedback.text('Błąd: ' + (response.data.message || 'Unknown error')).css('color', 'red');
            }
        }).fail(function() {
            $feedback.text('Błąd komunikacji z serwerem.').css('color', 'red');
        }).always(function() {
            $button.prop('disabled', false);
            $feedback.find('.spinner').remove();
            setTimeout(function() {
                $feedback.fadeOut();
            }, 5000);
        });
    });
});