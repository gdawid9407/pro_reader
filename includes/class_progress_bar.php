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
            REP_PLUGIN_URL . 'assets/css/style.css'
        );
        wp_enqueue_script(
            'rep-progress-script',
            REP_PLUGIN_URL . 'assets/js/progress-bar.js',
            [], '1.0', true
            
        );

        // Przekazanie ustawień do skryptu JS
        $opts     = get_option( 'reader_engagement_pro_options', [] );
        wp_localize_script(
            'rep-progress-script',
            'REP_Progress_Settings',
            [
                'position'         => $opts['position']         ?? 'top',
                'color'            => $opts['color']            ?? '#000',
                'opacity'          => $opts['opacity']          ?? '1.0',
                'excludeSelectors' => $opts['exclude_selectors'] ?? '',
            ]
        );
    }

    public function render_bar() {
        // Renderowanie paska w stopce strony
        $opts     = get_option('reader_engagement_pro_options', []);
        $posClass = ($opts['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $color    = $opts['color'] ?? '#000';
        $opacity  = $opts['opacity'] ?? '1.0';

        printf(
            '<div id="progress-bar-container" class="proreader-container %s" style="opacity:%s">
                <div id="progress-bar" class="proreader-bar" style="background-color:%s"></div>
            </div>',
            esc_attr($posClass),
            esc_attr($opacity),
            esc_attr($color)
        );
    }

    public function register_shortcodes() : void{
        // Rejestracja shortcode [progress_bar]
        add_shortcode('progress_bar', [ $this, 'render_bar_shortcode' ]);
    }

   public function render_bar_shortcode( array $atts = [] ) : string {
        $opts     = get_option( 'reader_engagement_pro_options', [] );
        $posClass = ( $opts['position'] ?? 'top' ) === 'bottom'
            ? 'position-bottom'
            : 'position-top';

        $color   = $opts['color']   ?? '#000';
        $opacity = $opts['opacity'] ?? '1.0';

        ob_start(); ?>
        <div id="progress-bar-container"
             class="proreader-container <?php echo esc_attr( $posClass ); ?>"
             style="opacity:<?php echo esc_attr( $opacity ); ?>;">
            <div id="progress-bar"
                 class="proreader-bar"
                 style="background-color:<?php echo esc_attr( $color ); ?>;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

