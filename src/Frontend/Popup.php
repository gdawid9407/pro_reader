<?php

namespace ReaderEngagementPro\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa odpowiedzialna za logikę i wyświetlanie popupa na froncie.
 */
class Popup
{
    private array $options = [];
    private bool $should_render = false;
    private static bool $assets_enqueued = false;

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);

        // Zakończ, jeśli moduł popupa jest wyłączony.
        if (empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1') {
            return;
        }
        // Właściwa obsługa AJAX jest teraz w AjaxHandler, więc usuwamy stąd hooki.

        // Podepnij metodę decyzyjną do haka 'wp'.
        add_action('wp', [$this, 'decide_to_render']);
    }

    /**
     * Decyduje, czy popup powinien być wyświetlony na bieżącej stronie.
     */
    public function decide_to_render(): void
    {
        $display_on = $this->options['popup_display_on'] ?? [];
        if (empty($display_on)) {
            return;
        }

        if (is_singular($display_on)) {
            $this->should_render = true;
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_footer', [$this, 'render_popup_in_footer']);
        }
    }

    /**
     * Renderuje główny kontener popupa w stopce, korzystając z szablonu.
     */
    public function render_popup_in_footer(): void
    {
        if (!$this->should_render) {
            return;
        }

        $template_vars = [
            'popup_content'  => $this->options['popup_content_main'] ?? '',
            'layout_class'   => 'layout-' . sanitize_html_class($this->options['popup_recommendations_layout'] ?? 'list'),
        ];

        extract($template_vars);
        include REP_PLUGIN_PATH . 'src/Templates/popup/main-popup.php';
    }

    /**
     * Rejestruje skrypty i style potrzebne dla popupa.
     */
    public function enqueue_assets(): void
    {
        if (!$this->should_render || self::$assets_enqueued) {
            return;
        }

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.1.0');
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.1.0', true);

        wp_localize_script(
            'rep-popup-script',
            'REP_Popup_Settings',
            [
                'popupEnable'                  => $this->options['popup_enable'] ?? '0',
                'triggerByScrollPercentEnable' => $this->options['popup_trigger_scroll_percent_enable'] ?? '1',
                'triggerByScrollPercent'       => $this->options['popup_trigger_scroll_percent'] ?? 85,
                'triggerByTime'                => $this->options['popup_trigger_time'] ?? 60,
                'triggerByScrollUp'            => $this->options['popup_trigger_scroll_up'] ?? '0',
                'ajaxUrl'                      => admin_url('admin-ajax.php'),
                'nonce'                        => wp_create_nonce('rep_recommendations_nonce'),
                'currentPostId'                => is_singular() ? get_the_ID() : 0,
            ]
        );

        self::$assets_enqueued = true;
    }

    /**
     * Generuje HTML dla pojedynczego elementu rekomendacji.
     * Ta metoda jest publiczna, aby AjaxHandler mógł z niej korzystać.
     *
     * @param int $post_id ID posta do wyrenderowania.
     * @return string HTML elementu <li>.
     */
    public function generate_recommendation_item_html(int $post_id): string
    {
        $item_layout = $this->options['popup_rec_item_layout'] ?? 'vertical';
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $this->options['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $this->options['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');
        $components_html = [];

        foreach ($components_order as $component_key) {
            if (!empty($components_visibility[$component_key])) {
                $components_html[$component_key] = $this->get_component_html($component_key, $post_id);
            }
        }
        
        $item_class = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);

        ob_start();
        
        // Przekaż zmienne i załaduj szablon elementu.
        extract([
            'item_class'        => $item_class,
            'item_layout'       => $item_layout,
            'components_html'   => $components_html,
            'components_order'  => $components_order,
        ]);
        include REP_PLUGIN_PATH . 'src/Templates/popup/recommendation-item.php';

        return ob_get_clean();
    }

    /**
     * Generuje HTML dla konkretnego komponentu (np. tytuł, obrazek).
     *
     * @param string $key Nazwa komponentu.
     * @param int $post_id ID posta.
     * @return string Kod HTML komponentu.
     */
    private function get_component_html(string $key, int $post_id): string
    {
        $post_link = get_permalink($post_id);
        
        switch ($key) {
            case 'thumbnail':
                $thumb_size = $this->options['popup_rec_thumb_size'] ?? 'medium';
                $thumb_fit = $this->options['popup_rec_thumb_fit'] ?? 'cover';
                $aspect_ratio = $this->options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
                $image_attrs = ['class' => 'rep-rec-thumb thumb-fit-' . sanitize_html_class($thumb_fit)];
                $thumbnail_html = get_the_post_thumbnail($post_id, $thumb_size, $image_attrs);
                if (empty($thumbnail_html)) {
                    $placeholder_url = REP_PLUGIN_URL . 'assets/images/placeholder.png';
                    $thumbnail_html = sprintf('<img src="%s" alt="" class="rep-rec-thumb rep-rec-thumb-placeholder">', esc_url($placeholder_url));
                }
                $link_style = ($aspect_ratio !== 'auto') ? 'aspect-ratio: ' . str_replace(':', ' / ', $aspect_ratio) . ';' : '';
                return sprintf('<a href="%s" class="rep-rec-thumb-link" style="%s">%s</a>', esc_url($post_link), esc_attr($link_style), $thumbnail_html);
            case 'title':
                return sprintf('<h3 class="rep-rec-title"><a href="%s">%s</a></h3>', esc_url($post_link), esc_html(get_the_title($post_id)));
            case 'excerpt':
                $excerpt = $this->get_processed_excerpt($post_id);
                if (empty($excerpt)) { return ''; }
                $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
                $style_attr = '';
                if ($limit_type === 'lines') {
                    $line_clamp = $this->options['popup_rec_excerpt_lines'] ?? 3;
                    $style_attr = ($line_clamp > 0) ? sprintf('style="-webkit-line-clamp: %d;"', esc_attr($line_clamp)) : '';
                }
                return sprintf('<p class="rep-rec-excerpt" %s>%s</p>', $style_attr, esc_html($excerpt));
            case 'meta':
                $date_html = get_the_date('j F, Y', $post_id);
                $category_html = '';
                $categories = get_the_category($post_id);
                if (!empty($categories)) {
                    $category_html = ' <span class="rep-rec-meta-separator">•</span> <span class="rep-rec-category">' . esc_html($categories[0]->name) . '</span>';
                }
                return sprintf('<p class="rep-rec-meta"><span class="rep-rec-date">%s</span>%s</p>', esc_html($date_html), $category_html);
            case 'link':
                $link_text = $this->options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';
                $style_vars = sprintf(
                    '--rep-btn-bg: %s; --rep-btn-text: %s; --rep-btn-bg-hover: %s; --rep-btn-text-hover: %s; border-radius: %dpx;',
                    esc_attr($this->options['popup_rec_button_bg_color'] ?? '#0073aa'),
                    esc_attr($this->options['popup_rec_button_text_color'] ?? '#ffffff'),
                    esc_attr($this->options['popup_rec_button_bg_hover_color'] ?? '#005177'),
                    esc_attr($this->options['popup_rec_button_text_hover_color'] ?? '#ffffff'),
                    esc_attr($this->options['popup_rec_button_border_radius'] ?? 4)
                );
                return sprintf('<a href="%s" class="rep-rec-button" style="%s">%s</a>', esc_url($post_link), $style_vars, wp_kses_post($link_text));
            default:
                return '';
        }
    }

    /**
     * Przetwarza zajawkę posta zgodnie z ustawieniami (limit słów).
     *
     * @param int $post_id ID posta.
     * @return string Przetworzona zajawka.
     */
    private function get_processed_excerpt(int $post_id): string
    {
        $raw_excerpt = get_the_excerpt($post_id);
        if (empty($raw_excerpt)) { return ''; }

        $limit_type = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
        if ($limit_type === 'words') {
            $length = $this->options['popup_rec_excerpt_length'] ?? 15;
            if ($length > 0) {
                return wp_trim_words($raw_excerpt, $length, '...');
            }
        }
        return $raw_excerpt;
    }
}