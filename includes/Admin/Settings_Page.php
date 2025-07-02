<?php

namespace ReaderEngagementPro\Admin;


use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup;

class Settings_Page {


    private const SETTINGS_GROUP = 'reader_engagement_pro_group';
    private const OPTION_NAME = 'reader_engagement_pro_options';
    
    // KLUCZOWA ZMIANA: Przechowujemy instancje, aby móc wywołać ich metody.
    private Settings_Progress_Bar $progress_bar_settings;
    private Settings_Popup $popup_settings;


    public function __construct() {
        
        $this->progress_bar_settings = new Settings_Progress_Bar();
        $this->popup_settings = new Settings_Popup();
        
        add_action('admin_init', [$this, 'page_init']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Centralnie rejestruje naszą wspólną tablicę opcji.
     */
    public function page_init() {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            ['type' => 'array', 'sanitize_callback' => [$this, 'route_sanitize_callback']]
        );
    }
    public function add_plugin_page() {
        add_menu_page(
            'Pasek czytania',               // Tytuł strony (tag <title>)
            'Pasek czytania',               // Nazwa w menu
            'manage_options',               // Wymagane uprawnienia
            'reader-engagement-pro',        // Slug (URL) strony
            [$this, 'create_admin_page'],   // Funkcja renderująca zawartość strony
            'dashicons-performance',        // Ikona
            81                              // Pozycja w menu
        );
    }

/**
     * KLUCZOWA ZMIANA: Ta funkcja działa jak "router".
     * Sprawdza, skąd pochodzą dane i przekazuje je do odpowiedniej klasy w celu sanitacji.
     *
     * @param array $input Dane z formularza.
     * @return array Przetworzone dane.
     */
    public function route_sanitize_callback(array $input): array {
        // Sprawdzamy, czy dane pochodzą z formularza "Pasek Postępu"
        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }

        // Sprawdzamy, czy dane pochodzą z formularza "Popup"
        if (isset($input['popup_trigger_time'])) {
            return $this->popup_settings->sanitize($input);
        }

        // Jeśli dane nie pasują do żadnego formularza, zwróć puste lub istniejące opcje,
        // aby uniknąć przypadkowego wyczyszczenia.
        return get_option(self::OPTION_NAME, []);
    }


    /**
     * Renderuje stronę ustawień wraz z nawigacją zakładek.
     */
    public function create_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'progress_bar';

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ustawienia wtyczki Reader Engagement Pro', 'pro_reader'); ?></h1>
            <p><?php esc_html_e('Zarządzaj ustawieniami dla poszczególnych modułów wtyczki.', 'pro_reader'); ?></p>

            <!-- Nawigacja z zakładkami -->
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
                // Używamy JEDNEJ, tej samej grupy ustawień dla wszystkich zakładek.
                settings_fields(self::SETTINGS_GROUP);
                if ($active_tab === 'progress_bar') {
                    do_settings_sections('reader-engagement-pro-progress-bar');   // Sekcje dla paska
                } elseif ($active_tab === 'popup') {
                    do_settings_sections('reader-engagement-pro-popup');   // Sekcje dla popupa
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
        // Ładuj zasoby tylko na naszej stronie ustawień.
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }
        
        // Wbudowany w WordPress color picker.
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Mały skrypt inline do inicjalizacji color pickera.
        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(function($){ $(".wp-color-picker-field").wpColorPicker(); });'
        );
        
        $custom_js = "
            jQuery(document).ready(function($) {
                // Selektory
                var checkbox = $('#popup_trigger_scroll_percent_enable');
                var targetRow = $('#popup_trigger_scroll_percent').closest('tr');

                // Funkcja do przełączania widoczności
                function toggleVisibility() {
                    if (checkbox.is(':checked')) {
                        targetRow.show();
                    } else {
                        targetRow.hide();
                    }
                }

                // Sprawdź stan przy załadowaniu strony
                toggleVisibility();

                // Dodaj listener do zmiany stanu checkboxa
                checkbox.on('change', function() {
                    toggleVisibility();
                });
            });
        ";
        wp_add_inline_script('wp-color-picker', $custom_js);
        
    }
}