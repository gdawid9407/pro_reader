<?php

namespace ReaderEngagementPro\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa odpowiedzialna za logikę i wyświetlanie paska postępu na froncie.
 */
class ProgressBar
{
    private array $options = [];
    private bool $should_render = false;
    private static bool $assets_enqueued = false;

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);

        // Zakończ, jeśli moduł paska postępu jest wyłączony w opcjach.
        if (empty($this->options['progress_bar_enable']) || $this->options['progress_bar_enable'] !== '1') {
            return;
        }

        // Podepnij metodę decyzyjną do haka 'wp'.
        add_action('wp', [$this, 'decide_to_render']);
    }

    /**
     * Decyduje, czy pasek postępu powinien być wyświetlony na bieżącej stronie.
     */
    public function decide_to_render(): void
    {
        $display_on = $this->options['progress_bar_display_on'] ?? [];

        if (empty($display_on)) {
            return;
        }

        // Sprawdź, czy bieżący typ treści jest na liście dozwolonych.
        if (is_singular($display_on)) {
            $this->should_render = true;
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_footer', [$this, 'render_bar_in_footer']);
        }
    }

    /**
     * Rejestruje skrypty i style potrzebne dla paska postępu.
     */
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
            '1.1.0', // Zaktualizowana wersja
            true
        );

        // Przekaż ustawienia z PHP do JavaScript.
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

    /**
     * Renderuje pasek postępu w stopce strony, korzystając z pliku szablonu.
     */
    public function render_bar_in_footer(): void
    {
        if (!$this->should_render) {
            return;
        }

        // Przygotuj zmienne dla szablonu.
        $posClass        = ($this->options['position'] ?? 'top') === 'bottom' ? 'position-bottom' : 'position-top';
        $opacity         = $this->options['opacity'] ?? '1.0';
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

        // Przekaż zmienne do zasięgu szablonu.
        $template_vars = [
            'posClass'        => $posClass,
            'style_attr'      => $style_attr,
            'show_percentage' => $this->options['show_percentage'] ?? '0',
            'bar_height'      => $bar_height,
            'label_start'     => $this->options['label_start'] ?? 'Start',
            'label_end'       => $this->options['label_end'] ?? 'Meta',
        ];

        // Wyodrębnij zmienne do lokalnego zasięgu i załaduj szablon.
        extract($template_vars);
        include REP_PLUGIN_PATH . 'src/Templates/progress-bar/bar.php';
    }
}