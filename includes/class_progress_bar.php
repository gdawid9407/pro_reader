<?php
namespace ReaderEngagementPro;

class Class_Progress_Bar {
    public function __construct() {
        // Rejestracja akcji ładowania zasobów i renderowania paska
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('init',               [ $this, 'register_shortcodes' ]);
    }

    public function enqueue_assets() : void{
        // Ładowanie stylów i skryptów
        wp_enqueue_style(
            'rep-progress-style',
            REP_PLUGIN_URL . 'assets/css/style.css');
        wp_enqueue_script(
            'rep-progress-script',
            REP_PLUGIN_URL . 'assets/js/progress-bar.js',
            [], '1.1', true );

        // Przekazanie ustawień do skryptu JS
        $opts     = get_option( 'reader_engagement_pro_options', [] );
        wp_localize_script(
            'rep-progress-script',
            'REP_Progress_Settings',
            [
                // ZMIANA: Przekazujemy kolory startowy i końcowy zamiast pojedynczego koloru.
                'position'         => $opts['position'] ?? 'top',
                'colorStart'       => $opts['color_start'] ?? '', // Dodano kolor startowy
                'colorEnd'         => $opts['color_end'] ?? '',   // Dodano kolor końcowy
                'opacity'          => $opts['opacity'] ?? '1.0',
                'contentSelector'  => $opts['content_selector'] ?? '',
                'showPercentage'   => $opts['show_percentage'] ?? '0',
            ]
        );
    }

      /**
     * Generuje spójny kod HTML dla paska postępu.
     * Używane przez render_bar() i render_bar_shortcode() w celu unikania duplikacji kodu.
     *
     * @return string
     */
    private function generate_bar_html(): string {
        $opts = get_option('reader_engagement_pro_options', []);
        $posClass = ($opts['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $opacity = $opts['opacity'] ?? '1.0';
        $label_start = $opts['label_start'] ?? 'Start';
        $label_end   = $opts['label_end'] ?? 'Meta';
        $show_percentage = $opts['show_percentage'] ?? '0';

        ob_start();
        ?>
        <div id="progress-bar-container-wrapper" class="proreader-container <?php echo esc_attr($posClass); ?>" style="opacity:<?php echo esc_attr($opacity); ?>;">
            <div id="progress-bar-gradient" class="proreader-gradient">
                <div id="progress-bar" class="proreader-bar"></div>
            </div>
            <div class="proreader-labels">
                 <span class="label-start"><?php echo esc_html($label_start); ?></span>
                <span class="label-end"><?php echo esc_html($label_end); ?></span>
            </div>
            <?php if ($show_percentage === '1') : ?>
                <span id="rep-progress-percentage">0%</span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Wyświetla pasek postępu (zwykle w stopce).
     */
    public function render_bar(): void {
        // UWAGA: Należy dodać warunek, aby pasek nie wyświetlał się,
        // jeśli na stronie jest już użyty shortcode [progress_bar].
        echo $this->generate_bar_html();
    }

    public function register_shortcodes(): void {
        add_shortcode('progress_bar', [$this, 'render_bar_shortcode']);
    }

    /**
     * Renderuje pasek postępu w miejscu użycia shortcode'u.
     *
     * @param array $atts Atrybuty shortcode'u (obecnie nieużywane).
     * @return string HTML paska postępu.
     */
    public function render_bar_shortcode(array $atts = []): string {
        // Shortcode po prostu zwraca ten sam, spójny HTML.
        return $this->generate_bar_html();
    }
}