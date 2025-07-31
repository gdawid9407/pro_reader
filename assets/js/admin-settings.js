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
});