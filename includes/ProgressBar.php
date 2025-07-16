<?php

namespace ReaderEngagementPro;

if (!defined('ABSPATH')) {
    exit;
}

class ProgressBar
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * Warunkowo ładuje zasoby tylko na stronach zawierających shortcode.
     */
    public function enqueue_assets(): void
    {
        global $post;

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'progress_bar')) {
            wp_enqueue_style(
                'rep-progress-style',
                REP_PLUGIN_URL . 'assets/css/progress-bar.css'
            );

            wp_enqueue_script(
                'rep-progress-script',
                REP_PLUGIN_URL . 'assets/js/progress-bar.js',
                [],
                '1.1',
                true
            );

            $options = get_option('reader_engagement_pro_options', []);
            wp_localize_script(
                'rep-progress-script',
                'REP_Progress_Settings',
                [
                    'position'           => $options['position'] ?? 'top',
                    'colorStart'         => $options['color_start'] ?? '',
                    'colorEnd'           => $options['color_end'] ?? '',
                    'opacity'            => $options['opacity'] ?? '1.0',
                    'contentSelector'    => $options['content_selector'] ?? '',
                    'showPercentage'     => $options['show_percentage'] ?? '0',
                    'percentagePosition' => $options['percentage_position'] ?? 'center',
                ]
            );
        }
    }

    public function register_shortcodes(): void
    {
        add_shortcode('progress_bar', [$this, 'render_bar_shortcode']);
    }

    /**
     * Renderuje shortcode, zwracając skrypt JS, który wstrzykuje HTML paska na stronę.
     * Zapobiega to problemom z renderowaniem wewnątrz treści filtrowanej przez motywy.
     */
    public function render_bar_shortcode(array $atts = []): string
    {
        static $is_rendered = false;
        if ($is_rendered) {
            return ''; // Zapobiega wielokrotnemu renderowaniu paska.
        }

        $bar_html      = $this->generate_bar_html();
        $bar_html_json = json_encode($bar_html);
        $is_rendered   = true;

        // Skrypt JS umieszcza pasek na początku elementu <body>
        return "<script type='text/javascript'>
            (function() {
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

    /**
     * Generuje finalny kod HTML dla paska postępu na podstawie opcji.
     */
    private function generate_bar_html(): string
    {
        $options = get_option('reader_engagement_pro_options', []);

        $posClass        = ($options['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $opacity         = $options['opacity'] ?? '1.0';
        $label_start     = $options['label_start'] ?? 'Start';
        $label_end       = $options['label_end'] ?? 'Meta';
        $show_percentage = $options['show_percentage'] ?? '0';
        $bar_height      = $options['bar_height'] ?? 20;
        $bar_width       = $options['bar_width'] ?? 100;

        $styles = [
            'opacity' => esc_attr($opacity),
            'height'  => esc_attr($bar_height) . 'px',
        ];

        if ($bar_width < 100) {
            $styles['width']     = esc_attr($bar_width) . '%';
            $styles['left']      = '50%';
            $styles['transform'] = 'translateX(-50%)';
        } else {
            $styles['width'] = '100%';
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

                <div class="proreader-labels">
                    <span class="label-start"><?php echo esc_html($label_start); ?></span>
                    <span class="label-end"><?php echo esc_html($label_end); ?></span>
                </div>
                
                <div id="progress-bar" class="proreader-bar"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}