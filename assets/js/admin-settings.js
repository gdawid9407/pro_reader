jQuery(function($) {
    'use strict';

    // --- 1. OBSŁUGA RĘCZNEGO RE-INDEKSOWANIA ---
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

    // --- 2. LOGIKA POKAZYWANIA/UKRYWANIA PÓL LIMITU ZAJAWKI ---
    const ExcerptLimitToggle = {
        init: function() {
            // Znajdź wszystkie przełączniki radio dla typu limitu (dla desktop i mobile)
            this.radioButtons = $('input[name*="[popup_rec_excerpt_limit_type]"]');
            
            // Ustaw poprawną widoczność pól przy załadowaniu strony
            this.radioButtons.each((i, radioGroup) => {
                // Sprawdzamy tylko zaznaczoną opcję, aby uniknąć wielokrotnego uruchamiania
                const $checkedRadio = $(radioGroup).filter(':checked');
                if ($checkedRadio.length) {
                    this.toggleFields($checkedRadio);
                }
            });

            // Dodaj event listener do przełączania w przyszłości
            this.radioButtons.on('change', (e) => {
                this.toggleFields($(e.currentTarget));
            });
        },

        toggleFields: function($radio) {
            const selectedValue = $radio.val();
            // Określ kontekst (desktop lub mobile) na podstawie atrybutu 'name'
            const device = $radio.attr('name').includes('[desktop]') ? 'desktop' : 'mobile';

            // Znajdź wiersze tabeli (<tr>) zawierające odpowiednie pola input
            const $wordsFieldRow = $(`#popup_rec_excerpt_length_${device}`).closest('tr');
            const $linesFieldRow = $(`#popup_rec_excerpt_lines_${device}`).closest('tr');

            if (selectedValue === 'words') {
                $wordsFieldRow.show();
                $linesFieldRow.hide();
            } else if (selectedValue === 'lines') {
                $wordsFieldRow.hide();
                $linesFieldRow.show();
            }
        }
    };

    // --- 3. PODGLĄD NA ŻYWO - WERSJA Z FUNKCJĄ SORTOWANIA ---
    const LivePreview = {
        // Elementy DOM
        $iframe: null,
        $iframeDoc: null,
        $previewContainer: null,
        $previewWrapper: null,
        $deviceLabel: null,
        $settingsTabs: null,
        $activeTabInput: null,
        $httpRefererInput: null,

        // Stan
        currentContext: 'general', // Kontekstem jest aktywna zakładka

        init: function() {
            this.$previewContainer = $('#rep-preview-container');
            this.$previewWrapper = $('#rep-settings-preview-wrapper');
            if (!this.$previewContainer.length) { return; }

            this.$iframe = $('#rep-live-preview-iframe');
            this.$deviceLabel = $('#rep-preview-device-label');
            this.$settingsTabs = $('h2.nav-tab-wrapper a.nav-tab[href*="sub-tab="]');
            this.$activeTabInput = $('#rep_active_sub_tab_input');
            this.$httpRefererInput = $('input[name="_wp_http_referer"]');

            // Inicjalizujemy jQuery UI Sortable
            $('.rep-layout-builder').sortable({
                axis: 'y',
                update: (event, ui) => {
                    this.updateComponentOrder($(event.target));
                }
            }).disableSelection();

            this.bindTabSwitcher();
            this.loadIframe();
            
            const urlParams = new URLSearchParams(window.location.search);
            const initialTab = urlParams.get('sub-tab') || 'general';
            this.switchToTab(initialTab, true);
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
        
        switchToTab: function(tabKey, isInitialLoad = false) {
            this.currentContext = tabKey;
            
            this.$settingsTabs.removeClass('nav-tab-active');
            this.$settingsTabs.filter(`[href*="sub-tab=${tabKey}"]`).addClass('nav-tab-active');
            
            $('.settings-tab-content').hide();
            $(`#reader-engagement-pro-popup-${tabKey}`).show();

            this.$activeTabInput.val(tabKey);

            const newUrl = new URL(window.location);
            newUrl.searchParams.set('sub-tab', tabKey);

            if (this.$httpRefererInput.length) {
                this.$httpRefererInput.val(newUrl.pathname + newUrl.search);
            }

            if (!isInitialLoad) {
                window.history.pushState({path: newUrl.href}, '', newUrl.href);
            }

            if (tabKey === 'general') {
                this.$previewWrapper.hide();
            } else {
                this.$previewWrapper.show();
            }
            
            this.refreshPreview();
        },

        bindFormEvents: function() {
            const $inputs = $('#reader-engagement-pro-popup-desktop :input, #reader-engagement-pro-popup-mobile :input');
            
            $inputs.on('input change', (e) => this.updatePreview($(e.currentTarget)));

            $('.wp-color-picker-field').wpColorPicker({
                change: (e, ui) => this.updatePreview($(e.target), ui.color.toString())
            });
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
                this.$deviceLabel.text('');
            }
        },

        applyStylesFromForm: function(device) {
            const $form = $(`#reader-engagement-pro-popup-${device}`);
            $form.find(':input').each((i, el) => {
                this.updatePreview($(el));
            });
            this.updateComponentOrder($form.find('.rep-layout-builder'));
        },
        
        updatePreview: function($input, value = null) {
            if ($input.is(':radio') && !$input.is(':checked')) {
                return;
            }

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
            
            $items.removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
        
            $items.each((i, item) => {
                const $item = $(item);
                const $thumb = $item.find('.rep-rec-thumb-link');
                const $content = $item.find('.rep-rec-content');
        
                if (!$thumb.length || !$content.length) {
                    return; 
                }
        
                if (layout === 'horizontal') {
                    if (!$thumb.parent().is($item)) {
                        $thumb.prependTo($item);
                    }
                } else { // layout === 'vertical'
                    if (!$thumb.parent().is($content)) {
                        $thumb.prependTo($content);
                    }
                }
            });
        },
        
        updateComponentVisibility: function($checkbox) {
            const name = $checkbox.attr('name');
            if (!name) return;
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

        updateComponentOrder: function($sortableList) {
            const newOrder = $sortableList.find('input[type="hidden"]').map((i, el) => $(el).val()).get();
            if (newOrder.length === 0) return;

            const $contentContainers = this.$iframeDoc.find('.rep-rec-content');
            
            $contentContainers.each((i, container) => {
                const $container = $(container);
                
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

    // --- 4. OBSŁUGA EDYTORA TinyMCE ---
    $(document).on('tinymce-init', function(event, editor) {
        if (editor.id === 'popup_content_main_editor') {
            editor.on('keyup change', function() {
                const newContent = editor.getContent();
                if (LivePreview.$iframeDoc) {
                    LivePreview.$iframeDoc.find('#rep-intelligent-popup__custom-content').html(newContent);
                }
            });
        }
    });

    // --- 5. INICJALIZACJA WSZYSTKICH MODUŁÓW ---
    ExcerptLimitToggle.init();
    LivePreview.init();

    // --- 6. OBSŁUGA ZAPISYWANIA SZABLONÓW AJAX ---
    $('.rep-save-template-btn').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const templateId = $button.data('template-id');
        const deviceType = $button.attr('id').includes('desktop') ? 'desktop' : 'mobile';
        const $feedbackSpan = $('#save-template-' + templateId + '-feedback-' + deviceType);
        
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
            $feedbackSpan.text('Błąd serwera.').css('color', 'red');
        })
        .always(function() {
            $button.prop('disabled', false);
            setTimeout(() => $feedbackSpan.fadeOut(), 4000);
        });
    });
});