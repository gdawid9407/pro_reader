<?php
/**
 * Zarządza logiką i renderowaniem Popupa "Czytaj Więcej".
 * Popup jest wstawiany na stronę za pomocą shortcode'u [pro_reader_popup].
 */
namespace ReaderEngagementPro;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Popup {

    /**
     * @var array Przechowuje wczytane opcje wtyczki.
     */
    private array $options = [];

    /**
     * @var bool Flaga zapobiegająca wielokrotnemu ładowaniu zasobów, jeśli shortcode jest użyty więcej niż raz.
     */
    private static bool $assets_enqueued = false;

    /**
     * ZMIANA: Flaga wskazująca, czy shortcode został użyty na stronie.
     * To decyduje, czy popup zostanie wyrenderowany w stopce.
     * @var bool
     */
    private static bool $shortcode_used = false;

    /**
     * Konstruktor klasy. Rejestruje potrzebne akcje WordPressa.
     */
    public function __construct() {
        // Wczytanie opcji wtyczki.
        $this->options = get_option('reader_engagement_pro_options', []);
        
        if ( empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1' ) {
            return;
        }

        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_ajax_nopriv_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        add_action('wp_ajax_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);

        // ZMIANA: Dodajemy akcję, która wyrenderuje HTML w stopce strony.
        add_action('wp_footer', [$this, 'render_popup_in_footer']);
    }

    /**
     * Rejestruje shortcode w systemie WordPress.
     */
    public function register_shortcode(): void {
        add_shortcode('pro_reader_popup', [$this, 'handle_shortcode']);
    }

    /**
     * ZMIANA: Obsługuje shortcode. Nie renderuje HTML, a jedynie kolejkuje zasoby i ustawia flagę.
     *
     * @param array $atts Atrybuty shortcode'u (obecnie nieużywane).
     * @return string Zawsze zwraca pusty ciąg znaków.
     */
    public function handle_shortcode(array $atts = []): string {
        if (!self::$shortcode_used) {
            self::$shortcode_used = true;
            $this->enqueue_assets();
        }
        return ''; // Zawsze zwracaj pusty string!
    }

    /**
     * NOWA METODA: Renderuje HTML popupa w stopce, jeśli shortcode został użyty.
     */
    public function render_popup_in_footer(): void {
        // Renderuj popup tylko wtedy, gdy na stronie znajduje się shortcode.
        if (self::$shortcode_used) {
            echo $this->generate_popup_html();
        }
    }

    private function enqueue_assets(): void {
        // Upewniamy się, że skrypty i style są ładowane tylko raz.
        if (self::$assets_enqueued) {
            return;
        }

        wp_enqueue_style(
            'rep-popup-style',
            REP_PLUGIN_URL . 'assets/css/popup.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'rep-popup-script',
            REP_PLUGIN_URL . 'assets/js/popup.js',
            ['jquery'],
            '1.0.0',
            true
        );

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
            ]
        );

        self::$assets_enqueued = true;
    }

    /**
     * Generuje szkielet HTML dla popupa.
     *
     * @return string
     */
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
                <?php
                echo wp_kses_post($popup_content);
                ?>
            </div>

            <ul id="rep-intelligent-popup__list">
                <li class="rep-rec-item-loading">Ładowanie rekomendacji...</li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obsługuje żądanie AJAX w celu pobrania rekomendacji.
     */
    public function fetch_recommendations_ajax(): void {
        check_ajax_referer('rep_recommendations_nonce', 'nonce');
        
        // TODO: Tutaj zostanie zaimplementowana logika pobierania postów.
        
        wp_send_json_error(['message' => 'Funkcjonalność w trakcie budowy.']);
    }
}