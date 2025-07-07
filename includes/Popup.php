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

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.0.1');
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.0.1', true);

        // Ustalenie ID bieżącego posta, jeśli jesteśmy na stronie pojedynczego wpisu/strony.
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
                'currentPostId'          => $current_post_id, // WAŻNE: Przekazanie ID do JS
            ]
        );

        self::$assets_enqueued = true;
    }

    private function generate_popup_html(): string {
        $popup_content = $this->options['popup_content_main'] ?? '';

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

            <ul id="rep-intelligent-popup__list">
                <!-- Informacja o ładowaniu, która zostanie zastąpiona przez AJAX -->
                <li class="rep-rec-item-loading">Ładowanie rekomendacji...</li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obsługuje żądanie AJAX w celu pobrania i zwrócenia rekomendacji.
     */
    public function fetch_recommendations_ajax(): void {
        check_ajax_referer('rep_recommendations_nonce', 'nonce');
        
        // Pobierz liczbę postów do wyświetlenia z opcji, z domyślną wartością 3.
        $posts_count = $this->options['popup_recommendations_count'] ?? 3;
        
        // Pobierz ID bieżącego posta, aby go wykluczyć.
        $current_post_id = isset($_POST['current_post_id']) ? absint($_POST['current_post_id']) : 0;
        
        $args = [
            'post_type'      => ['post', 'page'], // Rekomenduj zarówno wpisy jak i strony
            'post_status'    => 'publish',
            'posts_per_page' => (int) $posts_count,
            'orderby'        => 'date',
            'order'          => 'DESC', // Najnowsze najpierw
        ];
        
        // Jeśli mamy ID bieżącego posta, dodaj je do tablicy wykluczeń.
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

    /**
     * Generuje HTML dla pojedynczego elementu listy rekomendacji.
     * @param int $post_id ID posta do wyrenderowania.
     * @return string Wygenerowany HTML.
     */
    private function generate_recommendation_item_html(int $post_id): string {
        $post_title = get_the_title($post_id);
        $post_link = get_permalink($post_id);
        $post_date = get_the_date('j F Y', $post_id);

        $thumbnail_html = get_the_post_thumbnail($post_id, 'thumbnail', ['class' => 'rep-rec-thumb']);
        // Zapewnienie obrazka zastępczego, jeśli wpis nie ma miniaturki.
        if (empty($thumbnail_html)) {
            $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png'; // UWAGA: Należy dodać obrazek placeholder.png
            $thumbnail_html = sprintf(
                '<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder" width="150" height="150">',
                esc_url($placeholder_url)
            );
        }

        ob_start();
        ?>
        <li class="rep-rec-item">
            <?php echo $thumbnail_html; ?>
            <div class="rep-rec-content">
                <p class="rep-rec-date"><?php echo esc_html($post_date); ?></p>
                <h3 class="rep-rec-title"><?php echo esc_html($post_title); ?></h3>
                <a href="<?php echo esc_url($post_link); ?>" class="rep-rec-link">Zobacz więcej</a>
            </div>
        </li>
        <?php
        return ob_get_clean();
    }
}