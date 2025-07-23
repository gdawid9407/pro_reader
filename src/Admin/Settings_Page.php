<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

// Import klas, których będziemy używać w tym pliku.
use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup;

/**
 * Klasa odpowiedzialna za tworzenie i zarządzanie główną stroną ustawień wtyczki.
 */
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

    /**
     * Rejestruje ustawienia i pola na stronie admina.
     */
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

        // Sekcja dla narzędzi indeksowania (zawiera przycisk reindeksacji)
        add_settings_section(
            'popup_reindex_section',
            __('Narzędzia Indeksowania', 'pro_reader'),
            null,
            'reader-engagement-pro-popup'
        );

        // Pole z przyciskiem do ręcznego indeksowania
        add_settings_field(
            'popup_reindex_button',
            __('Ręczne Indeksowanie', 'pro_reader'),
            [$this, 'reindex_button_callback'],
            'reader-engagement-pro-popup',
            'popup_reindex_section'
        );
    }

    /**
     * Dodaje stronę ustawień wtyczki do menu w panelu WordPress.
     */
    public function add_plugin_page(): void
    {
        add_menu_page(
            __('Ustawienia Pro Reader', 'pro_reader'), // Tytuł strony
            __('Pro Reader', 'pro_reader'),           // Tytuł w menu
            'manage_options',                         // Wymagane uprawnienia
            'reader-engagement-pro',                  // Slug menu
            [$this, 'create_admin_page'],             // Funkcja renderująca stronę
            'dashicons-performance',                  // Ikona
            81                                        // Pozycja w menu
        );
    }

    /**
     * Kieruje dane do odpowiedniej funkcji sanitacji na podstawie przesłanych pól.
     */
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

    /**
     * Renderuje główną strukturę HTML strony ustawień (zakładki, formularz).
     */
    public function create_admin_page(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'progress_bar';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ustawienia wtyczki Reader Engagement Pro', 'pro_reader'); ?></h1>
            <p><?php esc_html_e('Zarządzaj ustawieniami dla poszczególnych modułów wtyczki.', 'pro_reader'); ?></p>

            <h2 class="nav-tab-wrapper">
                <a href="?page=reader-engagement-pro&tab=progress_bar" class="nav-tab <?php echo $active_tab === 'progress_bar' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Pasek Postępu', 'pro_reader'); ?>
                </a>
                <a href="?page=reader-engagement-pro&tab=popup" class="nav-tab <?php echo $active_tab === 'popup' ? 'nav-tab-active' : ''; ?>">
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
                        } elseif ($active_tab === 'popup') {
                            settings_fields(self::SETTINGS_GROUP);
                            do_settings_sections('reader-engagement-pro-popup');
                        }
                        submit_button();
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
        ob_start();
        // Dołączamy plik szablonu, zamiast trzymać HTML w metodzie.
        include REP_PLUGIN_PATH . 'src/Templates/popup/preview.php';
        return ob_get_clean();
    }

    /**
     * Wyświetla przycisk do ręcznego indeksowania.
     */
    public function reindex_button_callback(): void
    {
        echo '<button type="button" id="rep-reindex-button" class="button button-secondary">' . esc_html__('Uruchom pełne indeksowanie', 'pro_reader') . '</button>';
        echo '<p class="description">' . esc_html__('Kliknij, aby przeskanować wszystkie opublikowane wpisy i zbudować bazę linków dla rekomendacji. Może to zająć chwilę.', 'pro_reader') . '</p>';
        echo '<div id="rep-reindex-status" style="margin-top: 10px;"></div>';
    }

    /**
     * Rejestruje i dołącza skrypty oraz style dla strony ustawień.
     */
    public function enqueue_admin_assets($hook): void
    {
        // Sprawdź, czy jesteśmy na właściwej stronie ustawień.
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }

        // Rejestracja stylów i zależności.
        wp_enqueue_style('rep-popup-style-preview', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.1.0');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        // Rejestracja naszego nowego, dedykowanego pliku JavaScript.
        wp_enqueue_script(
            'rep-admin-script',
            REP_PLUGIN_URL . 'assets/js/admin-settings.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-sortable'], 
            '1.1.0', 
            true 
        );

        // Bezpieczne przekazanie danych z PHP do JavaScript za pomocą wp_localize_script.
        wp_localize_script('rep-admin-script', 'REP_Admin_Settings', [
            'option_name_attr'      => self::OPTION_NAME,
            'reindex_nonce'         => wp_create_nonce('rep_reindex_nonce'),
            'reindex_text_default'  => __('Uruchom pełne indeksowanie', 'pro_reader'),
            'reindex_text_running'  => __('Indeksowanie...', 'pro_reader'),
            'reindex_text_wait'     => __('Proszę czekać, to może potrwać kilka minut.', 'pro_reader'),
            'reindex_text_error'    => __('Wystąpił nieoczekiwany błąd serwera.', 'pro_reader'),
        ]);

        // Dodanie stylów inline (to jest w porządku, bo jest małe i specyficzne).
        $inline_css = "
            #rep-layout-builder .ui-sortable-placeholder { border: 2px dashed #ccd0d4; background: #f6f7f7; height: 40px; margin-bottom: 5px; visibility: visible !important; }
            #rep-layout-builder .ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95; }
        ";
        wp_add_inline_style('wp-admin', $inline_css);
    }
}