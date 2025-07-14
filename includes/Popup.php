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

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.2'); // Zwiększona wersja
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.0.1', true);

        $current_post_id = 0;
        if (is_singular()) {
            $current_post_id = get_the_ID();
        }

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
                'currentPostId'          => $current_post_id,
            ]
        );

        self::$assets_enqueued = true;
    }

    private function generate_popup_html(): string {
        $popup_content = $this->options['popup_content_main'] ?? '';
        
        // NOWOŚĆ: Pobranie ustawienia layoutu i przygotowanie klasy CSS.
        $layout_setting = $this->options['popup_recommendations_layout'] ?? 'list';
        $layout_class = 'layout-' . sanitize_html_class($layout_setting); // np. 'layout-list' lub 'layout-grid'

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
                <!-- Informacja o ładowaniu, która zostanie zastąpiona przez AJAX -->
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
        ];
        
        if ($current_post_id > 0) {
            $args['post__not_in'] = [$current_post_id];
        }

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
        $post_title = get_the_title($post_id);
        $post_link = get_permalink($post_id);
        // ZMIANA FORMATU DATY, ABY PASOWAŁ DO OBRAZKA
        $post_date = get_the_date('j F, Y', $post_id);

        $thumbnail_html = get_the_post_thumbnail($post_id, 'medium', ['class' => 'rep-rec-thumb']); // Zmiana na 'medium' dla lepszej jakości w siatce
        if (empty($thumbnail_html)) {
            $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png';
            $thumbnail_html = sprintf(
                '<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder">',
                esc_url($placeholder_url)
            );
        }
        
        // NOWOŚĆ: Pobranie kategorii lub terminu taksonomii
        $category_html = '';
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $category_html = ' • ' . esc_html($categories[0]->name);
        }

        ob_start();
        ?>
        <li class="rep-rec-item">
            <a href="<?php echo esc_url($post_link); ?>" class="rep-rec-thumb-link">
                <?php echo $thumbnail_html; ?>
            </a>
            <div class="rep-rec-content">
                <p class="rep-rec-date"><?php echo esc_html($post_date) . $category_html; ?></p>
                <h3 class="rep-rec-title">
                     <a href="<?php echo esc_url($post_link); ?>"><?php echo esc_html($post_title); ?></a>
                </h3>
                <a href="<?php echo esc_url($post_link); ?>" class="rep-rec-link">Zobacz więcej →</a>
            </div>
        </li>
        <?php
        return ob_get_clean();
    }
}