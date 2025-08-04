jQuery(function($) {
    'use strict';

    // --- 1. OBSŁUGA RĘCZNEGO RE-INDEKSOWANIA (bez zmian) ---
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

    // --- 2. PODGLĄD NA ŻYWO - WERSJA Z FUNKCJĄ SORTOWANIA ---
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

            // --- POCZĄTEK ZMIANY: WŁĄCZENIE SORTOWANIA ---
            // Inicjalizujemy jQuery UI Sortable na obu listach (dla desktopu i mobile).
            // WordPress domyślnie ładuje tę bibliotekę w panelu admina.
            $('.rep-layout-builder').sortable({
                
                axis: 'y', // Ogranicz ruch do osi Y
                update: (event, ui) => {
                    // 'update' jest wywoływane po zakończeniu przeciągania.
                    // Przekazujemy listę, która została zaktualizowana, do funkcji odświeżającej podgląd.
                    this.updateComponentOrder($(event.target));
                }
            }).disableSelection(); // Zapobiega zaznaczaniu tekstu podczas przeciągania.
            // --- KONIEC ZMIANY ---

            this.bindTabSwitcher();
            this.loadIframe();
            
            this.switchToTab('general');
        },
        
        loadIframe: function() {
            const previewUrl = REP_Admin_Settings.ajax_url + '?action=rep_live_preview';
            this.$iframe.attr('src', previewUrl);

            this.$iframe.on('load', () => {
                this.$iframeDoc = this.$iframe.contents();
                this.bindFormEvents();
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
        
        switchToTab: function(tabKey) {
            this.currentContext = tabKey;
            
            this.$settingsTabs.removeClass('nav-tab-active');
            this.$settingsTabs.filter(`[href="#reader-engagement-pro-popup-${tabKey}"]`).addClass('nav-tab-active');
            
            $('.settings-tab-content').hide();
            $(`#reader-engagement-pro-popup-${tabKey}`).show();

            this.$activeTabInput.val(tabKey);
            
            this.refreshPreview();
        },

        bindFormEvents: function() {
            const $inputs = $('#reader-engagement-pro-popup-desktop :input, #reader-engagement-pro-popup-mobile :input');
            
            // --- ZMIANA: Uproszczono bindowanie zdarzeń ---
            // 'input' dla pól tekstowych i liczbowych, 'change' dla checkboxów, radio i select
            $inputs.on('input change', (e) => this.updatePreview($(e.currentTarget)));

            // Dla selektorów kolorów używamy ich wbudowanego zdarzenia 'change'
            $('.wp-color-picker-field').wpColorPicker({
                change: (e, ui) => this.updatePreview($(e.target), ui.color.toString())
            });

            // Zdarzenie sortowania jest teraz obsługiwane w metodzie .sortable() w init()
            // Nie ma potrzeby bindować go tutaj ponownie.
        },

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
                this.$previewContainer.removeClass('is-mobile').addClass('is-desktop');
                this.$deviceLabel.text('Podgląd Desktop (ust. ogólne)');
                this.applyStylesFromForm('desktop');
            }
        },

        applyStylesFromForm: function(device) {
            const $form = $(`#reader-engagement-pro-popup-${device}`);
            $form.find(':input').each((i, el) => {
                this.updatePreview($(el));
            });
            // --- ZMIANA: Zapewnienie aktualizacji kolejności po przełączeniu zakładki ---
            this.updateComponentOrder($form.find('.rep-layout-builder'));
        },
        
        updatePreview: function($input, value = null) {
            const inputDevice = $input.closest('.settings-tab-content').attr('id').includes('desktop') ? 'desktop' : 'mobile';
            if (inputDevice !== (this.currentContext === 'general' ? 'desktop' : this.currentContext) ) {
                return;
            }

            if (!this.$iframeDoc) return;
            const name = $input.attr('name');
            value = (value !== null) ? value : $input.val();
            if (!name) return;

            const stylesMap = { 'popup_max_width': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-width', unit: 'px' }, 'popup_max_height': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-height', unit: 'vh' }, 'popup_margin_content_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-content-margin-bottom', unit: 'px' }, 'popup_gap_list_items': { selector: '#rep-intelligent-popup__container', property: '--rep-list-item-gap', unit: 'px' }, 'popup_gap_grid_items': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-gap', unit: 'px' }, 'popup_grid_item_width': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-width', unit: 'px' }, 'popup_rec_thumb_margin_right': { selector: '#rep-intelligent-popup__container', property: ['--rep-rec-thumb-margin-right', '--rep-rec-thumb-margin-bottom'], unit: 'px' }, 'popup_rec_thumb_width_horizontal': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-horizontal', unit: 'px' }, 'popup_rec_thumb_width_list_vertical': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-list-vertical', unit: '%' }, 'popup_rec_margin_meta_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-meta-margin-bottom', unit: 'px' }, 'popup_rec_margin_title_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-title-margin-bottom', unit: 'px' }, 'popup_rec_margin_excerpt_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-excerpt-margin-bottom', unit: 'px' }, 'popup_rec_button_bg_color': { selector: '.rep-rec-button', property: '--rep-btn-bg' }, 'popup_rec_button_text_color': { selector: '.rep-rec-button', property: '--rep-btn-text' }, 'popup_rec_button_bg_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-bg-hover' }, 'popup_rec_button_text_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-text-hover' }, 'popup_rec_button_border_radius': { selector: '#rep-intelligent-popup__container', property: '--rep-btn-border-radius', unit: 'px' }};
            for(const key in stylesMap) { if (name.includes(`[${key}]`)) { const { selector, property, unit } = stylesMap[key]; if (Array.isArray(property)) { property.forEach(p => this.updateCssVar(selector, p, value, unit)); } else { this.updateCssVar(selector, property, value, unit); } } }
            if (name.includes('[popup_padding_y]') || name.includes('[popup_padding_x]')) { const paddingY = $(`#popup_padding_y_${inputDevice}`).val(); const paddingX = $(`#popup_padding_x_${inputDevice}`).val(); this.updateCssVar('#rep-intelligent-popup__container', '--rep-popup-padding', `${paddingY}px ${paddingX}px`);}
            if (name.includes('[popup_recommendations_layout]')) { this.$iframeDoc.find('#rep-intelligent-popup__list').removeClass('layout-list layout-grid').addClass('layout-' + value); }
            if (name.includes('[popup_rec_item_layout]')) { this.updateItemLayout(value); }
            
            // --- ZMIANA: Obsługa widoczności jest teraz osobną funkcją dla przejrzystości ---
            if (name.includes('[popup_rec_components_visibility]')) {
                this.updateComponentVisibility($input);
            }

            if (name.includes('[popup_rec_thumb_fit]')) { this.$iframeDoc.find('.rep-rec-thumb').removeClass('thumb-fit-cover thumb-fit-contain').addClass('thumb-fit-' + value); }
            if (name.includes('[popup_rec_thumb_aspect_ratio]')) { const style = (value === 'auto') ? '' : 'aspect-ratio: ' + value.replace(':', ' / '); this.$iframeDoc.find('.rep-rec-thumb-link').attr('style', style);}
        },
        
        updateCssVar: function(selector, property, value, unit = '') { this.$iframeDoc.find(selector).css(property, value + unit); },
        updateItemLayout: function(layout) { const $items = this.$iframeDoc.find('.rep-rec-item'); $items.removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout); $items.each((i, item) => { const $item = $(item); const $thumb = $item.find('.rep-rec-thumb-link'); const $content = $item.find('.rep-rec-content'); if (layout === 'horizontal' && !$thumb.parent().is('.rep-rec-item')) { $thumb.prependTo($item); } else if (layout === 'vertical' && !$thumb.parent().is('.rep-rec-content')) { $thumb.prependTo($content); } });},
        
        // --- NOWA FUNKCJA: Aktualizuje widoczność komponentu ---
        updateComponentVisibility: function($checkbox) {
            const name = $checkbox.attr('name');
            if (!name) return;
            // Wyciągnij nazwę komponentu (np. 'thumbnail', 'title') z atrybutu name.
            const component = name.match(/\[popup_rec_components_visibility\]\[(.*?)\]/)[1];
            const isVisible = $checkbox.is(':checked');
            
            const componentMap = {
                'thumbnail': '.rep-rec-thumb-link',
                'meta': '.rep-rec-meta',
                'title': '.rep-rec-title',
                'excerpt': '.rep-rec-excerpt',
                'link': '.rep-rec-button'
            };
            
            if (componentMap[component]) {
                // Znajdź odpowiedni element w podglądzie i go pokaż/ukryj.
                this.$iframeDoc.find(componentMap[component]).toggle(isVisible);
            }
        },

        // --- NOWA FUNKCJA: Aktualizuje kolejność komponentów ---
        updateComponentOrder: function($sortableList) {
            // Pobierz nową kolejność z ukrytych pól input w posortowanej liście.
            const newOrder = $sortableList.find('input[type="hidden"]').map((i, el) => $(el).val()).get();
            if (newOrder.length === 0) return;

            const $contentContainers = this.$iframeDoc.find('.rep-rec-content');
            
            $contentContainers.each((i, container) => {
                const $container = $(container);
                
                // Przenieś każdy element na koniec kontenera w nowej, poprawnej kolejności.
                newOrder.forEach(componentKey => {
                    const componentMap = {
                        'meta': '.rep-rec-meta',
                        'title': '.rep-rec-title',
                        'excerpt': '.rep-rec-excerpt',
                        'link': '.rep-rec-button'
                    };
                    if (componentMap[componentKey]) {
                        $container.find(componentMap[componentKey]).appendTo($container);
                    }
                });
            });
        }
    };

    LivePreview.init();

    // --- Obsługa zapisywania szablonów AJAX (bez zmian) ---
    $('.rep-save-template-btn').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const templateId = $button.data('template-id');
        const deviceType = $button.attr('id').includes('desktop') ? 'desktop' : 'mobile';
        const $feedbackSpan = $('#save-template-' + templateId + '-feedback-' + deviceType);
        
        // Znajdź formularz dla odpowiedniej zakładki
        const $form = $(`#reader-engagement-pro-popup-${deviceType}`);
        const settingsString = $form.find(':input').serialize();

        $button.prop('disabled', true);
        $feedbackSpan.text('Zapisywanie...').css('color', 'black').show();

        $.post(REP_Admin_Settings.ajax_url, {
            action: 'save_popup_template',
            nonce: REP_Admin_Settings.admin_nonce,
            template_id: templateId,
            device_type: deviceType,
            settings_string: settingsString
        })
        .done(function(response) {
            if (response.success) {
                $feedbackSpan.text(response.data.message).css('color', 'green');
            } else {
                $feedbackSpan.text(response.data.message || 'Błąd zapisu.').css('color', 'red');
            }
        })
        .fail(function() {
            $feedbackSpan.text('Wystąpił błąd serwera.').css('color', 'red');
        })
        .always(function() {
            $button.prop('disabled', false);
            setTimeout(() => $feedbackSpan.fadeOut(), 4000);
        });
    });
});