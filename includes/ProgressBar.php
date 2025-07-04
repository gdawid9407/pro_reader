<?php
namespace ReaderEngagementPro;

class ProgressBar {

    public function __construct() {
        // Zostawiamy tylko akcje niezbędne do działania shortcode'u.
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('init',               [ $this, 'register_shortcodes' ]);
    }

    /**
     * ZMIANA: Skrypty i style będą ładowane tylko na stronach, 
     * które FAKTYCZNIE zawierają shortcode [progress_bar].
     */
    public function enqueue_assets() : void {
        global $post;

        // Sprawdzamy, czy jesteśmy na pojedynczym wpisie/stronie i czy zawiera on nasz shortcode.
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'progress_bar' ) ) {
            
            wp_enqueue_style(
                'rep-progress-style',
                REP_PLUGIN_URL . 'assets/css/style.css');
            wp_enqueue_script(
                'rep-progress-script',
                REP_PLUGIN_URL . 'assets/js/progress-bar.js',
                [], '1.1', true );

            // Przekazanie ustawień do skryptu JS pozostaje bez zmian.
            $opts     = get_option( 'reader_engagement_pro_options', [] );
            wp_localize_script(
                'rep-progress-script',
                'REP_Progress_Settings',
                [
                    'position'         => $opts['position'] ?? 'top',
                    'colorStart'       => $opts['color_start'] ?? '',
                    'colorEnd'         => $opts['color_end'] ?? '',
                    'opacity'          => $opts['opacity'] ?? '1.0',
                    'contentSelector'  => $opts['content_selector'] ?? '',
                    'showPercentage'   => $opts['show_percentage'] ?? '0',
                ]
            );
        }
    }

    /**
     * Generuje spójny kod HTML dla paska postępu. 
     * Ta funkcja pozostaje bez zmian.
     */
    private function generate_bar_html(): string {
        $opts = get_option('reader_engagement_pro_options', []);
        $posClass = ($opts['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $opacity = $opts['opacity'] ?? '1.0';
        $label_start = $opts['label_start'] ?? 'Start';
        $label_end   = $opts['label_end'] ?? 'Meta';
        $show_percentage = $opts['show_percentage'] ?? '0';
        $bar_height = $opts['bar_height'] ?? 20;
        $bar_width = $opts['bar_width'] ?? 100;

        $styles = [
            'opacity' => esc_attr($opacity),
            'height'  => esc_attr($bar_height) . 'px',
        ];

        if ($bar_width < 100) {
            $styles['width']     = esc_attr($bar_width) . '%';
            $styles['left']      = '50%';
            $styles['transform'] = 'translateX(-50%)';
        } else {
            $styles['left'] = '0';
            $styles['right'] = '0';
            $styles['width'] = 'auto';
            $styles['transform'] = 'none';
        }

        $style_attr = '';
        foreach ($styles as $key => $value) {
            $style_attr .= $key . ':' . $value . ';';
        }

        ob_start();
        ?>
        <div id="progress-bar-container-wrapper" class="proreader-container <?php echo esc_attr($posClass); ?>" style="<?php echo esc_attr($style_attr); ?>">
            <div id="progress-bar-gradient" class="proreader-gradient">
                <?php if ($show_percentage === '1') : ?>
                    <span id="rep-progress-percentage" style="line-height: <?php echo esc_attr($bar_height); ?>px;">0%</span>
                <?php endif; ?>
                <div id="progress-bar" class="proreader-bar"></div>
            </div>
            <div class="proreader-labels">
                 <span class="label-start"><?php echo esc_html($label_start); ?></span>
                <span class="label-end"><?php echo esc_html($label_end); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function register_shortcodes(): void {
        add_shortcode('progress_bar', [$this, 'render_bar_shortcode']);
    }

    /**
     * ZMIANA: Shortcode nie zwraca już bezpośrednio HTML.
     * Zamiast tego zwraca skrypt, który wstrzykuje HTML paska we właściwe miejsce na stronie.
     *
     * @param array $atts Atrybuty shortcode'u.
     * @return string Pusty string lub skrypt JS.
     */
    public function render_bar_shortcode(array $atts = []): string {
        // Sprawdzamy, czy pasek nie został już dodany na tej stronie.
        static $is_rendered = false;
        if ($is_rendered) {
            return ''; // Zapobiegamy dodaniu paska więcej niż raz.
        }

        // Pobieramy gotowy HTML paska.
        $bar_html = $this->generate_bar_html();
        // Kodujemy go do formatu JSON, aby bezpiecznie umieścić w stringu JS.
        $bar_html_json = json_encode($bar_html);
        
        // Zaznaczamy, że pasek został już dodany.
        $is_rendered = true;

        // Zwracamy skrypt. Nie zwróci on nic widocznego w miejscu shortcode'u.
        // Jego zadaniem jest umieszczenie paska na górze strony.
        return "<script type='text/javascript'>
            (function() {
                // Sprawdzamy, czy inny skrypt już nie dodał paska.
                if (document.getElementById('progress-bar-container-wrapper')) {
                    return;
                }
                
                var barHtml = {$bar_html_json};
                if (barHtml) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = barHtml.trim();
                    var barElement = tempDiv.firstChild;

                    if (barElement && document.body) {
                        document.body.prepend(barElement);
                    }
                }
            })();
        </script>";
    }
    
    // USUNIĘTO NIEPOTRZEBNE METODY: render_bar i render_global_bar_conditionally.
}