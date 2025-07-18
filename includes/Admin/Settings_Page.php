<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup;

class Settings_Page
{
    private const SETTINGS_GROUP = 'reader_engagement_pro_group';
    private const OPTION_NAME = 'reader_engagement_pro_options';
    
    private Settings_Progress_Bar $progress_bar_settings;
    private Settings_Popup $popup_settings;

    public function __construct()
    {
        $this->progress_bar_settings = new Settings_Progress_Bar();
        $this->popup_settings        = new Settings_Popup();
        
        add_action('admin_init', [$this, 'page_init']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('wp_ajax_rep_reindex_posts', [$this, 'handle_reindex_ajax']);
    }

    public function page_init(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'route_sanitize_callback']
            ]
        );

        add_settings_section(
            'popup_reindex_section', 
            __('Narzędzia Indeksowania', 'pro_reader'), 
            null, 
            'reader-engagement-pro-popup'
        );
        add_settings_field(
            'popup_reindex_button', 
            __('Ręczne Indeksowanie', 'pro_reader'), 
            [$this, 'reindex_button_callback'], 
            'reader-engagement-pro-popup', 
            'popup_reindex_section'
        );
    }

    public function add_plugin_page(): void
    {
        add_menu_page(
            'Pasek czytania',
            'Pasek czytania',
            'manage_options',
            'reader-engagement-pro',
            [$this, 'create_admin_page'],
            'dashicons-performance',
            81
        );
    }

    public function route_sanitize_callback(array $input): array
    {
        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }

        if (isset($input['popup_trigger_time']) || isset($input['popup_rec_item_layout'])) {
            return $this->popup_settings->sanitize($input);
        }

        return get_option(self::OPTION_NAME, []);
    }

    public function create_admin_page(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'progress_bar';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ustawienia wtyczki Reader Engagement Pro', 'pro_reader'); ?></h1>
            <p><?php esc_html_e('Zarządzaj ustawieniami dla poszczególnych modułów wtyczki.', 'pro_reader'); ?></p>

            <h2 class="nav-tab-wrapper">
                <a href="?page=reader-engagement-pro&tab=progress_bar" class="nav-tab <?php echo $active_tab == 'progress_bar' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Pasek Postępu', 'pro_reader'); ?>
                </a>
                <a href="?page=reader-engagement-pro&tab=popup" class="nav-tab <?php echo $active_tab == 'popup' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Popup "Czytaj Więcej"', 'pro_reader'); ?>
                </a>
            </h2>

            <div id="rep-settings-container" style="display: flex; flex-wrap: wrap; gap: 40px; margin-top: 20px;">
                
                <div id="rep-settings-form" style="flex: 1; min-width: 500px; max-width: 750px;">
                    <form method="post" action="options.php">
                        <?php
                        if ($active_tab === 'progress_bar') {
                            settings_fields(self::SETTINGS_GROUP);
                            do_settings_sections('reader-engagement-pro-progress-bar');
                            submit_button();
                        } elseif ($active_tab === 'popup') {
                            settings_fields(self::SETTINGS_GROUP);
                            do_settings_sections('reader-engagement-pro-popup');
                            submit_button();
                        }
                        ?>
                    </form>
                </div>

                <?php if ($active_tab === 'popup') : ?>
                <div id="rep-live-preview-wrapper" style="flex: 1; min-width: 400px;">
                    <div style="position: sticky; top: 50px;">
                        <h3 style="margin: 0 0 10px; padding: 0;"><?php esc_html_e('Podgląd na żywo', 'pro_reader'); ?></h3>
                        <div id="rep-live-preview-area" style="transform: scale(0.5); transform-origin: top left; width: 200%; height: 200%; pointer-events: none; background: #f0f0f1; padding: 20px; border-radius: 4px;">
                            <?php echo $this->render_preview_placeholder(); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
    }
    
    private function render_preview_placeholder(): string
    {
        $options = get_option(self::OPTION_NAME, []);
    
        // === POCZĄTEK POPRAWKI: Odczytanie liczby postów z opcji ===
        $posts_count = (int) ($options['popup_recommendations_count'] ?? 3);
        // === KONIEC POPRAWKI ===

        $popup_content  = $options['popup_content_main'] ?? '<h3>Spodobał Ci się ten artykuł?</h3><p>Czytaj dalej i odkryj więcej ciekawych treści, które dla Ciebie przygotowaliśmy!</p>';
        $layout_setting = $options['popup_recommendations_layout'] ?? 'list';
        $layout_class   = 'layout-' . sanitize_html_class($layout_setting);
        $item_layout    = $options['popup_rec_item_layout'] ?? 'vertical';
        $item_class     = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
        $link_text      = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

        $bg_color        = $options['popup_rec_button_bg_color'] ?? '#0073aa';
        $text_color      = $options['popup_rec_button_text_color'] ?? '#ffffff';
        $border_radius   = $options['popup_rec_button_border_radius'] ?? 4;
        $button_style = sprintf('background-color: %s; color: %s; border-radius: %dpx;', esc_attr($bg_color), esc_attr($text_color), esc_attr($border_radius));

        ob_start();
        ?>
        <div id="rep-intelligent-popup__overlay-preview" class="is-visible" style="position: absolute; opacity: 0.1; top:0; left:0; right:0; bottom:0; z-index: -1;"></div>
        <div id="rep-intelligent-popup__container" class="is-visible" style="position: relative; top: auto; left: auto; transform: none; max-width: 800px; z-index: 1;">
            <header id="rep-intelligent-popup__header">
                <h2 id="rep-intelligent-popup__title-static" class="screen-reader-text">Rekomendowane treści</h2>
                <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
            </header>
            
            <div id="rep-intelligent-popup__custom-content">
                <?php echo wp_kses_post($popup_content); ?>
            </div>
    
            <ul id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
                <?php // === POCZĄTEK POPRAWKI: Użycie zmiennej w pętli ===
                for ($i = 0; $i < $posts_count; $i++): 
                // === KONIEC POPRAWKI === ?>
                <li class="<?php echo esc_attr($item_class); ?>">
                    <a href="#" onclick="return false;" class="rep-rec-thumb-link" style="aspect-ratio: 16 / 9;">
                        <img src="<?php echo esc_url(REP_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="placeholder" class="rep-rec-thumb thumb-fit-cover">
                    </a>
                    <div class="rep-rec-content">
                        <p class="rep-rec-meta"><span class="rep-rec-date">1 Styczeń, 2025</span> <span class="rep-rec-meta-separator">•</span> <span class="rep-rec-category">Kategoria</span></p>
                        <h3 class="rep-rec-title"><a href="#" onclick="return false;">Przykładowy Tytuł Rekomendacji</a></h3>
                        <p class="rep-rec-excerpt">To jest przykład zajawki artykułu, aby pokazać jak będzie wyglądać w popupie i jak tekst może się zawijać.</p>
                        <a href="#" onclick="return false;" class="rep-rec-button" style="<?php echo esc_attr($button_style); ?>"><?php echo wp_kses_post($link_text); ?></a>
                    </div>
                </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    public function reindex_button_callback(): void
    {
        echo '<button type="button" id="rep-reindex-button" class="button button-secondary">' . esc_html__('Uruchom pełne indeksowanie', 'pro_reader') . '</button>';
        echo '<p class="description">' . esc_html__('Kliknij, aby przeskanować wszystkie opublikowane wpisy i zbudować bazę linków dla rekomendacji. Może to zająć chwilę.', 'pro_reader') . '</p>';
        echo '<div id="rep-reindex-status" style="margin-top: 10px;"></div>';
    }

    public function handle_reindex_ajax(): void
    {
        check_ajax_referer('rep_reindex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Brak uprawnień.'], 403);
        }

        if (!class_exists('REP_Link_Indexer')) {
            wp_send_json_error(['message' => 'Krytyczny błąd: Klasa REP_Link_Indexer nie została znaleziona.'], 500);
        }
        
        $indexer = new \REP_Link_Indexer();
        $args = [ 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ];
        $post_ids = get_posts($args);
        
        foreach ($post_ids as $post_id) {
            $indexer->index_post($post_id);
        }
        
        $count = count($post_ids);
        wp_send_json_success(['message' => "Pomyślnie zindeksowano {$count} wpisów."]);
    }

    public function enqueue_admin_assets($hook): void
    {
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }

        wp_enqueue_style('rep-popup-style-preview', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.6');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        $inline_css = "
            #rep-layout-builder .ui-sortable-placeholder { border: 2px dashed #ccd0d4; background: #f6f7f7; height: 40px; margin-bottom: 5px; visibility: visible !important; }
            #rep-layout-builder .ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95; }
        ";
        wp_add_inline_style('wp-admin', $inline_css);

        $option_name_attr = esc_js(self::OPTION_NAME);
        $reindex_nonce = wp_create_nonce('rep_reindex_nonce');
        $reindex_text_default = esc_js(__('Uruchom pełne indeksowanie', 'pro_reader'));
        $reindex_text_running = esc_js(__('Indeksowanie...', 'pro_reader'));
        $reindex_text_wait = esc_js(__('Proszę czekać, to może potrwać kilka minut.', 'pro_reader'));
        $reindex_text_error = esc_js(__('Wystąpił nieoczekiwany błąd serwera.', 'pro_reader'));

        $custom_js = <<<JS
        jQuery(document).ready(function($) {
            
            const optionPrefix = '{$option_name_attr}';

            $('#rep-layout-builder').sortable({
                axis: 'y', cursor: 'move', placeholder: 'ui-sortable-placeholder',
                helper: 'clone', opacity: 0.8,
                update: function() { $(this).trigger('sortupdate'); }
            });

            const mainPopupEnableCheckbox = $('#popup_enable');
            if (mainPopupEnableCheckbox.length) {
                const dependentPopupOptions = mainPopupEnableCheckbox.closest('tr').siblings();
                function togglePopupOptionsVisibility() {
                    dependentPopupOptions.toggle(mainPopupEnableCheckbox.is(':checked'));
                    if(mainPopupEnableCheckbox.is(':checked')) $('#popup_trigger_scroll_percent_enable').trigger('change');
                }
                mainPopupEnableCheckbox.on('change', togglePopupOptionsVisibility).trigger('change');
            }

            const nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
            if(nestedCheckbox.length) {
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

            $('#rep-reindex-button').on('click', function(e) {
                e.preventDefault();
                const \$button = $(this);
                const \$status = $('#rep-reindex-status');
                if (\$button.is('.disabled')) return;

                \$button.addClass('disabled').text('{$reindex_text_running}');
                \$status.html('<span class="spinner is-active" style="float:left; margin-right:5px;"></span>{$reindex_text_wait}').css('color', '');
                
                $.post(ajaxurl, {
                    action: 'rep_reindex_posts',
                    nonce: '{$reindex_nonce}'
                }).done(function(response) {
                    if (response.success) \$status.text(response.data.message).css('color', 'green');
                    else \$status.text('Błąd: ' + (response.data.message || 'Unknown error')).css('color', 'red');
                }).fail(function() {
                    \$status.text('{$reindex_text_error}').css('color', 'red');
                }).always(function() {
                    \$button.removeClass('disabled').text('{$reindex_text_default}');
                    \$status.find('.spinner').remove();
                });
            });

            $('.wp-color-picker-field').wpColorPicker();

            // --- POCZĄTEK: LOGIKA PODGLĄDU NA ŻYWO ---
            const \$previewWrapper = $('#rep-live-preview-wrapper');
            if (\$previewWrapper.length && \$previewWrapper.is(':visible')) {
                const \$previewContainer = $('#rep-intelligent-popup__container');
                const \$previewContent = \$previewContainer.find('#rep-intelligent-popup__custom-content');
                const \$previewList = \$previewContainer.find('#rep-intelligent-popup__list');

                if (typeof tinymce !== 'undefined') {
                    const contentEditor = tinymce.get('popup_content_main_editor');
                    if (contentEditor) {
                        contentEditor.on('keyup change', function() { \$previewContent.html(this.getContent()); });
                    }
                    const linkEditor = tinymce.get('popup_recommendations_link_text_editor');
                    if(linkEditor){
                         linkEditor.on('keyup change', function() { \$previewContainer.find('.rep-rec-button').html(this.getContent()); });
                    }
                }

                function updateButtonStyles() {
                    const \$buttons = \$previewContainer.find('.rep-rec-button');
                    const bgColor = \$('input[name="' + optionPrefix + '[popup_rec_button_bg_color]"]').val();
                    const textColor = \$('input[name="' + optionPrefix + '[popup_rec_button_text_color]"]').val();
                    const borderRadius = \$('input[name="' + optionPrefix + '[popup_rec_button_border_radius]"]').val();
                    \$buttons.css({ 'background-color': bgColor, 'color': textColor, 'border-radius': borderRadius + 'px' });
                }
                $('input[name*="[popup_rec_button_"]').on('input', updateButtonStyles);
                $('.wp-color-picker-field[name*="[popup_rec_button_"]').on('wpcolorpickerchange', updateButtonStyles);
                updateButtonStyles();

                \$('select[name="' + optionPrefix + '[popup_recommendations_layout]"]').on('change', function() {
                    \$previewList.removeClass('layout-list layout-grid').addClass('layout-' + $(this).val());
                }).trigger('change');

                \$('input[name="' + optionPrefix + '[popup_rec_item_layout]"]').on('change', function() {
                    const layout = $(this).filter(':checked').val();
                    \$previewList.find('.rep-rec-item').removeClass('item-layout-vertical item-layout-horizontal').addClass('item-layout-' + layout);
                }).filter(':checked').trigger('change');
                
                function updateComponentVisibilityAndOrder() {
                    \$previewList.find('.rep-rec-item').each(function() {
                        const \$item = $(this);
                        const \$contentWrapper = \$item.find('.rep-rec-content');
                        const \$components = {
                            'thumbnail': \$item.find('.rep-rec-thumb-link'), 'meta': \$item.find('.rep-rec-meta'),
                            'title': \$item.find('.rep-rec-title'), 'excerpt': \$item.find('.rep-rec-excerpt'),
                            'link': \$item.find('.rep-rec-button')
                        };
                        Object.keys(\$components).forEach(key => \$components[key].toggle(\$(`#v_`+key).is(':checked')));
                        $('#rep-layout-builder li').each(function() {
                            const key = $(this).find('input[type=hidden]').val();
                            if (key !== 'thumbnail' && \$components[key]) { \$contentWrapper.append(\$components[key]); }
                        });
                    });
                }
                $('#rep-layout-builder').on('sortupdate change', updateComponentVisibilityAndOrder);
                updateComponentVisibilityAndOrder();
                
                const \$excerpt = \$previewList.find('.rep-rec-excerpt');
                function updateExcerptClamp() {
                    if (\$('input[name="' + optionPrefix + '[popup_rec_excerpt_limit_type]"]:checked').val() === 'lines') {
                        \$excerpt.css('-webkit-line-clamp', \$('#popup_rec_excerpt_lines').val());
                    } else {
                        \$excerpt.css('-webkit-line-clamp', 'unset');
                    }
                }
                \$('input[name="' + optionPrefix + '[popup_rec_excerpt_limit_type]"]').on('change', updateExcerptClamp);
                \$('#popup_rec_excerpt_lines').on('input change', updateExcerptClamp);
                updateExcerptClamp();

                // === POCZĄTEK NOWEJ LOGIKI: Aktualizacja liczby postów w podglądzie ===
                const \$countInput = $('#popup_recommendations_count');
                function updatePreviewPostCount() {
                    const newCount = parseInt(\$countInput.val(), 10) || 0;
                    const \$items = \$previewList.find('.rep-rec-item');
                    const currentCount = \$items.length;

                    if (newCount > currentCount) {
                        // Dodaj brakujące elementy
                        const \$template = \$items.first().clone();
                        for (let i = 0; i < newCount - currentCount; i++) {
                            \$previewList.append(\$template.clone());
                        }
                    } else if (newCount < currentCount) {
                        // Usuń nadmiarowe elementy
                        \$items.filter(':gt(' + (newCount - 1) + ')').remove();
                    }
                }
                \$countInput.on('input change', updatePreviewPostCount);
                // === KONIEC NOWEJ LOGIKI ===
            }
        });
JS;
        wp_add_inline_script('jquery-ui-sortable', $custom_js);
    }
}