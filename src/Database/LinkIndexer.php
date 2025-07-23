<?php

namespace ReaderEngagementPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa odpowiedzialna za skanowanie treści postów i zapisywanie relacji linków.
 */
class LinkIndexer
{
    /**
     * Analizuje treść posta, wyodrębnia linki wewnętrzne i zapisuje je do bazy danych.
     *
     * @param int $post_id ID analizowanego posta.
     */
    public function index_post(int $post_id): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rep_link_index';

        $post_content = get_post_field('post_content', $post_id);
        if (empty($post_content)) {
            // Jeśli treść jest pusta, czyścimy stare wpisy i kończymy.
            $wpdb->delete($table_name, ['source_post_id' => $post_id], ['%d']);
            return;
        }

        // Krok 1: Wyczyść stare wpisy dla tego artykułu, aby uniknąć duplikatów przed ponownym wstawieniem.
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

        if (empty($linked_ids)) {
            return;
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