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

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.3');
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

    /**
     * Generuje HTML dla pojedynczej rekomendacji na podstawie ustawień z konstruktora układu.
     */
    private function generate_recommendation_item_html(int $post_id): string {
        $options = $this->options;
        $item_layout = $options['popup_rec_item_layout'] ?? 'vertical';
        
        // Domyślna kolejność i widoczność, jeśli nie ustawiono inaczej
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $options['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $options['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');

        $components_html = [];
        foreach ($components_order as $component_key) {
            // Renderuj komponent tylko jeśli jest włączony w opcjach
            if (!empty($components_visibility[$component_key])) {
                $components_html[$component_key] = $this->get_component_html($component_key, $post_id);
            }
        }
        
        $item_class = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
        ob_start();
        ?>
        <li class="<?php echo esc_attr($item_class); ?>">
            <?php
            // Dla układu horyzontalnego grupujemy miniaturkę i resztę treści
            if ($item_layout === 'horizontal' && !empty($components_html['thumbnail'])) {
                echo $components_html['thumbnail'];
                echo '<div class="rep-rec-content">';
                // Renderuj komponenty w ustalonej kolejności, pomijając miniaturkę
                foreach ($components_order as $key) {
                    if ($key !== 'thumbnail' && !empty($components_html[$key])) {
                        echo $components_html[$key];
                    }
                }
                echo '</div>';
            } else {
                // Dla układu wertykalnego renderuj wszystko po kolei
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
     * Pobiera HTML dla konkretnego "klocka" (komponentu).
     */
    private function get_component_html(string $key, int $post_id): string {
        $post_link = get_permalink($post_id);
        
        switch ($key) {
            case 'thumbnail':
                $thumbnail_html = get_the_post_thumbnail($post_id, 'medium', ['class' => 'rep-rec-thumb']);
                if (empty($thumbnail_html)) {
                    $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png';
                    $thumbnail_html = sprintf('<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder">', esc_url($placeholder_url));
                }
                return sprintf('<a href="%s" class="rep-rec-thumb-link">%s</a>', esc_url($post_link), $thumbnail_html);

            case 'title':
                return sprintf(
                    '<h3 class="rep-rec-title"><a href="%s">%s</a></h3>',
                    esc_url($post_link),
                    esc_html(get_the_title($post_id))
                );

            case 'excerpt':
                $excerpt = $this->get_processed_excerpt($post_id);
                if (empty($excerpt)) return '';
                return sprintf('<p class="rep-rec-excerpt">%s</p>', esc_html($excerpt));
                
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

    /**
     * Zwraca przetworzoną zajawkę z uwzględnieniem limitu słów.
     */
    private function get_processed_excerpt(int $post_id): string {
        $length = $this->options['popup_rec_excerpt_length'] ?? 15;
        $raw_excerpt = get_the_excerpt($post_id);

        if (empty($raw_excerpt)) {
            return '';
        }
        
        if ($length > 0) {
            return wp_trim_words($raw_excerpt, $length, '...');
        }
        
        return $raw_excerpt;
    }
}