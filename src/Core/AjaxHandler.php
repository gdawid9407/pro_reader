<?php

namespace ReaderEngagementPro\Core;

if (!defined('ABSPATH')) {
    exit;
}

use ReaderEngagementPro\Database\LinkIndexer;
use ReaderEngagementPro\Database\RecommendationQuery;
use ReaderEngagementPro\Frontend\Popup;

/**
 * Obsługuje zapytania AJAX wtyczki.
 */
class AjaxHandler
{
    private array $options;

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);

        add_action('wp_ajax_nopriv_fetch_recommendations', [$this, 'fetch_recommendations']);
        add_action('wp_ajax_fetch_recommendations', [$this, 'fetch_recommendations']);
        add_action('wp_ajax_rep_reindex_posts', [$this, 'handle_reindex']);
    }

    /**
     * Pobiera i zwraca rekomendacje dla popupa.
     */
    public function fetch_recommendations(): void
    {
        check_ajax_referer('rep_recommendations_nonce', 'nonce');

        $logic             = $this->options['popup_recommendation_logic'] ?? 'hybrid_fill';
        $posts_count       = (int) ($this->options['popup_recommendations_count'] ?? 3);
        $source_post_types = $this->options['popup_recommendation_post_types'] ?? ['post'];
        if (empty($source_post_types)) {
            $source_post_types = ['post'];
        }

        $current_post_id = isset($_POST['current_post_id']) ? absint($_POST['current_post_id']) : 0;
        $exclude_ids     = $current_post_id > 0 ? [$current_post_id] : [];
        $recommended_ids = [];

        $query_service = new RecommendationQuery();

        switch ($logic) {
            case 'popularity':
                $recommended_ids = $query_service->get_popular_ids($posts_count, $exclude_ids, $source_post_types);
                break;
            case 'hybrid_fill':
                $recommended_ids = $query_service->get_popular_ids($posts_count, $exclude_ids, $source_post_types);
                if (count($recommended_ids) < $posts_count) {
                    $needed_count     = $posts_count - count($recommended_ids);
                    $fill_exclude_ids = array_merge($exclude_ids, $recommended_ids);
                    $latest_ids       = $query_service->get_latest_ids($needed_count, $fill_exclude_ids, $source_post_types);
                    $recommended_ids  = array_merge($recommended_ids, $latest_ids);
                }
                break;
            case 'hybrid_mix':
                $popular_count      = (int) ceil($posts_count / 2);
                $latest_count       = (int) floor($posts_count / 2);
                $popular_ids        = $query_service->get_popular_ids($popular_count, $exclude_ids, $source_post_types);
                $latest_exclude_ids = array_merge($exclude_ids, $popular_ids);
                $latest_ids         = $query_service->get_latest_ids($latest_count, $latest_exclude_ids, $source_post_types);
                $recommended_ids    = array_merge($popular_ids, $latest_ids);
                if (count($recommended_ids) < $posts_count) {
                    $needed_count           = $posts_count - count($recommended_ids);
                    $final_fill_exclude_ids = array_merge($exclude_ids, $recommended_ids);
                    $fill_ids               = $query_service->get_latest_ids($needed_count, $final_fill_exclude_ids, $source_post_types);
                    $recommended_ids        = array_merge($recommended_ids, $fill_ids);
                }
                break;
            case 'date':
            default:
                $recommended_ids = $query_service->get_latest_ids($posts_count, $exclude_ids, $source_post_types);
                break;
        }

        if (empty($recommended_ids)) {
            wp_send_json_error(['message' => 'Nie znaleziono rekomendacji.']);
        }

        $args = [
            'post_type'      => $source_post_types,
            'post_status'    => 'publish',
            'posts_per_page' => count($recommended_ids),
            'post__in'       => $recommended_ids,
            'orderby'        => 'post__in',
        ];
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $html = '';
            $popup_instance = new Popup();

            while ($query->have_posts()) {
                $query->the_post();
                $html .= $popup_instance->generate_recommendation_item_html(get_the_ID());
            }
            wp_reset_postdata();
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => 'Nie znaleziono postów dla podanych ID.']);
        }
    }

    /**
     * Obsługuje ręczne reindeksowanie postów.
     */
    public function handle_reindex(): void
    {
        check_ajax_referer('rep_reindex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Brak uprawnień.'], 403);
            return;
        }

        $indexer = new LinkIndexer();
        $args = [
            'post_type'      => 'post', 
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids'
        ];
        $post_ids = get_posts($args);

        foreach ($post_ids as $post_id) {
            $indexer->index_post($post_id);
        }

        $count = count($post_ids);
        wp_send_json_success(['message' => "Pomyślnie zindeksowano {$count} wpisów."]);
    }
}