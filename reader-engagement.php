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
 * Tworzy dedykowaną tabelę w bazie danych do indeksowania linków.
 * Używa dbDelta do bezpiecznego tworzenia/aktualizowania struktury tabeli.
 */
function rep_create_link_index_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rep_link_index';
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source_post_id bigint(20) UNSIGNED NOT NULL,
        linked_post_id bigint(20) UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_link (source_post_id, linked_post_id),
        KEY source_post_id (source_post_id),
        KEY linked_post_id (linked_post_id)
    ) $charset_collate;";

    dbDelta($sql);
}

/**
 * Funkcja uruchamiana podczas aktywacji wtyczki.
 */
function rep_activate_plugin() {
    rep_create_link_index_table();
}
register_activation_hook(__FILE__, 'rep_activate_plugin');


/**
 * Klasa odpowiedzialna za skanowanie treści postów i zapisywanie relacji linków.
 */
class REP_Link_Indexer {
    
    /**
     * Analizuje treść posta, wyodrębnia linki wewnętrzne i zapisuje je do bazy danych.
     *
     * @param int $post_id ID analizowanego posta.
     */
    public function index_post(int $post_id): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rep_link_index';
        
        $post_content = get_post_field('post_content', $post_id);
        if (empty($post_content)) {
            return;
        }

        // Krok 1: Wyczyść stare wpisy dla tego artykułu, aby uniknąć duplikatów.
        $wpdb->delete($table_name, ['source_post_id' => $post_id], ['%d']);

        // Krok 2: Znajdź wszystkie linki w treści.
        preg_match_all('/<a\s[^>]*href=[\"\'](http[^\"\']+)[\"\']/i', $post_content, $matches);
        
        $site_url = site_url();
        $linked_ids = [];

        foreach ($matches[1] as $url) {
            // Krok 3: Sprawdź, czy link prowadzi do tej samej witryny.
            if (strpos($url, $site_url) !== 0) {
                continue;
            }
            
            // Krok 4: Przekonwertuj URL na ID posta.
            $linked_post_id = url_to_postid($url);
            
            // Sprawdź, czy ID jest poprawne, czy nie jest to link do samego siebie i czy już go nie dodaliśmy.
            if ($linked_post_id > 0 && $linked_post_id !== $post_id && !in_array($linked_post_id, $linked_ids)) {
                $linked_ids[] = $linked_post_id;
            }
        }
        
        // Krok 5: Zapisz unikalne, znalezione ID do bazy danych.
        foreach ($linked_ids as $linked_id) {
            $wpdb->insert(
                $table_name,
                [
                    'source_post_id' => $post_id,
                    'linked_post_id' => $linked_id
                ],
                ['%d', '%d']
            );
        }
    }
}

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
    
    // Uruchamia indeksowanie przy zapisie posta.
    // Używamy 'save_post_post', aby działało tylko dla postów typu 'post'.
    add_action('save_post_post', 'rep_handle_post_save', 10, 2);
}
add_action('plugins_loaded', 'rep_init_plugin');


/**
 * Funkcja obsługująca zapis posta - sprawdza warunki i uruchamia indeksowanie.
 *
 * @param int $post_id ID zapisanego posta.
 * @param \WP_Post $post Obiekt zapisanego posta.
 */
function rep_handle_post_save(int $post_id, \WP_Post $post) {
    // Ignoruj autozapisy i rewizje.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    // Upewnij się, że status to 'publish'.
    if ('publish' !== $post->post_status) {
        return;
    }
    
    $indexer = new REP_Link_Indexer();
    $indexer->index_post($post_id);
}


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