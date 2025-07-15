<?php

namespace ReaderEngagementPro\Admin;


use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup;

class Settings_Page {


    private const SETTINGS_GROUP = 'reader_engagement_pro_group';
    private const OPTION_NAME = 'reader_engagement_pro_options';
    
    private Settings_Progress_Bar $progress_bar_settings;
    private Settings_Popup $popup_settings;


    public function __construct() {
        
        $this->progress_bar_settings = new Settings_Progress_Bar();
        $this->popup_settings = new Settings_Popup();
        
        add_action('admin_init', [$this, 'page_init']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function page_init() {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            ['type' => 'array', 'sanitize_callback' => [$this, 'route_sanitize_callback']]
        );
    }
    public function add_plugin_page() {
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

    public function route_sanitize_callback(array $input): array {
        // Sprawdza, czy dane pochodzą z formularza "Pasek Postępu"
        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }

        // Sprawdza, czy dane pochodzą z formularza "Popup"
        // Używamy klucza, który jest unikalny dla tej zakładki, aby poprawnie skierować sanitację.
        if (isset($input['popup_trigger_time']) || isset($input['popup_rec_item_layout'])) {
            return $this->popup_settings->sanitize($input);
        }

        // Jeśli dane nie pasują, zwróć istniejące opcje, aby uniknąć ich wyczyszczenia.
        return get_option(self::OPTION_NAME, []);
    }

    public function create_admin_page() {
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

    /**
     * Kolejkuje skrypty i style potrzebne na stronie ustawień.
     */
    public function enqueue_admin_assets($hook) {
        
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }
        
        // Zależności
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable'); // Kluczowa zależność dla drag-and-drop

        // NOWOŚĆ: Style dla placeholdera w konstruktorze układu
        $inline_css = "
            #rep-layout-builder .ui-sortable-placeholder { 
                border: 2px dashed #ccd0d4; 
                background: #f6f7f7;
                height: 40px;
                margin-bottom: 5px;
                visibility: visible !important;
            }
            #rep-layout-builder .ui-sortable-helper {
                box-shadow: 0 5px 15px rgba(0,0,0,0.15);
                opacity: 0.95;
            }
        ";
        wp_add_inline_style('wp-admin', $inline_css); // Dołączenie stylów

        // Skrypt inicjalizujący
        $custom_js = "
            jQuery(document).ready(function($) {

                // --- START: Logika konstruktora układu ---
                var layoutBuilder = $('#rep-layout-builder');
                if (layoutBuilder.length) {
                    layoutBuilder.sortable({
                        axis: 'y',
                        cursor: 'move',
                        placeholder: 'ui-sortable-placeholder',
                        helper: 'clone',
                        opacity: 0.8
                    });
                }
                // --- KONIEC: Logika konstruktora układu ---


                // --- START: Logika ukrywania/pokazywania opcji Popup ---
                var mainPopupEnableCheckbox = $('#popup_enable');
                if (mainPopupEnableCheckbox.length) {
                    var dependentPopupOptions = mainPopupEnableCheckbox.closest('tr').siblings();

                    function togglePopupOptionsVisibility() {
                        if (mainPopupEnableCheckbox.is(':checked')) {
                            dependentPopupOptions.show();
                            $('#popup_trigger_scroll_percent_enable').trigger('change');
                        } else {
                            dependentPopupOptions.hide();
                        }
                    }
                    togglePopupOptionsVisibility();
                    mainPopupEnableCheckbox.on('change', togglePopupOptionsVisibility);
                }
                // --- KONIEC: Logika ukrywania/pokazywania opcji Popup ---


                // --- Istniejąca logika dla pola 'Wyzwalacz: Procent przewinięcia' ---
                var nestedCheckbox = $('#popup_trigger_scroll_percent_enable');
                if(nestedCheckbox.length) {
                    var targetRow = $('#popup_trigger_scroll_percent').closest('tr');
                    function toggleNestedVisibility() {
                        if (nestedCheckbox.is(':checked') && mainPopupEnableCheckbox.is(':checked')) {
                            targetRow.show();
                        } else {
                            targetRow.hide();
                        }
                    }
                    nestedCheckbox.on('change', toggleNestedVisibility);
                }

                // Inicjalizacja color pickera
                $('.wp-color-picker-field').wpColorPicker();
            });
        ";
        wp_add_inline_script('jquery-ui-sortable', $custom_js);
    }
}