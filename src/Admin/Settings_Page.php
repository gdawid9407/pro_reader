<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup_General;
use ReaderEngagementPro\Admin\Settings_Popup_Desktop;
use ReaderEngagementPro\Admin\Settings_Popup_Mobile;

/**
 * Zarządza główną stroną ustawień wtyczki.
 */
class Settings_Page
{
    private const SETTINGS_GROUP = 'reader_engagement_pro_group';
    private const OPTION_NAME = 'reader_engagement_pro_options';

    private Settings_Progress_Bar $progress_bar_settings;
    private Settings_Popup_General $popup_settings_general;
    private Settings_Popup_Desktop $popup_settings_desktop;
    private Settings_Popup_Mobile $popup_settings_mobile;

    public function __construct()
    {
        $this->progress_bar_settings = new Settings_Progress_Bar();
        $this->popup_settings_general = new Settings_Popup_General();
        $this->popup_settings_desktop = new Settings_Popup_Desktop();
        $this->popup_settings_mobile  = new Settings_Popup_Mobile();

        add_action('admin_init', [$this, 'page_init']);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Rejestruje ustawienia wtyczki.
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
        
        add_settings_section(
            'popup_reindex_section',
            __('Narzędzia Indeksowania', 'pro_reader'),
            null,
            'reader-engagement-pro-popup-general'
        );

        add_settings_field(
            'popup_reindex_button',
            __('Ręczne Indeksowanie', 'pro_reader'),
            [$this, 'reindex_button_callback'],
            'reader-engagement-pro-popup-general',
            'popup_reindex_section'
        );
    }

    /**
     * Dodaje stronę ustawień do menu w panelu WordPress.
     */
    public function add_plugin_page(): void
    {
        add_menu_page(
            __('Ustawienia Pro Reader', 'pro_reader'),
            __('Pro Reader', 'pro_reader'),
            'manage_options',
            'reader-engagement-pro',
            [$this, 'create_admin_page'],
            'dashicons-performance',
            81
        );
    }

    /**
     * Kieruje dane do odpowiedniej funkcji sanitacji na podstawie aktywnej zakładki.
     */
    public function route_sanitize_callback(array $input): array
    {
        $options = get_option(self::OPTION_NAME, []);
        $active_sub_tab = sanitize_key($_POST['rep_active_sub_tab'] ?? '');

        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }

        switch ($active_sub_tab) {
            case 'general':
                return $this->popup_settings_general->sanitize($input, $options);
            case 'desktop':
                return $this->popup_settings_desktop->sanitize($input, $options);
            case 'mobile':
                return $this->popup_settings_mobile->sanitize($input, $options);
        }
        return $options;
        
    }

    /**
     * Renderuje strukturę HTML strony ustawień.
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
                            submit_button();
                        } elseif ($active_tab === 'popup') {
                            $active_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'general';
                            ?>
                            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                                <a href="?page=reader-engagement-pro&tab=popup&sub_tab=general" class="nav-tab <?php echo $active_sub_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                                    <?php esc_html_e('Ustawienia Ogólne', 'pro_reader'); ?>
                                </a>
                                <a href="?page=reader-engagement-pro&tab=popup&sub_tab=desktop" class="nav-tab <?php echo $active_sub_tab === 'desktop' ? 'nav-tab-active' : ''; ?>">
                                    <?php esc_html_e('Wygląd - Desktop', 'pro_reader'); ?>
                                </a>
                                <a href="?page=reader-engagement-pro&tab=popup&sub_tab=mobile" class="nav-tab <?php echo $active_sub_tab === 'mobile' ? 'nav-tab-active' : ''; ?>">
                                    <?php esc_html_e('Wygląd - Mobilny', 'pro_reader'); ?>
                                </a>
                            </h2>
                            <?php
                            settings_fields(self::SETTINGS_GROUP);

                            echo '<input type="hidden" name="rep_active_sub_tab" value="' . esc_attr($active_sub_tab) . '">';

                            if ($active_sub_tab === 'general') {
                                do_settings_sections('reader-engagement-pro-popup-general');
                            } elseif ($active_sub_tab === 'desktop') {
                                do_settings_sections('reader-engagement-pro-popup-desktop');
                            } elseif ($active_sub_tab === 'mobile') {
                                do_settings_sections('reader-engagement-pro-popup-mobile');
                            }
                            submit_button();
                        }
                        ?>
                    </form>
                </div>

                <?php 
                if ($active_tab === 'popup') :
                    $active_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'general';
                    if (in_array($active_sub_tab, ['desktop', 'mobile'])) :
                        $preview_wrapper_class = 'rep-preview-mode-' . esc_attr($active_sub_tab);
                ?>
                <div id="rep-live-preview-wrapper" class="<?php echo $preview_wrapper_class; ?>" style="flex: 1; min-width: 400px;">
                    <div style="position: sticky; top: 50px;">
                        <h3 style="margin: 0 0 10px; padding: 0;"><?php esc_html_e('Podgląd na żywo', 'pro_reader'); ?></h3>
                        <div id="rep-live-preview-area" style="transform: scale(0.5); transform-origin: top left; width: 200%; height: 200%; pointer-events: none; background: #f0f0f1; padding: 20px; border-radius: 4px;">
                            <?php echo $this->render_preview_placeholder(); ?>
                        </div>
                    </div>
                </div>
                <?php 
                    endif;
                endif;
                ?>

            </div>
        </div>
        <?php
    }

    private function render_preview_placeholder(): string
    {
        ob_start();
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
     * Rejestruje skrypty i style dla strony ustawień.
     */
    public function enqueue_admin_assets($hook): void
    {
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }

        wp_enqueue_style('rep-popup-style-preview', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.2.0');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'rep-admin-script',
            REP_PLUGIN_URL . 'assets/js/admin-settings.js',
            ['jquery', 'wp-color-picker', 'jquery-ui-sortable'], 
            '1.2.0', 
            true 
        );

        wp_localize_script('rep-admin-script', 'REP_Admin_Settings', [
            'option_name_attr'      => self::OPTION_NAME,
            'reindex_nonce'         => wp_create_nonce('rep_reindex_nonce'),
            'reindex_text_default'  => __('Uruchom pełne indeksowanie', 'pro_reader'),
            'reindex_text_running'  => __('Indeksowanie...', 'pro_reader'),
            'reindex_text_wait'     => __('Proszę czekać, to może potrwać kilka minut.', 'pro_reader'),
            'reindex_text_error'    => __('Wystąpił nieoczekiwany błąd serwera.', 'pro_reader'),
        ]);

        $inline_css = "
            #rep-layout-builder .ui-sortable-placeholder { border: 2px dashed #ccd0d4; background: #f6f7f7; height: 40px; margin-bottom: 5px; visibility: visible !important; }
            #rep-layout-builder .ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.15); opacity: 0.95; }
        ";
        wp_add_inline_style('wp-admin', $inline_css);
    }
}