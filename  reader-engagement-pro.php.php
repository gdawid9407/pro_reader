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

Pro_Reader::init();
