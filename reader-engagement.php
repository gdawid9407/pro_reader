<?php
/**
 * Plugin Name: Pro Reader
 * Description: Uniwersalny pasek czytania, popup "Czytaj więcej" i rekomendacje artykułów.
 * Version:     1.0.0
 * Author:      Dawid Gołis
 * Text Domain: pro_reader
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'REP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'REP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

$autoload_file = REP_PLUGIN_PATH . 'vendor/autoload.php';
if ( file_exists( $autoload_file ) ) {
    require_once $autoload_file;
} else {
     add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo '<strong>Pro Reader Plugin:</strong> Nie można załadować klas wtyczki. Proszę uruchomić <code>composer install</code> w katalogu wtyczki lub skontaktować się z autorem.';
        echo '</p></div>';
    });
    return;
}

use ReaderEngagementPro\ProgressBar;
use ReaderEngagementPro\Popup;
use ReaderEngagementPro\Admin\Settings_Page;

function rep_init_plugin() {
    new ProgressBar();
    new Popup();

    // Inicjalizacja panelu administracyjnego tylko wtedy, gdy jesteśmy w panelu admina.
    if ( is_admin() ) {
        new Settings_Page();
    }
}
add_action('plugins_loaded', 'rep_init_plugin');


