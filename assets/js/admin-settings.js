jQuery(function($) {
    'use strict';

    // --- 1. OBSŁUGA RĘCZNEGO RE-INDEKSOWANIA (bez zmian) ---
    // ... (kod z poprzedniej wersji bez zmian)
    const $reindexButton = $('#rep-reindex-button');
    const $reindexStatus = $('#rep-reindex-status');
    if ($reindexButton.length) {
        $reindexButton.on('click', function(e) {
            e.preventDefault();
            $reindexButton.prop('disabled', true).text(REP_Admin_Settings.reindex_text_running);
            $reindexStatus.text(REP_Admin_Settings.reindex_text_wait).css('color', 'black');
            $.post(ajaxurl, { action: 'rep_reindex_posts', nonce: REP_Admin_Settings.reindex_nonce })
                .done(function(response) {
                    if (response.success) { $reindexStatus.text(response.data.message).css('color', 'green'); } 
                    else { $reindexStatus.text(response.data.message || REP_Admin_Settings.reindex_text_error).css('color', 'red'); }
                })
                .fail(function() { $reindexStatus.text(REP_Admin_Settings.reindex_text_error).css('color', 'red'); })
                .always(function() { $reindexButton.prop('disabled', false).text(REP_Admin_Settings.reindex_text_default); });
        });
    }

    // --- 2. PODGLĄD NA ŻYWO - WERSJA OSTATECZNA ---
    const LivePreview = {
        // Elementy DOM
        $iframe: null,
        $iframeDoc: null,
        $previewContainer: null,
        $deviceLabel: null,
        $settingsTabs: null,
        $activeTabInput: null,
        
        // Stan
        currentContext: 'general', // Kontekstem jest aktywna zakładka

        init: function() {
            this.$previewContainer = $('#rep-preview-container');
            if (!this.$previewContainer.length) { return; }

            this.$iframe = $('#rep-live-preview-iframe');
            this.$deviceLabel = $('#rep-preview-device-label');
            this.$settingsTabs = $('h2.nav-tab-wrapper a.nav-tab[href^="#reader-engagement-pro-popup-"]');
            this.$activeTabInput = $('#rep_active_sub_tab_input');

            this.bindTabSwitcher();
            this.loadIframe();
            
            // Pokaż domyślną zakładkę i ustaw stan początkowy
            this.switchToTab('general');
        },
        
        loadIframe: function() {
            const previewUrl = REP_Admin_Settings.ajax_url + '?action=rep_live_preview';
            this.$iframe.attr('src', previewUrl);

            this.$iframe.on('load', () => {
                this.$iframeDoc = this.$iframe.contents();
                this.bindFormEvents();
                // Po załadowaniu iframe, zastosuj style z aktywnej zakładki (początkowo 'general' nic nie robi)
                this.refreshPreview();
            });
        },
        
        bindTabSwitcher: function() {
            this.$settingsTabs.on('click', (e) => {
                e.preventDefault();
                const tabKey = $(e.currentTarget).attr('href').replace('#reader-engagement-pro-popup-', '');
                this.switchToTab(tabKey);
            });
        },
        
        // --- NOWA FUNKCJA CENTRALNA ---
        // Odpowiada za wszystkie zmiany związane z przełączeniem zakładki
        switchToTab: function(tabKey) {
            this.currentContext = tabKey;
            
            // 1. Zaktualizuj wygląd zakładek
            this.$settingsTabs.removeClass('nav-tab-active');
            this.$settingsTabs.filter(`[href="#reader-engagement-pro-popup-${tabKey}"]`).addClass('nav-tab-active');
            
            // 2. Pokaż/ukryj odpowiednie sekcje formularza
            $('.settings-tab-content').hide();
            $(`#reader-engagement-pro-popup-${tabKey}`).show();

            // 3. Zapisz aktywną zakładkę do ukrytego pola
            this.$activeTabInput.val(tabKey);
            
            // 4. Zaktualizuj widok podglądu
            this.refreshPreview();
        },

        bindFormEvents: function() {
            // Nasłuchuj na zmiany we WSZYSTKICH polach, ale logikę izolacji przenieś do updatePreview
            const $inputs = $('#reader-engagement-pro-popup-desktop :input, #reader-engagement-pro-popup-mobile :input');
            $inputs.on('input change', (e) => this.updatePreview($(e.currentTarget)));
            $('.wp-color-picker-field').wpColorPicker({ change: (e, ui) => this.updatePreview($(e.target), ui.color.toString()) });
            $('.rep-layout-builder').on('sortupdate', (e) => this.updateComponentOrder($(e.target)));
        },

        // --- GŁÓWNA FUNKCJA ODŚWIEŻAJĄCA PODGLĄD ---
        // Ustawia wygląd podglądu na podstawie AKTYWNEJ ZAKŁADKI
        refreshPreview: function() {
            if (!this.$iframeDoc) return;
            
            if (this.currentContext === 'desktop') {
                this.$previewContainer.removeClass('is-mobile').addClass('is-desktop');
                this.$deviceLabel.text('Podgląd Desktop');
                this.applyStylesFromForm('desktop');
            } else if (this.currentContext === 'mobile') {
                this.$previewContainer.removeClass('is-desktop').addClass('is-mobile');
                this.$deviceLabel.text('Podgląd Mobilny');
                this.applyStylesFromForm('mobile');
            } else {
                // Dla zakładki "general", pokaż domyślny wygląd (desktop)
                this.$previewContainer.removeClass('is-mobile').addClass('is-desktop');
                this.$deviceLabel.text('Podgląd Desktop (ust. ogólne)');
                this.applyStylesFromForm('desktop'); // Pokaż styl desktop jako bazowy
            }
        },

        // --- FUNKCJA APLIKUJĄCA STYLE Z KONKRETNEGO FORMULARZA ---
        applyStylesFromForm: function(device) {
            const $form = $(`#reader-engagement-pro-popup-${device}`);
            $form.find(':input').each((i, el) => {
                this.updatePreview($(el));
            });
            this.updateComponentOrder($form.find('.rep-layout-builder'));
        },
        
        updatePreview: function($input, value = null) {
            // --- KLUCZOWA ZMIANA ---
            // Zamiast przerywać funkcję, sprawdzamy, czy pole należy do aktywnego kontekstu.
            // Jeśli nie, to nic się nie stanie, bo pętle i warunki poniżej nie zostaną spełnione.
            const inputDevice = $input.closest('.settings-tab-content').attr('id').includes('desktop') ? 'desktop' : 'mobile';
            if (inputDevice !== (this.currentContext === 'general' ? 'desktop' : this.currentContext) ) {
                return;
            }

            // ... reszta funkcji `updatePreview` jest praktycznie bez zmian
            // ... ponieważ teraz `applyStylesFromForm` wywołuje ją tylko z polami z dobrego kontekstu
            
            if (!this.$iframeDoc) return;
            const name = $input.attr('name');
            value = (value !== null) ? value : $input.val();
            if (!name) return;

            const stylesMap = { 'popup_max_width': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-width', unit: 'px' }, 'popup_max_height': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-height', unit: 'vh' }, 'popup_margin_content_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-content-margin-bottom', unit: 'px' }, 'popup_gap_list_items': { selector: '#rep-intelligent-popup__container', property: '--rep-list-item-gap', unit: 'px' }, 'popup_gap_grid_items': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-gap', unit: 'px' }, 'popup_grid_item_width': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-width', unit: 'px' }, 'popup_rec_thumb_margin_right': { selector: '#rep-intelligent-popup__container', property: ['--rep-rec-thumb-margin-right', '--rep-rec-thumb-margin-bottom'], unit: 'px' }, 'popup_rec_thumb_width_horizontal': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-horizontal', unit: 'px' }, 'popup_rec_thumb_width_list_vertical': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-list-vertical', unit: '%' }, 'popup_rec_margin_meta_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-meta-margin-bottom', unit: 'px' }, 'popup_rec_margin_title_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-title-margin-bottom', unit: 'px' }, 'popup_rec_margin_excerpt_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-excerpt-margin-bottom', unit: 'px' }, 'popup_rec_button_bg_color': { selector: '.rep-rec-button', property: '--rep-btn-bg' }, 'popup_rec_button_text_color': { selector: '.rep-rec-button', property: '--rep-btn-text' }, 'popup_rec_button_bg_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-bg-hover' }, 'popup_rec_button_text_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-text-hover' }, 'popup_rec_button_border_radius': { selector: '#rep-intelligent-popup__container', property: '--rep-btn-border-radius', unit: 'px' }};
            for(const key in stylesMap) { if (name.includes(`[${key}]`)) { const { selector, property, unit } = stylesMap[key]; if (Array.isArray(property)) { property.forEach(p => this.updateCssVar(selector, p, value, unit)); } else { this.updateCssVar(selector, property, value, unit); } } }
            if (name.includes('[popup_padding_y]') || name.includes('[popup_padding_x]')) { const paddingY = $(`#popup_padding_y_${inputDevice}`).val(); const paddingX = $(`#popup_padding_x_${inputDevice}`).val(); this.updateCssVar('#rep-intelligent-popup__container', '--rep-popup-padding', `${paddingY}px ${paddingX}px`);}
            if (name.includes('[popup_recommendations_layout]')) { this.$iframeDoc.find('#rep-intelligent-popup__list').removeClass('layout-list layout-grid').addClass('layout-' + value); }
            if (name.includes('[popup_rec_item_layout]')) { this.updateItemLayout(value); }
            if (name.includes('[popup_rec_components_visibility]')) { this.updateComponentVisibility($input); }
            if (name.includes('[popup_rec_thumb_fit]')) { this.$iframeDoc.find('.rep-rec-thumb').removeClass('thumb-fit-cover thumb-fit-contain').addClass('thumb-fit-' + value); }
            if (name.includes('[popup_rec_thumb_aspect_ratio]')) { const style = (value === 'auto') ? '' : 'aspect-ratio: ' + value.replace(':', ' / '); this.$iframeDoc.find('.rep-rec-thumb-link').attr('style', style);}
        },
        
        // Funkcje pomocnicze bez zmian
        updateCssVar: function(selector, property, value, unit = '') { this.$iframeDoc.find(selector).css(property, value + unit); },
        updateItemLayout: function(layout) { const $items = this.$iframeDoc.find('.rep-rec-item'); $items.removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout); $items.each((i, item) => { const $item = $(item); const $thumb = $item.find('.rep-rec-thumb-link'); const $content = $item.find('.rep-rec-content'); if (layout === 'horizontal') { $thumb.prependTo($item); } else { $thumb.prependTo($content); } });},
        updateComponentVisibility: function($checkbox) { const name = $checkbox.attr('name'); const component = name.match(/\[popup_rec_components_visibility\]\[(.*?)\]/)[1]; const isVisible = $checkbox.is(':checked'); const componentMap = { 'thumbnail': '.rep-rec-thumb-link', 'meta': '.rep-rec-meta', 'title': '.rep-rec-title', 'excerpt': '.rep-rec-excerpt', 'link': '.rep-rec-button' }; if (componentMap[component]) { this.$iframeDoc.find(componentMap[component]).toggle(isVisible); }},
        updateComponentOrder: function($sortableList) { const newOrder = $sortableList.find('input[type="hidden"]').map((i, el) => $(el).val()).get(); const $contentContainers = this.$iframeDoc.find('.rep-rec-content'); $contentContainers.each((i, container) => { const $container = $(container); newOrder.forEach(componentKey => { const componentMap = { 'meta': '.rep-rec-meta', 'title': '.rep-rec-title', 'excerpt': '.rep-rec-excerpt', 'link': '.rep-rec-button' }; if(componentMap[componentKey]) { $container.find(componentMap[componentKey]).appendTo($container); } }); });}
    };

    LivePreview.init();
});