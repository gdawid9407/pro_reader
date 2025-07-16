<?php
/**
 * Plugin Name: Pro Reader
 * Description: Uniwersalny pasek czytania, popup "Czytaj więcej" i rekomendacje artykułów.
 * Version:     1.0.0
 * Author:      Dawid Gołis
 * Text Domain: pro_reader
 */

if (!defined('ABSPATH')) {
    exit;
}

define('REP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Sprawdzenie i załadowanie autoloadera composera.
$autoload_file = REP_PLUGIN_PATH . 'vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
} else {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        echo '<strong>Pro Reader Plugin:</strong> Nie można załadować klas wtyczki. Proszę uruchomić <code>composer install</code> w katalogu wtyczki lub skontaktować się z autorem.';
        echo '</p></div>';
    });
    return;
}

use ReaderEngagementPro\ProgressBar;
use ReaderEngagementPro\Popup;
use ReaderEngagementPro\Admin\Settings_Page;

/**
 * Inicjalizuje główne klasy wtyczki.
 */
function rep_init_plugin()
{
    new ProgressBar();
    new Popup();

    if (is_admin()) {
        new Settings_Page();
    }
}
add_action('plugins_loaded', 'rep_init_plugin');


/*
|--------------------------------------------------------------------------
| Rejestracja bloku Gutenberga dla popupu
|--------------------------------------------------------------------------
*/

/**
 * Rejestruje typ bloku na podstawie pliku block.json.
 */
function pro_reader_register_blocks()
{
    register_block_type(REP_PLUGIN_PATH . 'blocks/popup');
}
add_action('init', 'pro_reader_register_blocks');

/**
 * Wymusza ładowanie skryptu edytora bloku z poprawnymi zależnościami.
 * Rozwiązuje problemy z automatycznym ładowaniem zasobów przez WordPress.
 */
function pro_reader_enqueue_block_editor_assets()
{
    $script_asset_path = REP_PLUGIN_PATH . "assets/js/popup-block.asset.php";
    if (!file_exists($script_asset_path)) {
        // Ostrzeżenie na wypadek braku kluczowego pliku generowanego przez @wordpress/scripts.
        trigger_error(
            'Nie można znaleźć pliku popup-block.asset.php. Upewnij się, że proces budowania (npm run build) został wykonany.',
            E_USER_WARNING
        );
        return;
    }
    $script_asset = require($script_asset_path);

    wp_enqueue_script(
        'pro-reader-popup-block',
        REP_PLUGIN_URL . 'assets/js/popup-block.js',
        $script_asset['dependencies'],
        $script_asset['version'],
        true
    );
}
add_action('enqueue_block_editor_assets', 'pro_reader_enqueue_block_editor_assets');

/**
 * Funkcja renderująca blok po stronie publicznej (jeśli jest potrzebna).
 * Obecnie logika popupa opiera się na shortcode [pro_reader_popup], który jest dodawany
 * przez blok, a nie na renderowaniu przez PHP.
 */
function pro_reader_render_popup_block($attributes)
{
    // Shortcode [pro_reader_popup] jest już obecny w zapisanej treści posta.
    // Metoda handle_shortcode w klasie Popup zajmie się resztą.
    // Z tego powodu ta funkcja może pozostać pusta lub zwracać pusty string.
    return '';
}