<?php
namespace ReaderEngagementPro;

class Class_Progress_Bar {
    public function __construct() {
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_assets' ]);
        add_action('wp_footer',          [ $this, 'render_bar' ]);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'rep-progress-style',
            plugin_dir_url(__FILE__).'../assets/css/style.css'
        );
        wp_enqueue_script(
            'rep-progress-script',
            plugin_dir_url(__FILE__).'../assets/js/progress-bar.js',
            [], '1.0', true
        );
        $opts = get_option('reader_engagement_pro_options', []);
        wp_localize_script(
            'rep-progress-script',
            'REP_Progress_Settings',
            [
                'position'         => $opts['position']          ?? 'top',
                'color'            => $opts['color']             ?? '#000',
                'opacity'          => $opts['opacity']           ?? '1.0',
                'excludeSelectors' => $opts['exclude_selectors'] ?? '',
            ]
        );
    }

    public function render_bar() {
        $opts = get_option('reader_engagement_pro_options', []);
        $position = ($opts['position'] ?? 'top') === 'bottom' ? 'bottom' : 'top';
        $color    = $opts['color']    ?? '#000';
        $opacity  = $opts['opacity']  ?? '1.0';

        echo sprintf(
            '<div id="progress-bar-container" style="position:fixed;%s:0;width:100%%;opacity:%s;">
                <div id="progress-bar" style="background:%s;width:0;height:4px;"></div>
            </div>',
            $position,
            $opacity,
            $color
        );
    }
}

