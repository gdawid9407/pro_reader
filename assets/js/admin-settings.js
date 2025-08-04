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
        $previewWrapper: null, // <-- NOWY
        $deviceLabel: null,
        $settingsTabs: null,
        $activeTabInput: null,
        $httpRefererInput: null, // <-- NOWY

        // Stan
        currentContext: 'general', // Kontekstem jest aktywna zakładka

        init: function() {
            this.$previewContainer = $('#rep-preview-container');
            this.$previewWrapper = $('#rep-settings-preview-wrapper'); // <-- NOWY
            if (!this.$previewContainer.length) { return; }

            this.$iframe = $('#rep-live-preview-iframe');
            this.$deviceLabel = $('#rep-preview-device-label');
            this.$settingsTabs = $('h2.nav-tab-wrapper a.nav-tab[href*="sub-tab="]'); // ZMIANA
            this.$activeTabInput = $('#rep_active_sub_tab_input');
            this.$httpRefererInput = $('input[name="_wp_http_referer"]'); // <-- NOWY

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
            
            // --- POCZĄTEK ZMIANY: Odczytanie zakładki z URL ---
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('sub-tab') || 'general';
            this.switchToTab(initialTab, true); // true, aby nie modyfikować historii przy pierwszym ładowaniu
            // --- KONIEC ZMIANY ---
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
                const url = new URL(e.currentTarget.href);
                const tabKey = url.searchParams.get('sub-tab');
                this.switchToTab(tabKey);
            });
        },
        
        switchToTab: function(tabKey, isInitialLoad = false) { // ZMIANA
            this.currentContext = tabKey;
            
            this.$settingsTabs.removeClass('nav-tab-active');
            this.$settingsTabs.filter(`[href*="sub-tab=${tabKey}"]`).addClass('nav-tab-active'); // ZMIANA
            
            $('.settings-tab-content').hide();
            $(`#reader-engagement-pro-popup-${tabKey}`).show();

            this.$activeTabInput.val(tabKey);

            // --- POCZĄTEK ZMIANY: Aktualizacja URL i _wp_http_referer ---
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('sub-tab', tabKey);

            // Aktualizuj _wp_http_referer, aby po zapisaniu wrócić do właściwej zakładki
            if (this.$httpRefererInput.length) {
                this.$httpRefererInput.val(newUrl.pathname + newUrl.search);
            }

            // Zmień URL w pasku adresu bez przeładowywania strony
            if (!isInitialLoad) {
                window.history.pushState({path: newUrl.href}, '', newUrl.href);
            }
            // --- KONIEC ZMIANY ---


            // --- POCZĄTEK ZMIANY: KONTROLA WIDOCZNOŚCI PODGLĄDU ---
            if (tabKey === 'general') {
                this.$previewWrapper.hide();
            } else {
                this.$previewWrapper.show();
            }
            // --- KONIEC ZMIANY ---
            
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
                // Domyślnie pokazuj podgląd desktopowy, ale nie pokazuj go w zakładce 'general'
                this.$previewContainer.removeClass('is-mobile').addClass('is-desktop');
                this.$deviceLabel.text(''); // Pusty label, bo podgląd jest ukryty
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
            // --- POCZĄTEK POPRAWKI ---
            // Jeśli element jest przyciskiem radio i nie jest zaznaczony, przerwij.
            // To zapobiega nadpisywaniu poprawnych ustawień przez niezaznaczone opcje
            // podczas inicjalizacji podglądu w pętli `applyStylesFromForm`.
            if ($input.is(':radio') && !$input.is(':checked')) {
                return;
            }
            // --- KONIEC POPRAWKI ---

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
        
        updateCssVar: function(selector, property, value, unit = '') { 
            this.$iframeDoc.find(selector).css(property, value + unit); 
        },
        
        updateItemLayout: function(layout) {
            const $items = this.$iframeDoc.find('.rep-rec-item');
            
            // 1. Zaktualizuj klasę główną, aby dopasować style CSS
            $items.removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
        
            // 2. Upewnij się, że struktura DOM jest poprawna dla każdego elementu
            $items.each((i, item) => {
                const $item = $(item);
                const $thumb = $item.find('.rep-rec-thumb-link');
                const $content = $item.find('.rep-rec-content');
        
                // Jeśli $thumb lub $content nie istnieją, przerwij dla tego elementu
                if (!$thumb.length || !$content.length) {
                    return; 
                }
        
                if (layout === 'horizontal') {
                    // W układzie horyzontalnym, miniaturka powinna być bezpośrednim dzieckiem .rep-rec-item
                    // i znajdować się *przed* .rep-rec-content.
                    if (!$thumb.parent().is($item)) {
                        $thumb.prependTo($item);
                    }
                } else { // layout === 'vertical'
                    // W układzie wertykalnym, miniaturka powinna być pierwszym dzieckiem .rep-rec-content.
                    if (!$thumb.parent().is($content)) {
                        $thumb.prependTo($content);
                    }
                }
            });
        },
        
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

    // --- POCZĄTEK ZMIANY: Obsługa edytora TinyMCE ---
    // Musimy poczekać, aż edytor zostanie w pełni zainicjowany.
    $(document).on('tinymce-init', function(event, editor) {
        // Sprawdzamy, czy to właściwy edytor.
        if (editor.id === 'popup_content_main_editor') {
            // Bindowanie do zdarzenia 'keyup' i 'change' w edytorze.
            editor.on('keyup change', function() {
                // Pobieramy aktualną treść z edytora.
                const newContent = editor.getContent();
                // Aktualizujemy podgląd.
                if (LivePreview.$iframeDoc) {
                    LivePreview.$iframeDoc.find('#rep-intelligent-popup__custom-content').html(newContent);
                }
            });
        }
    });
    // --- KONIEC ZMIANY ---

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