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
     * Konstruktor klasy. Rejestruje potrzebne akcje WordPressa.
     */
    public function __construct() {
        // Wczytanie opcji wtyczki.
        $this->options = get_option('reader_engagement_pro_options', []);
        
        if ( empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1' ) {
            return; }

        add_action('init', [$this, 'register_shortcode']);
        add_action('wp_ajax_nopriv_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
        add_action('wp_ajax_fetch_recommendations', [$this, 'fetch_recommendations_ajax']);
    }

    /**
     * Rejestruje shortcode w systemie WordPress.
     */
    public function register_shortcode(): void {
        add_shortcode('pro_reader_popup', [$this, 'render_popup_from_shortcode']);
    }

    /**
     * Renderuje HTML popupa w miejscu użycia shortcode'u i kolejkuje niezbędne zasoby.
     *
     * @param array $atts Atrybuty shortcode'u (obecnie nieużywane).
     * @return string HTML popupa.
     */
    public function render_popup_from_shortcode(array $atts = []): string {
        $this->enqueue_assets();
        return $this->generate_popup_html();
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
        // Rozpoczynamy buforowanie wyjścia, aby przechwycić HTML do zmiennej.
        ob_start();
        ?>
        <div id="rep-intelligent-popup__overlay"></div>
        <div id="rep-intelligent-popup__container" role="dialog" aria-modal="true" aria-labelledby="rep-intelligent-popup__title" style="display: none;">
            <header id="rep-intelligent-popup__header">
                <h2 id="rep-intelligent-popup__title">Może Cię zainteresować</h2>
                <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
            </header>
            <ul id="rep-intelligent-popup__list">
                <!-- Treść (rekomendacje) zostanie wstrzyknięta tutaj dynamicznie przez AJAX -->
                <li class="rep-rec-item-loading">Ładowanie rekomendacji...</li>
            </ul>
        </div>
        <?php
        // Zwracamy zawartość bufora jako string.
        return ob_get_clean();
    }

    /**
     * Obsługuje żądanie AJAX w celu pobrania rekomendacji.
     * Na tym etapie jest to placeholder.
     */
    public function fetch_recommendations_ajax(): void {
        // Weryfikacja Nonce dla bezpieczeństwa
        check_ajax_referer('rep_recommendations_nonce', 'nonce');
        
        // TODO: Tutaj zostanie zaimplementowana logika pobierania postów.
        // np. za pomocą nowej klasy Recommendations.

        // Przykładowa odpowiedź błędu
        wp_send_json_error(['message' => 'Funkcjonalność w trakcie budowy.']);
    }
}