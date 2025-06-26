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


use ReaderEngagementPro\Class_Progress_Bar;
use ReaderEngagementPro\Settings;

add_action('plugins_loaded', function(){
    new Class_Progress_Bar();
    new Settings();
});

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style( 'pro-reader-style', plugin_dir_url(__FILE__).'assets/css/style.css' );
    wp_enqueue_script( 'pro-reader-script', plugin_dir_url(__FILE__).'assets/js/bundle.js', ['jquery'], null, true );
});


