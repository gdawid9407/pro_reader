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

        if (empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1') {
            return;
        }

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
     * Renderuje popup w stopce, przekazując ustawienia jako zmienne CSS.
     */
    public function render_popup_in_footer(): void
     {
        if (!$this->should_render) {
            return;
        }

        // Zawsze generuj klasy i układy dla desktopu jako podstawę.
        $layout_class   = 'layout-' . sanitize_html_class($this->options['popup_recommendations_layout'] ?? 'list');
        $item_layout    = $this->options['popup_rec_item_layout'] ?? 'vertical';
        
        // Pobierz ustawienia mobilne, aby przekazać je do atrybutów data-*.
        $layout_mobile  = $this->options['popup_recommendations_layout_mobile'] ?? 'list';
        $item_layout_mobile = $this->options['popup_rec_item_layout_mobile'] ?? 'vertical';

        $popup_content  = $this->options['popup_content_main'] ?? '';
        $button_width_class = 'btn-width-' . sanitize_html_class($this->options['popup_rec_button_width'] ?? 'compact');

        // --- POCZĄTEK ZMIAN ---
        // Zaktualizowano generowanie paddingu dla desktopa.
        $padding_y_desktop = $this->options['popup_padding_y_desktop'] ?? 24;
        $padding_x_desktop = $this->options['popup_padding_x_desktop'] ?? 32;

        $styles = [
            // Ustawienia Desktop (z fallbackami)
            '--rep-popup-max-width'         => ($this->options['popup_max_width'] ?? 800) . 'px',
            '--rep-popup-max-height'        => ($this->options['popup_max_height'] ?? 90) . 'vh',
            '--rep-popup-padding'           => "{$padding_y_desktop}px {$padding_x_desktop}px",
            '--rep-content-margin-bottom'   => ($this->options['popup_margin_content_bottom'] ?? 20) . 'px',
            '--rep-list-item-gap'           => ($this->options['popup_gap_list_items'] ?? 16) . 'px',
            '--rep-grid-item-gap'           => ($this->options['popup_gap_grid_items'] ?? 24) . 'px',
             '--rep-rec-thumb-margin-right'  => ($this->options['popup_rec_thumb_margin_right'] ?? 16) . 'px',
            '--rep-rec-thumb-margin-bottom' => ($this->options['popup_rec_thumb_margin_right'] ?? 16) . 'px',
            '--rep-rec-thumb-width-horizontal' => ($this->options['popup_rec_thumb_width_horizontal'] ?? 200) . 'px',
            '--rep-btn-border-radius'       => ($this->options['popup_rec_button_border_radius'] ?? 4) . 'px',
            
            // Odstępy wewnątrz komponentu
            '--rep-rec-meta-margin-bottom'    => ($this->options['popup_rec_margin_meta_bottom'] ?? 8) . 'px',
            '--rep-rec-title-margin-bottom'   => ($this->options['popup_rec_margin_title_bottom'] ?? 12) . 'px',
            '--rep-rec-excerpt-margin-bottom' => ($this->options['popup_rec_margin_excerpt_bottom'] ?? 12) . 'px',

            // Ustawienia Mobilne (z fallbackami)
            '--rep-popup-width-mobile'      => ($this->options['popup_max_width_mobile'] ?? 90) . 'vw',
            '--rep-popup-padding-mobile'    => ($this->options['popup_padding_container_mobile'] ?? 16) . 'px',
        ];
        // --- KONIEC ZMIAN ---

        $container_styles = '';
        foreach ($styles as $key => $value) {
            $container_styles .= esc_attr($key) . ':' . esc_attr($value) . ';';
        }

        $template_vars = [
            'layout_class'     => $layout_class,
            'popup_content'    => $popup_content,
            'container_styles' => $container_styles,
            'item_layout'      => $item_layout,
            'components_order' => $this->options['popup_rec_components_order'] ?? ['thumbnail', 'meta', 'title', 'excerpt', 'link'],
            'components_visibility' => $this->options['popup_rec_components_visibility'] ?? array_fill_keys(['thumbnail', 'meta', 'title', 'excerpt', 'link'], '1'),
            'layout_mobile'    => $layout_mobile,
            'item_layout_mobile' => $item_layout_mobile,
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

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.3.0'); // Bump wersji
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
     */
    public function generate_recommendation_item_html(int $post_id): string
    {
        // Zawsze renderuj strukturę DOM dla ustawień desktopowych.
        // JS na froncie zajmie się jej zmianą dla widoku mobilnego.
        $item_layout = $this->options['popup_rec_item_layout'] ?? 'vertical';
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $this->options['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $this->options['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');
        $components_html = [];

        foreach ($default_order as $component_key) { // Użyj domyślnej kolejności, aby upewnić się, że wszystkie komponenty są dostępne
            if (!empty($components_visibility[$component_key])) {
                $components_html[$component_key] = $this->get_component_html($component_key, $post_id);
            }
        }
        
        $item_class = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);

        ob_start();

        extract([
            'post_id' => $post_id,
            'item_class' => $item_class,
            'item_layout' => $item_layout,
            'components_order' => $components_order,
            'components_html' => $components_html
        ]);

        include REP_PLUGIN_PATH . 'src/Templates/popup/recommendation-item.php';
        
        return ob_get_clean();
    }

    /**
     * Generuje HTML dla konkretnego komponentu.
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
                $bg_color = $this->options['popup_rec_button_bg_color'] ?? '#0073aa';
                $text_color = $this->options['popup_rec_button_text_color'] ?? '#ffffff';
                $bg_hover = $this->options['popup_rec_button_bg_hover_color'] ?? '#005177';
                $text_hover = $this->options['popup_rec_button_text_hover_color'] ?? '#ffffff';
                $button_width_class = 'btn-width-' . sanitize_html_class($this->options['popup_rec_button_width'] ?? 'compact');
                
                $style_vars = sprintf(
                    '--rep-btn-bg: %s; --rep-btn-text: %s; --rep-btn-bg-hover: %s; --rep-btn-text-hover: %s;',
                    esc_attr($bg_color), esc_attr($text_color), esc_attr($bg_hover), esc_attr($text_hover)
                );
                return sprintf('<a href="%s" class="rep-rec-button %s" style="%s">%s</a>', esc_url($post_link), esc_attr($button_width_class), $style_vars, wp_kses_post($link_text));
            default:
                return '';
        }
    }

    /**
     * Przetwarza zajawkę posta zgodnie z ustawieniami.
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