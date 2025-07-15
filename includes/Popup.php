<?php
/**
 * Zarządza logiką i renderowaniem Popupa "Czytaj Więcej".
 */
namespace ReaderEngagementPro;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Popup {

    private array $options = [];
    private static bool $assets_enqueued = false;
    private static bool $shortcode_used = false;

    public function __construct() {
        $this->options = get_option('reader_engagement_pro_options', []);
        
        if ( empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1' ) {
            return;
        }

        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_ajax_nopriv_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        add_action('wp_ajax_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        add_action('wp_footer', [$this, 'render_popup_in_footer']);
    }

    public function register_shortcode(): void {
        add_shortcode('pro_reader_popup', [$this, 'handle_shortcode']);
    }

    public function handle_shortcode(array $atts = []): string {
        if (!self::$shortcode_used) {
            self::$shortcode_used = true;
            $this->enqueue_assets();
        }
        return '';
    }

    public function render_popup_in_footer(): void {
        if (self::$shortcode_used) {
            echo $this->generate_popup_html();
        }
    }

    private function enqueue_assets(): void {
        if (self::$assets_enqueued) {
            return;
        }

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.4');
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.0.1', true);

        wp_localize_script(
            'rep-popup-script',
            'REP_Popup_Settings',
            [
                'popupEnable'            => $this->options['popup_enable'] ?? '0',
                'triggerByScrollPercentEnable'  => $this->options['popup_trigger_scroll_percent_enable'] ?? '1',
                'triggerByScrollPercent' => $this->options['popup_trigger_scroll_percent'] ?? 85,
                'triggerByTime'          => $this->options['popup_trigger_time'] ?? 60,
                'triggerByScrollUp'      => $this->options['popup_trigger_scroll_up'] ?? '0',
                'ajaxUrl'                => admin_url('admin-ajax.php'),
                'nonce'                  => wp_create_nonce('rep_recommendations_nonce'),
                'currentPostId'          => is_singular() ? get_the_ID() : 0,
            ]
        );

        self::$assets_enqueued = true;
    }

    private function generate_popup_html(): string {
        $popup_content = $this->options['popup_content_main'] ?? '';
        $layout_setting = $this->options['popup_recommendations_layout'] ?? 'list';
        $layout_class = 'layout-' . sanitize_html_class($layout_setting);

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

    public function fetch_recommendations_ajax(): void {
        check_ajax_referer('rep_recommendations_nonce', 'nonce');
        
        $posts_count = $this->options['popup_recommendations_count'] ?? 3;
        $current_post_id = isset($_POST['current_post_id']) ? absint($_POST['current_post_id']) : 0;
        
        $args = [
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => (int) $posts_count,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => $current_post_id > 0 ? [$current_post_id] : [],
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
            wp_send_json_error(['message' => 'Nie znaleziono rekomendacji.']);
        }
    }

    private function generate_recommendation_item_html(int $post_id): string {
        $options = $this->options;
        $item_layout = $options['popup_rec_item_layout'] ?? 'vertical';
        
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $options['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $options['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');

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
    
    /**
     * Zwraca HTML dla pojedynczego komponentu (klocka) na podstawie jego klucza.
     */
    private function get_component_html(string $key, int $post_id): string {
        $post_link = get_permalink($post_id);
        
        switch ($key) {
            case 'thumbnail':
                $thumb_size = $this->options['popup_rec_thumb_size'] ?? 'medium';
                $thumb_fit = $this->options['popup_rec_thumb_fit'] ?? 'cover';
                $aspect_ratio = $this->options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
                
                // Atrybuty dla obrazka <img>
                $image_attrs = [
                    'class' => 'rep-rec-thumb thumb-fit-' . sanitize_html_class($thumb_fit),
                ];
                $thumbnail_html = get_the_post_thumbnail($post_id, $thumb_size, $image_attrs);

                if (empty($thumbnail_html)) {
                    $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png';
                    $thumbnail_html = sprintf('<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder">', esc_url($placeholder_url));
                }
                
                // Style inline dla kontenera <a>
                $link_style = '';
                if ($aspect_ratio !== 'auto') {
                    // Zamiana formatu '16:9' na '16 / 9' dla CSS
                    $link_style = 'aspect-ratio: ' . str_replace(':', ' / ', $aspect_ratio) . ';';
                }

                return sprintf(
                    '<a href="%s" class="rep-rec-thumb-link" style="%s">%s</a>',
                    esc_url($post_link),
                    esc_attr($link_style),
                    $thumbnail_html
                );

            case 'title':
                return sprintf(
                    '<h3 class="rep-rec-title"><a href="%s">%s</a></h3>',
                    esc_url($post_link),
                    esc_html(get_the_title($post_id))
                );
                case 'excerpt':
    $excerpt = $this->get_processed_excerpt($post_id);
    if (empty($excerpt)) return '';

    $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
    $style_attr = '';

    // Dodaj styl limitu linii tylko, jeśli ta opcja jest aktywna
    if ($limit_type === 'lines') {
        $line_clamp = $this->options['popup_rec_excerpt_lines'] ?? 3;
        if ($line_clamp > 0) {
            $style_attr = sprintf(
                'style="display: -webkit-box; -webkit-line-clamp: %d; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;"',
                esc_attr($line_clamp)
            );
        }
    }
            
                
// POPRAWKA: Dodano %s dla atrybutu stylu ($style_attr)
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
                return sprintf(
                    '<a href="%s" class="rep-rec-link">%s</a>',
                    esc_url($post_link),
                    wp_kses_post($link_text)
                );
                
            default:
                return '';
        }
    }

    private function get_processed_excerpt(int $post_id): string {
    $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
    $raw_excerpt = get_the_excerpt($post_id);

    if (empty($raw_excerpt)) {
        return '';
    }

    // Stosuj limit słów tylko, jeśli jest wybrany
    if ($limit_type === 'words') {
        $length = $this->options['popup_rec_excerpt_length'] ?? 15;
        if ($length > 0) {
            return wp_trim_words($raw_excerpt, $length, '...');
        }
    }
    
    // W przeciwnym razie zwróć pełną zajawkę (limit linii zadziała w CSS)
    return $raw_excerpt;
}
}