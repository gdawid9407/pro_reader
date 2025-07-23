<?php

namespace ReaderEngagementPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa odpowiedzialna za operacje instalacyjne i deinstalacyjne wtyczki.
 */
class Installer
{
    /**
     * Główna metoda uruchamiana podczas aktywacji wtyczki.
     */
    public static function activate(): void
    {
        self::create_link_index_table();
    }


    private static function create_link_index_table(): void
    {
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
}