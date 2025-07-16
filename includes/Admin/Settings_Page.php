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

    /**
     * Kieruje dane z formularza do odpowiedniej metody sanitacji na podstawie
     * unikalnych kluczy pól z danej zakładki.
     */
    public function route_sanitize_callback(array $input): array
    {
        // Sprawdza, czy dane pochodzą z formularza "Pasek Postępu".
        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }

        // Sprawdza, czy dane pochodzą z formularza "Popup".
        if (isset($input['popup_trigger_time']) || isset($input['popup_rec_item_layout'])) {
            return $this->popup_settings->sanitize($input);
        }

        // Zwraca istniejące opcje, aby uniknąć ich wyczyszczenia,
        // jeśli żaden z powyższych warunków nie został spełniony.
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
                settings_fields(self::SETTINGS_GROUP);
                if ($active_tab === 'progress_bar') {
                    do_settings_sections('reader-engagement-pro-progress-bar');
                } elseif ($active_tab === 'popup') {
                    do_settings_sections('reader-engagement-pro-popup');
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
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

            // Inicjalizacja color pickera
            $('.wp-color-picker-field').wpColorPicker();
        });
        ";
        wp_add_inline_script('jquery-ui-sortable', $custom_js);
    }
}