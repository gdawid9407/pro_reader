<?php

namespace ReaderEngagementPro\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zarządza logiką i wyświetlaniem popupa na froncie.
 */
class Popup
{
    private array $options = [];
    private bool $should_render = false;
    private static bool $assets_enqueued = false;
    private array $appearance_settings = [];

    public function __construct()
    {
        $this->options = get_option('reader_engagement_pro_options', []);

        if (empty($this->options['popup_enable']) || $this->options['popup_enable'] !== '1') {
            return;
        }

        $this->prepare_settings();

        add_action('wp', [$this, 'decide_to_render']);
    }

    /**
     * Przygotowuje ustawienia wyglądu, uwzględniając urządzenie i szablony.
     */
    private function prepare_settings(): void
    {
        $is_mobile = wp_is_mobile();
        $device_key = $is_mobile ? 'mobile' : 'desktop';
        $fallback_key = $is_mobile ? 'desktop' : 'mobile';

        // 1. Ustaw domyślne ustawienia na podstawie klucza urządzenia
        $this->appearance_settings = $this->options[$device_key] ?? [];

        // 2. Jeśli brakuje ustawień dla danego urządzenia, użyj ustawień z drugiego
        if (empty($this->appearance_settings)) {
            $this->appearance_settings = $this->options[$fallback_key] ?? [];
        }

        // 3. Wczytaj ustawienia szablonu, jeśli został wybrany
        $template_slug = $this->options['popup_appearance_template'] ?? 'custom';
        if (in_array($template_slug, ['template_1', 'template_2'])) {
            $template_id = str_replace('template_', '', $template_slug);
            $template_option_name = 'reader_engagement_pro_template_' . $device_key . '_' . $template_id;
            $template_options = get_option($template_option_name, []);

            if (!empty($template_options)) {
                $this->appearance_settings = array_merge($this->appearance_settings, $template_options);
            }
        }
    }

    /**
     * Decyduje, czy popup powinien być wyświetlony.
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
     * Renderuje popup w stopce i przekazuje ustawienia jako zmienne CSS.
     */
    public function render_popup_in_footer(): void
     {
        if (!$this->should_render) {
            return;
        }

        $settings = $this->appearance_settings;

        $layout_class   = 'layout-' . sanitize_html_class($settings['popup_recommendations_layout'] ?? 'list');
        $item_layout    = $settings['popup_rec_item_layout'] ?? 'vertical';
        
        $popup_content  = $this->options['popup_content_main'] ?? '';
        $button_width_class = 'btn-width-' . sanitize_html_class($settings['popup_rec_button_width'] ?? 'compact');

        $padding_y = $settings['popup_padding_y'] ?? 24;
        $padding_x = $settings['popup_padding_x'] ?? 32;

        $styles = [
            '--rep-popup-max-width'         => ($settings['popup_max_width'] ?? 800) . 'px',
            '--rep-popup-max-height'        => ($settings['popup_max_height'] ?? 90) . 'vh',
            '--rep-popup-padding'           => "{$padding_y}px {$padding_x}px",
            '--rep-content-margin-bottom'   => ($settings['popup_margin_content_bottom'] ?? 20) . 'px',
            '--rep-list-item-gap'           => ($settings['popup_gap_list_items'] ?? 16) . 'px',
            '--rep-grid-item-gap'           => ($settings['popup_gap_grid_items'] ?? 24) . 'px',
            '--rep-grid-item-width'         => ($settings['popup_grid_item_width'] ?? 234) . 'px',
            '--rep-rec-thumb-margin-right'  => ($settings['popup_rec_thumb_margin_right'] ?? 16) . 'px',
            '--rep-rec-thumb-margin-bottom' => ($settings['popup_rec_thumb_margin_right'] ?? 16) . 'px',
            '--rep-rec-thumb-width-horizontal' => ($settings['popup_rec_thumb_width_horizontal'] ?? 200) . 'px',
            '--rep-rec-thumb-width-list-vertical' => ($settings['popup_rec_thumb_width_list_vertical'] ?? 100) . '%',
            '--rep-btn-border-radius'       => ($settings['popup_rec_button_border_radius'] ?? 4) . 'px',
            
            '--rep-rec-meta-margin-bottom'    => ($settings['popup_rec_margin_meta_bottom'] ?? 8) . 'px',
            '--rep-rec-title-margin-bottom'   => ($settings['popup_rec_margin_title_bottom'] ?? 12) . 'px',
            '--rep-rec-excerpt-margin-bottom' => ($settings['popup_rec_margin_excerpt_bottom'] ?? 12) . 'px',
        ];

        $container_styles = '';
        foreach ($styles as $key => $value) {
            $container_styles .= esc_attr($key) . ':' . esc_attr($value) . ';';
        }

        $template_vars = [
            'layout_class'     => $layout_class,
            'popup_content'    => $popup_content,
            'container_styles' => $container_styles,
            'item_layout'      => $item_layout,
            'components_order' => $settings['popup_rec_components_order'] ?? ['thumbnail', 'meta', 'title', 'excerpt', 'link'],
            'components_visibility' => $settings['popup_rec_components_visibility'] ?? array_fill_keys(['thumbnail', 'meta', 'title', 'excerpt', 'link'], '1'),
        ];

        extract($template_vars);
        include REP_PLUGIN_PATH . 'src/Templates/popup/main-popup.php';
    }

