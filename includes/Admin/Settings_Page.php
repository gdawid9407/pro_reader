<?php

namespace ReaderEngagementPro\Admin;


use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup;

class Settings_Page {
    public function __construct() {
       
        new Settings_Progress_Bar();
        new Settings_Popup();
       
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Dodaje stronę ustawień do menu panelu administracyjnego.
     */
    public function add_plugin_page() {
        add_menu_page(
            'Pasek czytania',         // Tytuł strony (tag <title>)
            'Pasek czytania',                    // Nazwa w menu
            'manage_options',                // Wymagane uprawnienia
            'reader-engagement-pro',         // Slug (URL) strony
            [$this, 'create_admin_page'],    // Funkcja renderująca zawartość strony
            'dashicons-performance',         // Ikona
            81                               // Pozycja w menu
        );
    }

    /**
     * Renderuje stronę ustawień wraz z nawigacją zakładek.
     */
    public function create_admin_page() {
        // Pobieramy aktywną zakładkę z URL, domyślnie jest to 'progress_bar'.
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
                // W zależności od aktywnej zakładki, ładujemy odpowiednią grupę ustawień.
                // Każda zakładka musi mieć unikalną grupę zarejestrowaną przez register_setting().
                if ($active_tab === 'progress_bar') {
                    settings_fields('reader_engagement_pro_progress_bar_group'); // Grupa dla paska
                    do_settings_sections('reader-engagement-pro-progress-bar');   // Sekcje dla paska
                } elseif ($active_tab === 'popup') {
                    settings_fields('reader_engagement_pro_popup_group'); // Grupa dla popupa
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
    }
}