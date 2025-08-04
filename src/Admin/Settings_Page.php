<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use ReaderEngagementPro\Admin\Settings_Progress_Bar;
use ReaderEngagementPro\Admin\Settings_Popup_General;
use ReaderEngagementPro\Admin\Settings_Popup_Desktop;
use ReaderEngagementPro\Admin\Settings_Popup_Mobile;

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

    public function page_init(): void
    {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            ['type' => 'array', 'sanitize_callback' => [$this, 'route_sanitize_callback']]
        );
        add_settings_section('popup_reindex_section', __('Narzędzia Indeksowania', 'pro_reader'), null, 'reader-engagement-pro-popup-general');
        add_settings_field('popup_reindex_button', __('Ręczne Indeksowanie', 'pro_reader'), [$this, 'reindex_button_callback'], 'reader-engagement-pro-popup-general', 'popup_reindex_section');
    }

    public function add_plugin_page(): void
    {
        add_menu_page(__('Ustawienia Pro Reader', 'pro_reader'), __('Pro Reader', 'pro_reader'), 'manage_options', 'reader-engagement-pro', [$this, 'create_admin_page'], 'dashicons-performance', 81);
    }

    public function route_sanitize_callback(array $input): array
    {
        $options = get_option(self::OPTION_NAME, []);
        $active_sub_tab = isset($_POST['rep_active_sub_tab']) ? sanitize_key($_POST['rep_active_sub_tab']) : '';
        if (isset($input['position'])) {
            return $this->progress_bar_settings->sanitize($input);
        }
        switch ($active_sub_tab) {
            case 'general': return $this->popup_settings_general->sanitize($input, $options);
            case 'desktop': return $this->popup_settings_desktop->sanitize($input, $options);
            case 'mobile': return $this->popup_settings_mobile->sanitize($input, $options);
            default: return $options;
        }
    }

    public function create_admin_page(): void
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'progress_bar';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ustawienia wtyczki Reader Engagement Pro', 'pro_reader'); ?></h1>
            <p><?php esc_html_e('Zarządzaj ustawieniami dla poszczególnych modułów wtyczki.', 'pro_reader'); ?></p>
            <h2 class="nav-tab-wrapper">
                <a href="?page=reader-engagement-pro&tab=progress_bar" class="nav-tab <?php echo $active_tab === 'progress_bar' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Pasek Postępu', 'pro_reader'); ?></a>
                <a href="?page=reader-engagement-pro&tab=popup" class="nav-tab <?php echo $active_tab === 'popup' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Popup "Czytaj Więcej"', 'pro_reader'); ?></a>
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
                            ?>
                            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                                <a href="#reader-engagement-pro-popup-general" class="nav-tab"><?php esc_html_e('Ustawienia Ogólne', 'pro_reader'); ?></a>
                                <a href="#reader-engagement-pro-popup-desktop" class="nav-tab"><?php esc_html_e('Wygląd - Desktop', 'pro_reader'); ?></a>
                                <a href="#reader-engagement-pro-popup-mobile" class="nav-tab"><?php esc_html_e('Wygląd - Mobilny', 'pro_reader'); ?></a>
                            </h2>
                            <?php
                            settings_fields(self::SETTINGS_GROUP);
                            echo '<input type="hidden" id="rep_active_sub_tab_input" name="rep_active_sub_tab" value="general">';
                            echo '<div id="reader-engagement-pro-popup-general" class="settings-tab-content">';
                            do_settings_sections('reader-engagement-pro-popup-general');
                            echo '</div>';
                            echo '<div id="reader-engagement-pro-popup-desktop" class="settings-tab-content" style="display:none;">';
                            do_settings_sections('reader-engagement-pro-popup-desktop');
                            echo '</div>';
                            echo '<div id="reader-engagement-pro-popup-mobile" class="settings-tab-content" style="display:none;">';
                            do_settings_sections('reader-engagement-pro-popup-mobile');
                            echo '</div>';
                            submit_button();
                        }
                        ?>
                    </form>
                </div>
                <?php 
                $active_sub_tab = isset($_GET['sub-tab']) ? sanitize_key($_GET['sub-tab']) : 'general';
                if ($active_tab === 'popup') : 
                ?>
                <div id="rep-settings-preview-wrapper" style="flex: 1; min-width: 400px; position: sticky; top: 40px; height: calc(100vh - 80px);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0;"><?php esc_html_e('Podgląd na żywo', 'pro_reader'); ?></h3>
                        <!-- POCZĄTEK ZMIANY: Usunęliśmy przyciski przełączania -->
                        <div id="rep-preview-device-label" style="font-style: italic; color: #555;"></div>
                        <!-- KONIEC ZMIANY -->
                    </div>
                    <div id="rep-preview-container" class="is-desktop">
                        <iframe id="rep-live-preview-iframe" title="<?php esc_attr_e('Podgląd popupa na żywo', 'pro_reader'); ?>"></iframe>
                    </div>
                    <style>
                        #rep-preview-container { width: 100%; height: 100%; border: 1px solid #ccd0d4; background: #f0f0f1; box-shadow: 0 1px 1px rgba(0,0,0,.04); transition: all 0.4s ease-in-out; margin: 0 auto; }
                        #rep-live-preview-iframe { width: 100%; height: 100%; border: none; background: #fff; }
                        #rep-preview-container.is-mobile { width: 375px; height: 90%; max-height: 667px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-radius: 20px; border: 5px solid #333; }
                    </style>
                     <p class="description" style="margin-top: 15px; text-align: center;"><?php esc_html_e('Podgląd odzwierciedla niezapisane zmiany.', 'pro_reader'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_assets(string $hook): void { /* ... bez zmian ... */
        if ($hook !== 'toplevel_page_reader-engagement-pro') { return; }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('rep-admin-settings', REP_PLUGIN_URL . 'assets/js/admin-settings.js', ['jquery', 'wp-color-picker', 'jquery-ui-sortable'], '2.1.0', true);
        wp_localize_script('rep-admin-settings', 'REP_Admin_Settings', [
            'ajax_url' => admin_url('admin-ajax.php'), 'admin_nonce' => wp_create_nonce('rep_admin_nonce'), 'reindex_nonce' => wp_create_nonce('rep_reindex_nonce'), 'reindex_text_default' => __('Przebuduj indeks linków', 'pro_reader'), 'reindex_text_running' => __('Indeksowanie w toku...', 'pro_reader'), 'reindex_text_wait' => __('Proszę czekać, to może potrwać chwilę.', 'pro_reader'), 'reindex_text_error' => __('Wystąpił błąd serwera.', 'pro_reader'), 'option_name_attr' => self::OPTION_NAME
        ]);
    }

    public function reindex_button_callback(): void { /* ... bez zmian ... */
        echo '<button id="rep-reindex-button" class="button button-secondary">' . esc_html__('Przebuduj indeks linków', 'pro_reader') . '</button>';
        echo '<p class="description">' . esc_html__('Użyj tego narzędzia, jeśli dodałeś nowe wpisy lub uważasz, że rekomendacje nie działają poprawnie.', 'pro_reader') . '</p>';
        echo '<div id="rep-reindex-status" style="margin-top: 10px;"></div>';
    }
}