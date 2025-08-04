jQuery(function($) {
    'use strict';

    // --- 1. OBSŁUGA ZAKŁADEK W PANELU USTAWIEŃ ---
    const $subTabLinks = $('h2.nav-tab-wrapper a.nav-tab[href^="#"]');
    const $subTabContents = $('.settings-tab-content');
    const $activeSubTabInput = $('#rep_active_sub_tab_input');

    $subTabLinks.on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).attr('href');

        $subTabLinks.removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $subTabContents.hide();
        $(tabId).show();

        // Zapisz aktywną zakładkę w ukrytym polu, aby formularz wiedział, co sanitować
        const tabKey = tabId.replace('#reader-engagement-pro-popup-', '');
        $activeSubTabInput.val(tabKey);
    });

    // Pokaż domyślną zakładkę (general)
    $subTabLinks.filter('[href="#reader-engagement-pro-popup-general"]').addClass('nav-tab-active');
    $('#reader-engagement-pro-popup-general').show();


    // --- 2. OBSŁUGA RĘCZNEGO RE-INDEKSOWANIA ---
    const $reindexButton = $('#rep-reindex-button');
    const $reindexStatus = $('#rep-reindex-status');

    if ($reindexButton.length) {
        $reindexButton.on('click', function(e) {
            e.preventDefault();
            $reindexButton.prop('disabled', true).text(REP_Admin_Settings.reindex_text_running);
            $reindexStatus.text(REP_Admin_Settings.reindex_text_wait).css('color', 'black');

            $.post(ajaxurl, {
                action: 'rep_reindex_posts',
                nonce: REP_Admin_Settings.reindex_nonce
            })
            .done(function(response) {
                if (response.success) {
                    $reindexStatus.text(response.data.message).css('color', 'green');
                } else {
                    $reindexStatus.text(response.data.message || REP_Admin_Settings.reindex_text_error).css('color', 'red');
                }
            })
            .fail(function() {
                $reindexStatus.text(REP_Admin_Settings.reindex_text_error).css('color', 'red');
            })
            .always(function() {
                $reindexButton.prop('disabled', false).text(REP_Admin_Settings.reindex_text_default);
            });
        });
    }


    // --- 3. PODGLĄD NA ŻYWO (LIVE PREVIEW) ---
    const LivePreview = {
        // Elementy DOM
        $iframe: null,
        $iframeDoc: null,
        $previewContainer: null,
        $deviceButtons: null,
        $formDesktop: null,
        $formMobile: null,

        /**
         * Inicjalizacja podglądu
         */
        init: function() {
            this.$previewContainer = $('#rep-preview-container');
            if (!this.$previewContainer.length) {
                return; // Zakończ, jeśli nie ma kontenera podglądu na stronie
            }

            this.$iframe = $('#rep-live-preview-iframe');
            this.$deviceButtons = $('#rep-preview-controls button');
            this.$formDesktop = $('#reader-engagement-pro-popup-desktop');
            this.$formMobile = $('#reader-engagement-pro-popup-mobile');

            this.loadIframe();
            this.bindDeviceSwitcher();
        },

        /**
         * Ładuje zawartość do iframe'a i po załadowaniu bindowuje zdarzenia
         */
        loadIframe: function() {
            const previewUrl = REP_Admin_Settings.ajax_url + '?action=rep_live_preview';
            this.$iframe.attr('src', previewUrl);

            // Musimy poczekać, aż iframe się w pełni załaduje
            this.$iframe.on('load', () => {
                this.$iframeDoc = this.$iframe.contents();
                this.bindFormEvents();
                // Odśwież podgląd na start, aby pobrać zapisane wartości
                this.refreshAll(); 
            });
        },
        
        /**
         * Bindowanie przełącznika Desktop/Mobile
         */
        bindDeviceSwitcher: function() {
            this.$deviceButtons.on('click', (e) => {
                const $button = $(e.currentTarget);
                const device = $button.data('device');

                this.$deviceButtons.removeClass('button-primary').addClass('button-secondary');
                $button.removeClass('button-secondary').addClass('button-primary');

                this.$previewContainer.removeClass('is-desktop is-mobile').addClass('is-' + device);
            });
        },

        /**
         * Bindowanie zdarzeń do wszystkich pól formularza
         */
        bindFormEvents: function() {
            const $inputs = this.$formDesktop.find(':input').add(this.$formMobile.find(':input'));
            
            $inputs.on('input change', (e) => {
                const $input = $(e.currentTarget);
                this.updatePreview($input);
            });

            // Specjalna obsługa dla Color Picker
            $('.wp-color-picker-field').wpColorPicker({
                change: (event, ui) => {
                    this.updatePreview($(event.target), ui.color.toString());
                }
            });

            // Specjalna obsługa dla Sortable (przeciąganie)
            $('.rep-layout-builder').on('sortupdate', (e, ui) => {
                this.updateComponentOrder($(e.target));
            });
        },
        
        /**
         * Główna funkcja aktualizująca, wywoływana po zmianie w formularzu
         */
        updatePreview: function($input, value = null) {
            if (!this.$iframeDoc) return;

            const name = $input.attr('name');
            value = (value !== null) ? value : $input.val();
            
            if (!name) return;

            // --- Aktualizacje stylów za pomocą zmiennych CSS ---
            const stylesMap = {
                'popup_max_width': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-width', unit: 'px' },
                'popup_max_height': { selector: '#rep-intelligent-popup__container', property: '--rep-popup-max-height', unit: 'vh' },
                'popup_margin_content_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-content-margin-bottom', unit: 'px' },
                'popup_gap_list_items': { selector: '#rep-intelligent-popup__container', property: '--rep-list-item-gap', unit: 'px' },
                'popup_gap_grid_items': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-gap', unit: 'px' },
                'popup_grid_item_width': { selector: '#rep-intelligent-popup__container', property: '--rep-grid-item-width', unit: 'px' },
                'popup_rec_thumb_margin_right': { selector: '#rep-intelligent-popup__container', property: ['--rep-rec-thumb-margin-right', '--rep-rec-thumb-margin-bottom'], unit: 'px' },
                'popup_rec_thumb_width_horizontal': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-horizontal', unit: 'px' },
                'popup_rec_thumb_width_list_vertical': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-thumb-width-list-vertical', unit: '%' },
                'popup_rec_margin_meta_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-meta-margin-bottom', unit: 'px' },
                'popup_rec_margin_title_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-title-margin-bottom', unit: 'px' },
                'popup_rec_margin_excerpt_bottom': { selector: '#rep-intelligent-popup__container', property: '--rep-rec-excerpt-margin-bottom', unit: 'px' },
                'popup_rec_button_bg_color': { selector: '.rep-rec-button', property: '--rep-btn-bg' },
                'popup_rec_button_text_color': { selector: '.rep-rec-button', property: '--rep-btn-text' },
                'popup_rec_button_bg_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-bg-hover' },
                'popup_rec_button_text_hover_color': { selector: '.rep-rec-button', property: '--rep-btn-text-hover' },
                'popup_rec_button_border_radius': { selector: '#rep-intelligent-popup__container', property: '--rep-btn-border-radius', unit: 'px' },
            };

            for(const key in stylesMap) {
                if (name.includes(`[${key}]`)) {
                    const { selector, property, unit } = stylesMap[key];
                    if (Array.isArray(property)) {
                        property.forEach(p => this.updateCssVar(selector, p, value, unit));
                    } else {
                        this.updateCssVar(selector, property, value, unit);
                    }
                }
            }

            // --- Aktualizacje paddingu (wymaga złożenia dwóch wartości) ---
            if (name.includes('[popup_padding_y]') || name.includes('[popup_padding_x]')) {
                const device = name.includes('[desktop]') ? 'desktop' : 'mobile';
                const paddingY = $(`#popup_padding_y_${device}`).val();
                const paddingX = $(`#popup_padding_x_${device}`).val();
                this.updateCssVar('#rep-intelligent-popup__container', '--rep-popup-padding', `${paddingY}px ${paddingX}px`);
            }
            
            // --- Inne aktualizacje (klasy, atrybuty, treść) ---
            
            // Układ ogólny (Lista/Siatka)
            if (name.includes('[popup_recommendations_layout]')) {
                this.$iframeDoc.find('#rep-intelligent-popup__list').removeClass('layout-list layout-grid').addClass('layout-' + value);
            }

            // Struktura elementu (Horyzontalna/Wertykalna)
            if (name.includes('[popup_rec_item_layout]')) {
                this.updateItemLayout(value);
            }

            // Kolejność i widoczność komponentów
            if (name.includes('[popup_rec_components_visibility]')) {
                this.updateComponentVisibility($input);
            }

            // Dopasowanie i proporcje miniaturki
            if (name.includes('[popup_rec_thumb_fit]')) {
                 this.$iframeDoc.find('.rep-rec-thumb').removeClass('thumb-fit-cover thumb-fit-contain').addClass('thumb-fit-' + value);
            }
            if (name.includes('[popup_rec_thumb_aspect_ratio]')) {
                const style = (value === 'auto') ? '' : 'aspect-ratio: ' + value.replace(':', ' / ');
                this.$iframeDoc.find('.rep-rec-thumb-link').attr('style', style);
            }
        },

        /**
         * Helper do zmiany zmiennych CSS wewnątrz iframe
         */
        updateCssVar: function(selector, property, value, unit = '') {
            this.$iframeDoc.find(selector).css(property, value + unit);
        },

        /**
         * Zmiana układu poszczególnych elementów
         */
        updateItemLayout: function(layout) {
            const $items = this.$iframeDoc.find('.rep-rec-item');
            $items.removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
            
            // Przenosimy elementy HTML dla układu horyzontalnego
            $items.each((i, item) => {
                const $item = $(item);
                const $thumb = $item.find('.rep-rec-thumb-link');
                const $content = $item.find('.rep-rec-content');
                if (layout === 'horizontal') {
                    $thumb.prependTo($item); // Miniaturka jest pierwszym dzieckiem <li>
                } else {
                    $thumb.prependTo($content); // Miniaturka jest pierwszym dzieckiem .rep-rec-content
                }
            });
        },

        /**
         * Zmiana widoczności komponentów
         */
        updateComponentVisibility: function($checkbox) {
            const name = $checkbox.attr('name');
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
                 this.$iframeDoc.find(componentMap[component]).toggle(isVisible);
            }
        },

        /**
         * Zmiana kolejności komponentów
         */
        updateComponentOrder: function($sortableList) {
            const newOrder = $sortableList.find('input[type="hidden"]').map((i, el) => $(el).val()).get();
            const $contentContainers = this.$iframeDoc.find('.rep-rec-content');

            $contentContainers.each((i, container) => {
                const $container = $(container);
                newOrder.forEach(componentKey => {
                    const componentMap = { 'meta': '.rep-rec-meta', 'title': '.rep-rec-title', 'excerpt': '.rep-rec-excerpt', 'link': '.rep-rec-button' };
                    if(componentMap[componentKey]) {
                        $container.find(componentMap[componentKey]).appendTo($container);
                    }
                });
            });
        },
        
        /**
         * Odświeża cały podgląd na podstawie aktualnych wartości w formularzu
         */
        refreshAll: function() {
            const activeTabId = $subTabLinks.filter('.nav-tab-active').attr('href');
            $(activeTabId).find(':input').each((i, el) => {
                this.updatePreview($(el));
            });
        }
    };

    // Inicjalizuj podgląd na żywo, gdy strona jest gotowa
    LivePreview.init();

    // Dodatkowa obsługa przełączania zakładek Wyglądu
    $('a.nav-tab[href="#reader-engagement-pro-popup-desktop"], a.nav-tab[href="#reader-engagement-pro-popup-mobile"]').on('click', function() {
        // Po kliknięciu na zakładkę wyglądu, odśwież podgląd
        setTimeout(() => LivePreview.refreshAll(), 10);
    });

});