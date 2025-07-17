<?php

namespace ReaderEngagementPro;

if (!defined('ABSPATH')) {
    exit;
}

class ProgressBar
{
    private array $options = [];
    private bool $should_render = false;
    private static bool $assets_enqueued = false;

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);

        if (empty($this->options['progress_bar_enable']) || $this->options['progress_bar_enable'] !== '1') {
            return;
        }

        add_action('wp', [$this, 'decide_to_render']);
    }

    public function decide_to_render(): void
    {
        $display_on = $this->options['progress_bar_display_on'] ?? [];
        
        // === ZMIANA 1: Sprawdzenie, czy opcje wyświetlania nie są puste ===
        if (empty($display_on)) {
            return;
        }
        // === KONIEC ZMIANY 1 ===

        if (is_singular($display_on)) {
            $this->should_render = true;
            
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_footer', [$this, 'render_bar_in_footer']);
        }
    }
    
    // === ZMIANA 2: Zmiana widoczności z 'private' na 'public' ===
    public function enqueue_assets(): void
    {
        if (!$this->should_render || self::$assets_enqueued) {
            return;
        }

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

        wp_localize_script(
            'rep-progress-script',
            'REP_Progress_Settings',
            [
                'position'           => $this->options['position'] ?? 'top',
                'colorStart'         => $this->options['color_start'] ?? '',
                'colorEnd'           => $this->options['color_end'] ?? '',
                'opacity'            => $this->options['opacity'] ?? '1.0',
                'contentSelector'    => $this->options['content_selector'] ?? '',
                'showPercentage'     => $this->options['show_percentage'] ?? '0',
                'percentagePosition' => $this->options['percentage_position'] ?? 'center',
            ]
        );
        
        self::$assets_enqueued = true;
    }

    public function render_bar_in_footer(): void
    // === KONIEC ZMIANY 2 ===
    {
        if ($this->should_render) {
            echo $this->generate_bar_html();
        }
    }

    private function generate_bar_html(): string
    {
        $posClass        = ($this->options['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $opacity         = $this->options['opacity'] ?? '1.0';
        $label_start     = $this->options['label_start'] ?? 'Start';
        $label_end       = $this->options['label_end'] ?? 'Meta';
        $show_percentage = $this->options['show_percentage'] ?? '0';
        $bar_height      = $this->options['bar_height'] ?? 20;
        $bar_width       = $this->options['bar_width'] ?? 100;

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