    /**
     * Rejestruje skrypty i style dla popupa.
     */
    public function enqueue_assets(): void
    {
        if (!$this->should_render || self::$assets_enqueued) {
            return;
        }

        wp_enqueue_style('rep-popup-style', REP_PLUGIN_URL . 'assets/css/popup.css', [], '1.4.0');
        wp_enqueue_style('rep-popup-mobile-style', REP_PLUGIN_URL . 'assets/css/popup-mobile.css', ['rep-popup-style'], '1.4.0');
        wp_enqueue_script('rep-popup-script', REP_PLUGIN_URL . 'assets/js/popup.js', ['jquery'], '1.2.0', true);

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
        $settings = $this->appearance_settings;
        $item_layout = $settings['popup_rec_item_layout'] ?? 'vertical';
        $default_order = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $components_order = $settings['popup_rec_components_order'] ?? $default_order;
        $components_visibility = $settings['popup_rec_components_visibility'] ?? array_fill_keys($default_order, '1');
        $components_html = [];

        foreach ($default_order as $component_key) {
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
        $settings = $this->appearance_settings;
        
        switch ($key) {
            case 'thumbnail':
                $thumb_size = $settings['popup_rec_thumb_size'] ?? 'medium';
                $thumb_fit = $settings['popup_rec_thumb_fit'] ?? 'cover';
                $aspect_ratio = $settings['popup_rec_thumb_aspect_ratio'] ?? '16:9';
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
                $limit_type = $settings['popup_rec_excerpt_limit_type'] ?? 'words';
                $style_attr = '';
                if ($limit_type === 'lines') {
                    $line_clamp = $settings['popup_rec_excerpt_lines'] ?? 3;
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
                $link_text = $this->options['popup_recommendations_link_text'] ?? 'Zobacz więcej →'; // Global setting
                $bg_color = $settings['popup_rec_button_bg_color'] ?? '#0073aa';
                $text_color = $settings['popup_rec_button_text_color'] ?? '#ffffff';
                $bg_hover = $settings['popup_rec_button_bg_hover_color'] ?? '#005177';
                $text_hover = $settings['popup_rec_button_text_hover_color'] ?? '#ffffff';
                $button_width_class = 'btn-width-' . sanitize_html_class($settings['popup_rec_button_width'] ?? 'compact');
                
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

        $settings = $this->appearance_settings;
        $limit_type = $settings['popup_rec_excerpt_limit_type'] ?? 'words';
        if ($limit_type === 'words') {
            $length = $settings['popup_rec_excerpt_length'] ?? 15;
            if ($length > 0) {
                return wp_trim_words($raw_excerpt, $length, '...');
            }
        }
        return $raw_excerpt;
    }
}