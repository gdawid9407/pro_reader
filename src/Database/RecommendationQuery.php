<?php

namespace ReaderEngagementPro\Database;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa odpowiedzialna za wykonywanie zapytań do bazy danych w celu pobrania rekomendacji.
 */
class RecommendationQuery
{
    /**
     * Pobiera ID najpopularniejszych postów na podstawie liczby linków wewnętrznych.
     *
     * @param int   $count       Liczba ID do pobrania.
     * @param array $exclude_ids Tablica ID postów do wykluczenia.
     * @param array $post_types  Tablica typów postów do uwzględnienia.
     * @return array Tablica ID postów.
     */
    public function get_popular_ids(int $count, array $exclude_ids = [], array $post_types = ['post']): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rep_link_index';

        if (empty($post_types)) {
            return [];
        }

        // Sprawdź, czy tabela indeksu istnieje.
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            return [];
        }

        $where_clauses = ["p.post_status = 'publish'"];
        $params = [];

        // Dynamiczne budowanie warunku dla typów postów.
        $post_type_placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
        $where_clauses[] = "p.post_type IN ($post_type_placeholders)";
        $params = array_merge($params, $post_types);

        // Dynamiczne budowanie warunku dla wykluczonych ID.
        if (!empty($exclude_ids)) {
            $exclude_placeholders = implode(', ', array_fill(0, count($exclude_ids), '%d'));
            $where_clauses[] = "li.linked_post_id NOT IN ($exclude_placeholders)";
            $params = array_merge($params, $exclude_ids);
        }

        // Przygotowanie i wykonanie zapytania.
        $query = "SELECT li.linked_post_id
                  FROM {$table_name} AS li
                  JOIN {$wpdb->posts} AS p ON li.linked_post_id = p.ID
                  WHERE " . implode(' AND ', $where_clauses) . "
                  GROUP BY li.linked_post_id
                  ORDER BY COUNT(li.linked_post_id) DESC
                  LIMIT %d";
        $params[] = $count;

        $prepared_query = $wpdb->prepare($query, $params);
        $ids = $wpdb->get_col($prepared_query);

        if (!is_array($ids)) {
            return [];
        }

        return array_map('absint', $ids);
    }

    /**
     * Pobiera ID najnowszych postów.
     *
     * @param int   $count       Liczba ID do pobrania.
     * @param array $exclude_ids Tablica ID postów do wykluczenia.
     * @param array $post_types  Tablica typów postów do uwzględnienia.
     * @return array Tablica ID postów.
     */
    public function get_latest_ids(int $count, array $exclude_ids = [], array $post_types = ['post']): array
    {
        if ($count <= 0 || empty($post_types)) {
            return [];
        }

        $args = [
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => array_unique(array_filter($exclude_ids)),
            'fields'         => 'ids',
        ];

        $query = new \WP_Query($args);
        return $query->posts;
    }
}