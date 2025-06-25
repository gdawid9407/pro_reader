<?php

class Reader_Engagement_Pro_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        add_options_page(
            'Reader Engagement Pro Settings',
            'Reader Engagement Pro',
            'manage_options',
            'reader-engagement-pro',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Ustawienia Paska Postępu</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'reader_engagement_pro_group' );
                do_settings_sections( 'reader-engagement-pro-admin' );
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
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id',
            'Ustawienia Paska Postępu',
            null,
            'reader-engagement-pro-admin'
        );

        add_settings_field(
            'position',
            'Pozycja Paska',
            array( $this, 'position_callback' ),
            'reader-engagement-pro-admin',
            'setting_section_id'
        );

        add_settings_field(
            'color',
            'Kolor Paska',
            array( $this, 'color_callback' ),
            'reader-engagement-pro-admin',
            'setting_section_id'
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['position'] ) )
            $new_input['position'] = sanitize_text_field( $input['position'] );
        if( isset( $input['color'] ) )
            $new_input['color'] = sanitize_text_field( $input['color'] );
        return $new_input;
    }

    public function position_callback() {
        $options = get_option( 'reader_engagement_pro_options' );
        echo '<input type="text" id="position" name="reader_engagement_pro_options[position]" value="' . $options['position'] . '" />';
    }

    public function color_callback() {
        $options = get_option( 'reader_engagement_pro_options' );
        echo '<input type="text" id="color" name="reader_engagement_pro_options[color]" value="' . $options['color'] . '" />';
    }
}

if( is_admin() )
    $reader_engagement_pro_settings = new Reader_Engagement_Pro_Settings();
