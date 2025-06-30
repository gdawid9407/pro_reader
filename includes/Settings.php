<?php

namespace ReaderEngagementPro;

class Settings {
    private array $options = [];

    public function __construct() {
        $this->options = get_option('reader_engagement_pro_options', []);
        add_action('admin_menu', [$this, 'add_plugin_page']);
        add_action('admin_init', [$this, 'page_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_plugin_page() {
        add_menu_page(
            'Reader Engagement Pro',
            'Reader Engagement',
            'manage_options',
            'reader-engagement-pro',
            [$this, 'create_admin_page']
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Reader Engagement Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('reader_engagement_pro_group');
                do_settings_sections('reader-engagement-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'reader_engagement_pro_group',
            'reader_engagement_pro_options',
            ['type' => 'array', 'sanitize_callback' => [$this, 'sanitize']]
        );

        add_settings_section(
            'main_section',
            'Główne ustawienia',
            null,
            'reader-engagement-pro'
        );

        add_settings_field(
            'position',
            'Pozycja paska',
            [$this, 'position_callback'],
            'reader-engagement-pro',
            'main_section'
        );
        add_settings_field(
            'color',
            'Kolor paska',
            [$this, 'color_callback'],
            'reader-engagement-pro',
            'main_section'
        );
        add_settings_field(
            'color_start',
            'Kolor startowy paska',
            [$this, 'color_start_callback'],
            'reader-engagement-pro',
            'main_section'
        );
        add_settings_field(
            'color_end',
            'Kolor końcowy paska',
            [$this, 'color_end_callback'],
            'reader-engagement-pro',
            'main_section'
        );
    }

    public function sanitize($input): array {
        $sanitized = [];
        $sanitized['position']     = sanitize_text_field($input['position'] ?? '');
        $sanitized['color']        = sanitize_hex_color($input['color'] ?? '');
        $sanitized['color_start']  = sanitize_hex_color($input['color_start'] ?? '');
        $sanitized['color_end']    = sanitize_hex_color($input['color_end'] ?? '');
        return $sanitized;
    }

    public function position_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        printf(
            '<input type="text" id="position" name="reader_engagement_pro_options[position]" value="%s" />',
            esc_attr($opts['position'] ?? '')
        );
    }

    public function color_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        printf(
            '<input type="text" id="color" name="reader_engagement_pro_options[color]" value="%s" class="wp-color-picker-field" data-default-color="#4facfe" />',
            esc_attr($opts['color'] ?? '')
        );
    }

    public function color_start_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        printf(
            '<input type="text" id="color_start" name="reader_engagement_pro_options[color_start]" value="%s" class="wp-color-picker-field" data-default-color="#4facfe" />',
            esc_attr($opts['color_start'] ?? '')
        );
    }

    public function color_end_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        printf(
            '<input type="text" id="color_end" name="reader_engagement_pro_options[color_end]" value="%s" class="wp-color-picker-field" data-default-color="#43e97b" />',
            esc_attr($opts['color_end'] ?? '')
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_reader-engagement-pro') {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(function($){$(".wp-color-picker-field").wpColorPicker();});'
        );
    }
}
