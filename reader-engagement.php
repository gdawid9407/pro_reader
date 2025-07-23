<?php
/**
 * Plugin Name: Pro Reader
 * Description: Uniwersalny pasek czytania, popup "Czytaj więcej" i rekomendacje artykułów.
 * Version:     1.1.0
 * Author:      Dawid Gołis
 * Text Domain: pro_reader
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Definicje kluczowych stałych wtyczki.
define('REP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('REP_PLUGIN_FILE', __FILE__); // Ważne dla haka aktywacyjnego

// Sprawdzenie i załadowanie autoloadera composera.
$autoload_file = REP_PLUGIN_PATH . 'vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
} else {
    // Wyświetl powiadomienie w panelu admina, jeśli autoloader nie istnieje.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>';
        echo '<strong>Pro Reader Plugin:</strong> Nie można załadować klas wtyczki. Proszę uruchomić <code>composer install</code> w katalogu wtyczki lub skontaktować się z autorem.';
        echo '</p></div>';
    });
    return;
}

// Inicjalizacja wtyczki.
new ReaderEngagementPro\Core\Plugin();