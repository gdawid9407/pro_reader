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
            <h1>Ustawienia wtyczki Reader Engagement Pro</h1>
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
        add_settings_field(
            'opacity',
            'Przezroczystość paska',
            [$this, 'opacity_callback'],
            'reader-engagement-pro',
            'main_section'
        );
        
        add_settings_field(
            'label_start',
            'Tekst początkowy',
            [$this, 'label_start_callback'],
            'reader-engagement-pro',
            'main_section'
        );

        add_settings_field(
            'label_end',
            'Tekst końcowy',
            [$this, 'label_end_callback'],
            'reader-engagement-pro',
            'main_section'
        );
        
        add_settings_field(
            'content_selector',
            'Selektor treści',
            [$this, 'content_selector_callback'],
            'reader-engagement-pro',
            'main_section'
        );

    }

    public function sanitize($input): array {
        $sanitized = [];
        $sanitized['position']     = sanitize_text_field($input['position'] ?? '');
        $sanitized['color_start']  = sanitize_hex_color($input['color_start'] ?? '');
        $sanitized['color_end']    = sanitize_hex_color($input['color_end'] ?? '');
        $sanitized['opacity']          = isset($input['opacity']) ? str_replace(',', '.', $input['opacity']) : '1.0';
        $sanitized['opacity']          = max(0.0, min(1.0, floatval($sanitized['opacity'])));
        // Sanityzacja nowych pól tekstowych
        $sanitized['label_start']      = isset($input['label_start']) ? sanitize_text_field($input['label_start']) : 'Start';
        $sanitized['label_end']        = isset($input['label_end']) ? sanitize_text_field($input['label_end']) : 'Meta';
        $sanitized['content_selector'] = isset($input['content_selector']) ? sanitize_text_field($input['content_selector']) : '.entry-content';

        return $sanitized;
    }

    public function position_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        $current_position = esc_attr($opts['position'] ?? 'top');
        echo '<select id="position" name="reader_engagement_pro_options[position]">';
        echo '<option value="top"' . selected($current_position, 'top', false) . '>Góra</option>';
        echo '<option value="bottom"' . selected($current_position, 'bottom', false) . '>Dół</option>';
        echo '</select>';
        
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

    public function opacity_callback() {
        $opts = get_option('reader_engagement_pro_options', []);
        printf(
            '<input type="number" id="opacity" name="reader_engagement_pro_options[opacity]" value="%s" min="0" max="1" step="0.1" />
            <p class="description">Wprowadź wartość od 0.0 (całkowicie przezroczysty) do 1.0 (całkowicie widoczny).</p>',
            esc_attr($opts['opacity'] ?? '1.0')
        );
    }
    
     public function label_start_callback(): void {
        printf(
            '<input type="text" id="label_start" name="reader_engagement_pro_options[label_start]" value="%s" />',
            esc_attr($this->options['label_start'] ?? 'Start')
        );
    }

    public function label_end_callback(): void {
        printf(
            '<input type="text" id="label_end" name="reader_engagement_pro_options[label_end]" value="%s" />',
            esc_attr($this->options['label_end'] ?? 'Meta')
        );
    }
    
    public function content_selector_callback(): void {
        printf(
            '<input type="text" id="content_selector" name="reader_engagement_pro_options[content_selector]" value="%s" class="regular-text" />',
            esc_attr($this->options['content_selector'] ?? '.entry-content')
        );
        echo '<p class="description">Podaj selektor CSS dla głównego kontenera treści artykułu (np. <code>.entry-content</code>, <code>#main-content</code>, <code>article</code>). Poprawia to dokładność paska.</p>';
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
