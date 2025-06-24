<?php
/**
 * Plugin Name: Pro Reader
 * Description: Uniwersalny pasek czytania, popup "Czytaj więcej" i rekomendacje artykułów.
 * Version:     1.0.0
 * Author:      Twoje Imię
 * Text Domain: pro_reader
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';


function reader_engagement_pro_assets() {
    // CSS
    wp_enqueue_style( 'bootstrap-css', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
    // JS
    wp_enqueue_script( 'bootstrap-js', plugin_dir_url( __FILE__ ) . 'assets/js/bundle.js', array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'reader_engagement_pro_assets' );

Pro_Reader::init();
