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

        // Rejestracja punktu końcowego dla AJAX do obsługi reindeksowania.
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

        // Dodanie nowej sekcji dla narzędzia do indeksowania w zakładce "Popup".
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

            <form method="post" action="options.php">
                <?php
                if ($active_tab === 'progress_bar') {
                    settings_fields(self::SETTINGS_GROUP);
                    do_settings_sections('reader-engagement-pro-progress-bar');
                    submit_button();
                } elseif ($active_tab === 'popup') {
                    settings_fields(self::SETTINGS_GROUP);
                    do_settings_sections('reader-engagement-pro-popup');
                    // Przycisk submit jest warunkowy, bo sekcja z reindeksowaniem go nie potrzebuje.
                    // Można go przenieść do wewnątrz warunku, jeśli chcemy go tylko dla jednej zakładki.
                    submit_button();
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Wyświetla przycisk i status dla narzędzia do reindeksowania.
     */
    public function reindex_button_callback(): void
    {
        echo '<button type="button" id="rep-reindex-button" class="button button-secondary">' . esc_html__('Uruchom pełne indeksowanie', 'pro_reader') . '</button>';
        echo '<p class="description">' . esc_html__('Kliknij, aby przeskanować wszystkie opublikowane wpisy i zbudować bazę linków dla rekomendacji. Może to zająć chwilę.', 'pro_reader') . '</p>';
        echo '<div id="rep-reindex-status" style="margin-top: 10px;"></div>';
    }

    /**
     * Obsługuje żądanie AJAX do ponownego zindeksowania wszystkich postów.
     */
    public function handle_reindex_ajax(): void
    {
        check_ajax_referer('rep_reindex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Brak uprawnień.'], 403);
        }

        // Klasa REP_Link_Indexer jest w głównym pliku, więc jest dostępna globalnie.
        if (!class_exists('REP_Link_Indexer')) {
            wp_send_json_error(['message' => 'Krytyczny błąd: Klasa REP_Link_Indexer nie została znaleziona.'], 500);
        }
        
        $indexer = new \REP_Link_Indexer();
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // Pobierz wszystkie posty
            'fields'         => 'ids', // Potrzebujemy tylko ID
        ];
        
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
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        $inline_css = "
            #rep-layout-builder .ui-sortable-placeholder {
                border: 2px dashed #ccd0d4; background: #f6f7f7; height: 40px;
                margin-bottom: 5px; visibility: visible !important;
            }
            #rep-layout-builder .ui-sortable-helper {
                box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95;
            }
        ";
        wp_add_inline_style('wp-admin', $inline_css);

        $option_name_attr = esc_js(self::OPTION_NAME);
        $custom_js = "
        jQuery(document).ready(function($) {
            // Logika konstruktora układu drag-and-drop
            $('#rep-layout-builder').sortable({
                axis: 'y', cursor: 'move', placeholder: 'ui-sortable-placeholder',
                helper: 'clone', opacity: 0.8
            });

            // Logika ukrywania/pokazywania głównych opcji Popup
            const mainPopupEnableCheckbox = $('#popup_enable');
            if (mainPopupEnableCheckbox.length) {
                const dependentPopupOptions = mainPopupEnableCheckbox.closest('tr').siblings();
                function togglePopupOptionsVisibility() {
                    const isChecked = mainPopupEnableCheckbox.is(':checked');
                    dependentPopupOptions.toggle(isChecked);
                    if(isChecked) $('#popup_trigger_scroll_percent_enable').trigger('change');
                }
                mainPopupEnableCheckbox.on('change', togglePopupOptionsVisibility);
                togglePopupOptionsVisibility();
            }

            // Logika dla zagnieżdżonego checkboxa 'Procent przewinięcia'
            const nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
            if(nestedCheckbox.length) {
                const targetRow = $('#popup_trigger_scroll_percent').closest('tr');
                function toggleNestedVisibility() {
                    const isEnabled = nestedCheckbox.is(':checked') && mainPopupEnableCheckbox.is(':checked');
                    targetRow.toggle(isEnabled);
                }
                nestedCheckbox.on('change', toggleNestedVisibility);
            }
            
            // Logika przełączania pól dla limitu zajawki
            const limitTypeRadios = $('input[name=\"{$option_name_attr}[popup_rec_excerpt_limit_type]\"]');
            if (limitTypeRadios.length) {
                const wordsRow = $('#popup_rec_excerpt_length').closest('tr');
                const linesRow = $('#popup_rec_excerpt_lines').closest('tr');

                function toggleExcerptLimitFields() {
                    const selectedType = limitTypeRadios.filter(':checked').val();
                    wordsRow.toggle(selectedType === 'words');
                    linesRow.toggle(selectedType === 'lines');
                }
                toggleExcerptLimitFields(); 
                limitTypeRadios.on('change', toggleExcerptLimitFields);
            }

            // Logika przycisku do reindeksowania
            $('#rep-reindex-button').on('click', function(e) {
                e.preventDefault();
                const \$button = $(this);
                const \$status = $('#rep-reindex-status');

                if (\$button.is('.disabled')) {
                    return;
                }

                \$button.addClass('disabled').text('" . esc_js(__('Indeksowanie...', 'pro_reader')) . "');
                \$status.html('<span class=\"spinner is-active\" style=\"float:left; margin-right:5px;\"></span>" . esc_js(__('Proszę czekać, to może potrwać kilka minut.', 'pro_reader')) . "').css('color', '');

                $.post(ajaxurl, {
                    action: 'rep_reindex_posts',
                    nonce: '" . wp_create_nonce('rep_reindex_nonce') . "'
                })
                .done(function(response) {
                    if (response.success) {
                        \$status.text(response.data.message).css('color', 'green');
                    } else {
                        \$status.text('Błąd: ' + response.data.message).css('color', 'red');
                    }
                })
                .fail(function() {
                    \$status.text('" . esc_js(__('Wystąpił nieoczekiwany błąd serwera.', 'pro_reader')) . "').css('color', 'red');
                })
                .always(function() {
                    \$button.removeClass('disabled').text('" . esc_js(__('Uruchom pełne indeksowanie', 'pro_reader')) . "');
                    \$status.find('.spinner').remove();
                });
            });

            // Inicjalizacja color pickera
            $('.wp-color-picker-field').wpColorPicker();
        });
        ";
        wp_add_inline_script('jquery-ui-sortable', $custom_js);
    }
}