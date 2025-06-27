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

define( 'REP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require __DIR__ . '/vendor/autoload.php';


use ReaderEngagementPro\Class_Progress_Bar;
use ReaderEngagementPro\Settings;

add_action('plugins_loaded', function(){
    new Class_Progress_Bar();
    new Settings();
});



