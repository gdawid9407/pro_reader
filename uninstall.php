<?php
/**
 * Usuwanie danych wtyczki przy deaktywacji
 *
 * @package Reader Engagement Pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Usuwanie opcji wtyczki z bazy danych
delete_option( 'reader_engagement_pro_settings' );

// Usuwanie niestandardowych tabel w bazie danych, jeśli istnieją
global $wpdb;
$table_name = $wpdb->prefix . 'reader_engagement_pro_progress';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}

// Usuwanie wszystkich danych związanych z wtyczką, jeśli było to wymagane
$wpdb->query( "DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'reader_engagement_pro'" );
$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE post_id IN (SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'reader_engagement_pro')" );

// Usuwanie innych danych związanych z wtyczką
// (np. dane logów, ustawienia użytkowników itd. - dostosuj do specyfiki wtyczki)
