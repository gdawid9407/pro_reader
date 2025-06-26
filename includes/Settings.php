<?php

namespace ReaderEngagementPro;

class Settings {
    private array $options = [];

    public function __construct() {
        $this->options = get_option('reader_engagement_pro_options', []);
        // Możliwość dalszej inicjalizacji ustawień
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }
    
    
    public function create_admin_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Reader Engagement', 'reader-engagement-pro'); ?></h1>
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

    public function add_plugin_page() {
        add_menu_page(
            'Reader Engagement',
            'Reader Engagement',
            'manage_options',
            'reader-engagement-pro',
            array($this, 'create_admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function page_init() {
        register_setting(
            'reader_engagement_pro_group',
            'reader_engagement_pro_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'setting_section_id',
            'Ustawienia paska postępu',
            null,
            'reader-engagement-pro'
        );

        add_settings_field(
            'position',
            'Pozycja',
            array($this, 'position_callback'),
            'reader-engagement-pro',
            'setting_section_id'
        );

        add_settings_field(
            'color',
            'Kolor',
            array($this, 'color_callback'),
            'reader-engagement-pro',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = [];
        if (isset($input['position'])) {
            $new_input['position'] = sanitize_text_field($input['position']);
        }
        if (isset($input['color'])) {
            $new_input['color'] = sanitize_text_field($input['color']);
        }
        return $new_input;
    }

    public function position_callback() {
        // Pobiera opcje jako tablicę, aby uniknąć wartości bool
        $options = get_option('reader_engagement_pro_options', []);
        $value = isset($options['position']) ? esc_attr($options['position']) : '';
        echo '<input type="text" id="position" name="reader_engagement_pro_options[position]" value="' . $value . '" />';
    }

    public function color_callback() {
        // Pobiera opcje jako tablicę, aby uniknąć wartości bool
        $options = get_option('reader_engagement_pro_options', []);
        $value = isset($options['color']) ? esc_attr($options['color']) : '';
        echo '<input type="text" id="color" name="reader_engagement_pro_options[color]" value="' . $value . '" />';
    }
}
