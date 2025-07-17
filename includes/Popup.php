<?php

namespace ReaderEngagementPro;

if (!defined('ABSPATH')) {
    exit;
}

class Popup
{
    private array $options = [];
    private bool $should_render = false;
    private static bool $assets_enqueued = false;

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);
        
        // === POCZĄTEK ZMIANY: Kluczowa zmiana w architekturze ===
        // Rejestrujemy obsługę AJAX bezwarunkowo. Logika decydująca o wyświetleniu
        // będzie sprawdzana wewnątrz funkcji AJAX, a nie podczas jej rejestracji.
        // To zapewnia, że WordPress zawsze "wie", co zrobić z żądaniem.
        add_action('wp_ajax_nopriv_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        add_action('wp_ajax_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        
        // Logikę decydującą o wyświetleniu popupa na stronie zostawiamy tak jak była.
        if (!empty($this->options['popup_enable']) && $this->options['popup_enable'] === '1') {
            add_action('wp', [$this, 'decide_to_render']);
        }
        // === KONIEC ZMIANY ===
    }

    public function decide_to_render(): void
    {
        $display_on = $this->options['popup_display_on'] ?? [];
        if (empty($display_on)) {
            return;
        }

        if (is_singular($display_on)) {
            $this->should_render = true;
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_footer', [$this, 'render_popup_in_footer']);
        }
    }

    public function render_popup_in_footer(): void
    {
        if ($this->should_render) {
            echo $this->generate_popup_html();
        }
    }

    public function enqueue_assets(): void
    {
        if (!$this->should_render || self::$assets_enqueued) {
            return;
        }

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.4');
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.0.1', true);

        wp_localize_script(
            'rep-popup-script',
            'REP_Popup_Settings',
            [
                'popupEnable'                  => $this->options['popup_enable'] ?? '0',
                'triggerByScrollPercentEnable' => $this->options['popup_trigger_scroll_percent_enable'] ?? '1',
                'triggerByScrollPercent'       => $this->options['popup_trigger_scroll_percent'] ?? 85,
                'triggerByTime'                => $this->options['popup_trigger_time'] ?? 60,
                'triggerByScrollUp'            => $this->options['popup_trigger_scroll_up'] ?? '0',
                'ajaxUrl'                      => admin_url('admin-ajax.php'),
                'nonce'                        => wp_create_nonce('rep_recommendations_nonce'),
                'currentPostId'                => is_singular() ? get_the_ID() : 0,
            ]
        );

        self::$assets_enqueued = true;
    }

    private function generate_popup_html(): string
    {
        $popup_content  = $this->options['popup_content_main'] ?? '';
        $layout_setting = $this->options['popup_recommendations_layout'] ?? 'list';
        $layout_class   = 'layout-' . sanitize_html_class($layout_setting);

        ob_start();
        ?>
        <div id="rep-intelligent-popup__overlay"></div>
        <div id="rep-intelligent-popup__container" role="dialog" aria-modal="true" aria-labelledby="rep-intelligent-popup__title-static">
            <header id="rep-intelligent-popup__header">
                <h2 id="rep-intelligent-popup__title-static" class="screen-reader-text">Rekomendowane treści</h2>
                <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
            </header>
            
            <div id="rep-intelligent-popup__custom-content">
                <?php echo wp_kses_post($popup_content); ?>
            </div>

            <ul id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
                <li class="rep-rec-item-loading">Ładowanie rekomendacji...</li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_popular_post_ids(int $count, array $exclude_ids = []): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rep_link_index';

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
            return [];
        }

        $query = "SELECT linked_post_id FROM {$table_name}";
        $params = [];

        if (!empty($exclude_ids)) {
            $placeholders = implode(', ', array_fill(0, count($exclude_ids), '%d'));
            $query .= " WHERE linked_post_id NOT IN ({$placeholders})";
            $params = array_merge($params, $exclude_ids);
        }

        $query .= " GROUP BY linked_post_id ORDER BY COUNT(linked_post_id) DESC LIMIT %d";
        $params[] = $count;
    
        $prepared_query = $wpdb->prepare($query, $params);
        $ids = $wpdb->get_col($prepared_query);
    
        if (!is_array($ids)) {
            return [];
        }
    
        return array_map('absint', $ids);
    }

    private function get_latest_post_ids(int $count, array $exclude_ids = []): array
    {
        if ($count <= 0) { return []; }
        $args = [
            'post_type' => ['post', 'page'], 'post_status' => 'publish',
            'posts_per_page' => $count, 'orderby' => 'date', 'order' => 'DESC',
            'post__not_in' => array_unique(array_filter($exclude_ids)), 'fields' => 'ids',
        ];
        $query = new \WP_Query($args);
        return $query->posts;
    }

    public function fetch_recommendations_ajax(): void
    {
        check_ajax_referer('rep_recommendations_nonce', 'nonce');
        
        $logic = $this->options['popup_recommendation_logic'] ?? 'hybrid_fill';
        $posts_count = (int) ($this->options['popup_recommendations_count'] ?? 3);
        $current_post_id = isset($_POST['current_post_id']) ? absint($_POST['current_post_id']) : 0;
        
        $recommended_ids = [];
        $exclude_ids = $current_post_id > 0 ? [$current_post_id] : [];

        switch ($logic) {
            case 'popularity':
                $recommended_ids = $this->get_popular_post_ids($posts_count, $exclude_ids);
                break;
            case 'hybrid_fill':
                $popular_ids = $this->get_popular_post_ids($posts_count, $exclude_ids);
                $recommended_ids = $popular_ids;
                $found_count = count($recommended_ids);
                if ($found_count < $posts_count) {
                    $needed_count = $posts_count - $found_count;
                    $fill_exclude_ids = array_merge($exclude_ids, $recommended_ids);
                    $latest_ids = $this->get_latest_post_ids($needed_count, $fill_exclude_ids);
                    $recommended_ids = array_merge($recommended_ids, $latest_ids);
                }
                break;
            case 'hybrid_mix':
                $popular_count = (int) ceil($posts_count / 2);
                $latest_count = (int) floor($posts_count / 2);
                $popular_ids = $this->get_popular_post_ids($popular_count, $exclude_ids);
                $latest_exclude_ids = array_merge($exclude_ids, $popular_ids);
                $latest_ids = $this->get_latest_post_ids($latest_count, $latest_exclude_ids);
                $recommended_ids = array_merge($popular_ids, $latest_ids);
                $found_count = count($recommended_ids);
                if ($found_count < $posts_count) {
                    $needed_count = $posts_count - $found_count;
                    $final_fill_exclude_ids = array_merge($exclude_ids, $recommended_ids);
                    $fill_ids = $this->get_latest_post_ids($needed_count, $final_fill_exclude_ids);
                    $recommended_ids = array_merge($recommended_ids, $fill_ids);
                }
                break;
            case 'date':
            default:
                $recommended_ids = $this->get_latest_post_ids($posts_count, $exclude_ids);
                break;
        }

        if (empty($recommended_ids)) {
            wp_send_json_error(['message' => 'Nie znaleziono rekomendacji.']);
            return;
        }
        
        $args = [
            'post_type' => ['post', 'page'], 'post_status' => 'publish',
            'posts_per_page' => count($recommended_ids), 'post__in' => $recommended_ids,
            'orderby' => 'post__in',
        ];
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            $html = '';
            while ($query->have_posts()) {
                $query->the_post();
                $html .= $this->generate_recommendation_item_html(get_the_ID());
            }
            wp_reset_postdata();
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => 'Nie znaleziono postów dla podanych ID.']);
        }
    }

    private function generate_recommendation_item_html(int $post_id): string
    {
        $item_layout = $this->options['popup_rec_item_layout'] ?? 'vertical';
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $this->options['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $this->options['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');
        $components_html = [];

        foreach ($components_order as $component_key) {
            if (!empty($components_visibility[$component_key])) {
                $components_html[$component_key] = $this->get_component_html($component_key, $post_id);
            }
        }
        
        $item_class = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
        ob_start();
        ?>
        <li class="<?php echo esc_attr($item_class); ?>">
            <?php
            if ($item_layout === 'horizontal' && !empty($components_html['thumbnail'])) {
                echo $components_html['thumbnail'];
                echo '<div class="rep-rec-content">';
                foreach ($components_order as $key) {
                    if ($key !== 'thumbnail' && !empty($components_html[$key])) {
                        echo $components_html[$key];
                    }
                }
                echo '</div>';
            } else {
                foreach ($components_order as $key) {
                    if (!empty($components_html[$key])) {
                        echo $components_html[$key];
                    }
                }
            }
            ?>
        </li>
        <?php
        return ob_get_clean();
    }
    
    private function get_component_html(string $key, int $post_id): string
    {
        $post_link = get_permalink($post_id);
        
        switch ($key) {
            case 'thumbnail':
                $thumb_size = $this->options['popup_rec_thumb_size'] ?? 'medium';
                $thumb_fit = $this->options['popup_rec_thumb_fit'] ?? 'cover';
                $aspect_ratio = $this->options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
                $image_attrs = ['class' => 'rep-rec-thumb thumb-fit-' . sanitize_html_class($thumb_fit)];
                $thumbnail_html = get_the_post_thumbnail($post_id, $thumb_size, $image_attrs);
                if (empty($thumbnail_html)) {
                    $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png';
                    $thumbnail_html = sprintf('<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder">', esc_url($placeholder_url));
                }
                $link_style = '';
                if ($aspect_ratio !== 'auto') {
                    $link_style = 'aspect-ratio: ' . str_replace(':', ' / ', $aspect_ratio) . ';';
                }
                return sprintf('<a href="%s" class="rep-rec-thumb-link" style="%s">%s</a>', esc_url($post_link), esc_attr($link_style), $thumbnail_html);
            case 'title':
                return sprintf('<h3 class="rep-rec-title"><a href="%s">%s</a></h3>', esc_url($post_link), esc_html(get_the_title($post_id)));
            case 'excerpt':
                $excerpt = $this->get_processed_excerpt($post_id);
                if (empty($excerpt)) { return ''; }
                $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
                $style_attr = '';
                if ($limit_type === 'lines') {
                    $line_clamp = $this->options['popup_rec_excerpt_lines'] ?? 3;
                    if ($line_clamp > 0) {
                        $style_attr = sprintf('style="display: -webkit-box; -webkit-line-clamp: %d; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;"', esc_attr($line_clamp));
                    }
                }
                return sprintf('<p class="rep-rec-excerpt" %s>%s</p>', $style_attr, esc_html($excerpt));
            case 'meta':
                $date = get_the_date('j F, Y', $post_id);
                $category_html = '';
                $categories = get_the_category($post_id);
                if (!empty($categories)) {
                    $category_html = ' <span class="rep-rec-meta-separator">•</span> <span class="rep-rec-category">' . esc_html($categories[0]->name) . '</span>';
                }
                return sprintf('<p class="rep-rec-meta"><span class="rep-rec-date">%s</span>%s</p>', esc_html($date), $category_html);
            case 'link':
    $link_text = $this->options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

    // Pobierz opcje stylu przycisku
    $bg_color        = $this->options['popup_rec_button_bg_color'] ?? '#0073aa';
    $text_color      = $this->options['popup_rec_button_text_color'] ?? '#ffffff';
    $bg_hover_color  = $this->options['popup_rec_button_bg_hover_color'] ?? '#005177';
    $text_hover_color= $this->options['popup_rec_button_text_hover_color'] ?? '#ffffff';
    $border_radius   = $this->options['popup_rec_button_border_radius'] ?? 4;

    // Przygotuj zmienne CSS dla stylów inline
    $style_vars = sprintf(
        '--rep-btn-bg: %s; --rep-btn-text: %s; --rep-btn-bg-hover: %s; --rep-btn-text-hover: %s; border-radius: %dpx;',
        esc_attr($bg_color),
        esc_attr($text_color),
        esc_attr($bg_hover_color),
        esc_attr($text_hover_color),
        esc_attr($border_radius)
    );
    
    // Zamiast klasy 'rep-rec-link', użyj 'rep-rec-button' i dodaj style
    return sprintf(
        '<a href="%s" class="rep-rec-button" style="%s">%s</a>',
        esc_url($post_link),
        $style_vars,
        wp_kses_post($link_text)
    );
            default:
                return '';
        }
    }

    private function get_processed_excerpt(int $post_id): string
    {
        $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
        $raw_excerpt = get_the_excerpt($post_id);
        if (empty($raw_excerpt)) { return ''; }
        if ($limit_type === 'words') {
            $length = $this->options['popup_rec_excerpt_length'] ?? 15;
            if ($length > 0) {
                return wp_trim_words($raw_excerpt, $length, '...');
            }
        }
        return $raw_excerpt;
    }
}