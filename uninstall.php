<?php
/**
 * Skrypt deinstalacyjny wtyczki Reader Engagement Pro.
 *
 * Uruchamiany podczas usuwania wtyczki z panelu WordPress.
 * Zapewnia usunięcie wszystkich opcji i niestandardowych tabel z bazy danych.
 *
 * @package Reader Engagement Pro
 */

// Zabezpieczenie przed bezpośrednim dostępem do pliku.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Nazwa opcji, w której zapisane są ustawienia wtyczki.
$option_name = 'reader_engagement_pro_options';

// Usunięcie opcji wtyczki z tabeli wp_options.
delete_option($option_name);

// Uzyskanie dostępu do obiektu bazy danych WordPress.
global $wpdb;

// Nazwa niestandardowej tabeli przechowującej indeks linków.
$table_name = $wpdb->prefix . 'rep_link_index';

// Usunięcie niestandardowej tabeli z bazy danych.
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");