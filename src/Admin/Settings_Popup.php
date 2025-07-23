<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Settings_Popup
{
    private const OPTION_NAME = 'reader_engagement_pro_options';
    private array $options = [];

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME, []);
        add_action('admin_init', [$this, 'page_init']);
    }

    public function page_init(): void
    {
        $page = 'reader-engagement-pro-popup';

        // Sekcja 1: Wyzwalacze
        add_settings_section('popup_triggers_section', __('Ustawienia Wyzwalaczy i Widoczności', 'pro_reader'), null, $page);
        $this->register_trigger_fields($page, 'popup_triggers_section');

        // Sekcja 2: Treść
        add_settings_section('popup_content_section', __('Treść Popupa', 'pro_reader'), null, $page);
        add_settings_field('popup_content_main', __('Edytor treści', 'pro_reader'), [$this, 'content_main_callback'], $page, 'popup_content_section');
        add_settings_field('popup_recommendations_link_text', __('Treść linku "Czytaj dalej"', 'pro_reader'), [$this, 'recommendations_link_text_callback'], $page, 'popup_content_section');
        
        
        // Sekcja 3: Rekomendacje Ogólne
        add_settings_section('popup_recommendations_section', __('Ustawienia Ogólne Rekomendacji', 'pro_reader'), null, $page);
        $this->register_recommendation_fields($page, 'popup_recommendations_section');
        
        // Sekcja 4: Konstruktor Układu
        add_settings_section('popup_layout_builder_section', __('Konstruktor Układu Rekomendacji', 'pro_reader'), null, $page);
        $this->register_layout_builder_fields($page, 'popup_layout_builder_section');
        
        // sekcja odstepów 
        add_settings_section('popup_layout_spacing_section', __('Układ i Odstępy', 'pro_reader'), null, $page);
        $this->register_spacing_fields($page, 'popup_layout_spacing_section');
        
        // Sekcja 5: Ustawienia przycisku
        add_settings_section('popup_button_settings_section', __('Ustawienia Przycisku "Czytaj dalej"', 'pro_reader'), null, $page);
        $this->register_button_fields($page, 'popup_button_settings_section');

        // Sekcja 6: Ustawienia Miniaturki
        add_settings_section('popup_thumbnail_settings_section', __('Ustawienia Miniaturki', 'pro_reader'), null, $page);
        $this->register_thumbnail_fields($page, 'popup_thumbnail_settings_section');
    
    }
    

    private function register_trigger_fields(string $page, string $section): void
    {
        add_settings_field('popup_enable', __('Włącz Moduł Popup', 'pro_reader'), [$this, 'enable_callback'], $page, $section);
        add_settings_field('popup_display_on', __('Wyświetlaj na', 'pro_reader'), [$this, 'display_on_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_percent_enable', __('Wyzwalacz: Procent przewinięcia', 'pro_reader'), [$this, 'trigger_scroll_percent_enable_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_percent', __('Wartość procentowa', 'pro_reader'), [$this, 'trigger_scroll_percent_callback'], $page, $section);
        add_settings_field('popup_trigger_time', __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'), [$this, 'trigger_time_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_up', __('Wyzwalacz: Scroll w górę', 'pro_reader'), [$this, 'trigger_scroll_up_callback'], $page, $section);
    }

    private function register_spacing_fields(string $page, string $section): void
{
    add_settings_field('popup_padding_container', __('Padding kontenera (px)', 'pro_reader'), [$this, 'padding_container_callback'], $page, $section);
    add_settings_field('popup_margin_content_bottom', __('Odstęp pod treścią (px)', 'pro_reader'), [$this, 'margin_content_bottom_callback'], $page, $section);
    add_settings_field('popup_gap_list_items', __('Odstęp między elementami - Lista (px)', 'pro_reader'), [$this, 'gap_list_items_callback'], $page, $section);
    add_settings_field('popup_gap_grid_items', __('Odstęp między elementami - Siatka (px)', 'pro_reader'), [$this, 'gap_grid_items_callback'], $page, $section);
    add_settings_field('popup_spacing_reset', '', [$this, 'spacing_reset_callback'], $page, $section);
}

    private function register_recommendation_fields(string $page, string $section): void
    {
        add_settings_field('popup_recommendations_count', __('Liczba wpisów', 'pro_reader'), [$this, 'recommendations_count_callback'], $page, $section);
        add_settings_field('popup_recommendation_post_types', __('Źródło rekomendacji', 'pro_reader'), [$this, 'recommendation_post_types_callback'], $page, $section);
        add_settings_field('popup_recommendation_logic', __('Kolejność rekomendacji', 'pro_reader'), [$this, 'recommendation_logic_callback'], $page, $section);
        add_settings_field('popup_recommendations_layout', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], $page, $section);
    }

    private function register_layout_builder_fields(string $page, string $section): void
    {
        add_settings_field('popup_rec_item_layout', __('Struktura elementu', 'pro_reader'), [$this, 'item_layout_callback'], $page, $section);
        add_settings_field('popup_rec_components_order', __('Kolejność i widoczność elementów', 'pro_reader'), [$this, 'components_order_callback'], $page, $section);
        add_settings_field('popup_rec_excerpt_limit_type', __('Typ limitu zajawki', 'pro_reader'), [$this, 'excerpt_limit_type_callback'], $page, $section);
        add_settings_field('popup_rec_excerpt_length', __('Limit słów zajawki', 'pro_reader'), [$this, 'excerpt_length_callback'], $page, $section);
        add_settings_field('popup_rec_excerpt_lines', __('Limit linii zajawki', 'pro_reader'), [$this, 'excerpt_lines_callback'], $page, $section);
    }

    private function register_thumbnail_fields(string $page, string $section): void
    {
        add_settings_field('popup_rec_thumb_size', __('Rozmiar obrazka', 'pro_reader'), [$this, 'thumb_size_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_aspect_ratio', __('Proporcje obrazka', 'pro_reader'), [$this, 'thumb_aspect_ratio_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_fit', __('Dopasowanie obrazka', 'pro_reader'), [$this, 'thumb_fit_callback'], $page, $section);
    }
    
    private function register_button_fields(string $page, string $section): void
    {
        add_settings_field('popup_rec_button_bg_color', __('Kolor tła', 'pro_reader'), [$this, 'button_bg_color_callback'], $page, $section);
        add_settings_field('popup_rec_button_text_color', __('Kolor tekstu', 'pro_reader'), [$this, 'button_text_color_callback'], $page, $section);
        add_settings_field('popup_rec_button_bg_hover_color', __('Kolor tła (hover)', 'pro_reader'), [$this, 'button_bg_hover_color_callback'], $page, $section);
        add_settings_field('popup_rec_button_text_hover_color', __('Kolor tekstu (hover)', 'pro_reader'), [$this, 'button_text_hover_color_callback'], $page, $section);
        add_settings_field('popup_rec_button_border_radius', __('Zaokrąglenie rogów (px)', 'pro_reader'), [$this, 'button_border_radius_callback'], $page, $section);
    }

    public function button_bg_color_callback(): void
    {
        $value = $this->options['popup_rec_button_bg_color'] ?? '#0073aa';
        printf('<input type="text" name="%s[popup_rec_button_bg_color]" value="%s" class="wp-color-picker-field" />', self::OPTION_NAME, esc_attr($value));
    }

    public function button_text_color_callback(): void
    {
        $value = $this->options['popup_rec_button_text_color'] ?? '#ffffff';
        printf('<input type="text" name="%s[popup_rec_button_text_color]" value="%s" class="wp-color-picker-field" />', self::OPTION_NAME, esc_attr($value));
    }

    public function button_bg_hover_color_callback(): void
    {
        $value = $this->options['popup_rec_button_bg_hover_color'] ?? '#005177';
        printf('<input type="text" name="%s[popup_rec_button_bg_hover_color]" value="%s" class="wp-color-picker-field" />', self::OPTION_NAME, esc_attr($value));
    }

    public function button_text_hover_color_callback(): void
    {
        $value = $this->options['popup_rec_button_text_hover_color'] ?? '#ffffff';
        printf('<input type="text" name="%s[popup_rec_button_text_hover_color]" value="%s" class="wp-color-picker-field" />', self::OPTION_NAME, esc_attr($value));
    }

    public function button_border_radius_callback(): void
    {
        $value = $this->options['popup_rec_button_border_radius'] ?? 4;
        printf('<input type="number" name="%s[popup_rec_button_border_radius]" value="%d" min="0" max="50" />', self::OPTION_NAME, esc_attr($value));
    }

    public function sanitize(array $input): array
    {
        $sanitized = get_option(self::OPTION_NAME, []);
        
        if (isset($input['popup_trigger_time'])) {
            $sanitized['popup_enable']                      = !empty($input['popup_enable']) ? '1' : '0';
            if (!empty($input['popup_display_on']) && is_array($input['popup_display_on'])) {
                $sanitized['popup_display_on'] = array_map('sanitize_key', $input['popup_display_on']);
            } else {
                $sanitized['popup_display_on'] = [];
            }
            $sanitized['popup_trigger_scroll_up']           = !empty($input['popup_trigger_scroll_up']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent_enable'] = !empty($input['popup_trigger_scroll_percent_enable']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent']      = max(1, min(100, absint($input['popup_trigger_scroll_percent'] ?? 85)));
            $sanitized['popup_trigger_time']                = absint($input['popup_trigger_time'] ?? 60);
            $sanitized['popup_content_main']                = wp_kses_post($input['popup_content_main'] ?? '');
        }

        if (isset($input['popup_rec_item_layout'])) {
            $sanitized['popup_recommendations_count']     = max(1, min(10, absint($input['popup_recommendations_count'] ?? 3)));
            
            if (!empty($input['popup_recommendation_post_types']) && is_array($input['popup_recommendation_post_types'])) {
                $sanitized['popup_recommendation_post_types'] = array_map('sanitize_key', $input['popup_recommendation_post_types']);
            } else {
                $sanitized['popup_recommendation_post_types'] = ['post'];
            }
            $allowed_logics = ['date', 'popularity', 'hybrid_fill', 'hybrid_mix'];
            if (isset($input['popup_recommendation_logic']) && in_array($input['popup_recommendation_logic'], $allowed_logics)) {
                $sanitized['popup_recommendation_logic'] = $input['popup_recommendation_logic'];
            } else {
                $sanitized['popup_recommendation_logic'] = 'hybrid_fill';
            }

            $sanitized['popup_recommendations_layout']    = in_array($input['popup_recommendations_layout'] ?? 'list', ['list', 'grid']) ? $input['popup_recommendations_layout'] : 'list';
            $sanitized['popup_recommendations_link_text'] = wp_kses_post($input['popup_recommendations_link_text'] ?? 'Zobacz więcej →');
            
            $sanitized['popup_rec_item_layout']          = in_array($input['popup_rec_item_layout'] ?? 'vertical', ['vertical', 'horizontal']) ? $input['popup_rec_item_layout'] : 'vertical';
            $sanitized['popup_rec_excerpt_limit_type']   = in_array($input['popup_rec_excerpt_limit_type'] ?? 'words', ['words', 'lines']) ? $input['popup_rec_excerpt_limit_type'] : 'words';
            $sanitized['popup_rec_excerpt_length']       = absint($input['popup_rec_excerpt_length'] ?? 15);
            $sanitized['popup_rec_excerpt_lines']        = absint($input['popup_rec_excerpt_lines'] ?? 3);
            
            $allowed_components = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
            $sanitized['popup_rec_components_order'] = $this->sanitize_order_array($input['popup_rec_components_order'] ?? [], $allowed_components);
            $sanitized['popup_rec_components_visibility'] = $this->sanitize_visibility_array($input['popup_rec_components_visibility'] ?? [], $allowed_components);

            $allowed_sizes = array_keys($this->get_image_sizes_for_select());
            $sanitized['popup_rec_thumb_size'] = in_array($input['popup_rec_thumb_size'] ?? 'medium', $allowed_sizes) ? $input['popup_rec_thumb_size'] : 'medium';
            
            $allowed_ratios = ['16:9', '4:3', '1:1', '3:4', 'auto'];
            $sanitized['popup_rec_thumb_aspect_ratio'] = in_array($input['popup_rec_thumb_aspect_ratio'] ?? '16:9', $allowed_ratios) ? $input['popup_rec_thumb_aspect_ratio'] : '16:9';

            $allowed_fits = ['cover', 'contain'];
            $sanitized['popup_rec_thumb_fit'] = in_array($input['popup_rec_thumb_fit'] ?? 'cover', $allowed_fits) ? $input['popup_rec_thumb_fit'] : 'cover';
            $sanitized['popup_rec_button_bg_color']          = sanitize_hex_color($input['popup_rec_button_bg_color'] ?? '#0073aa');
            $sanitized['popup_rec_button_text_color']        = sanitize_hex_color($input['popup_rec_button_text_color'] ?? '#ffffff');
            $sanitized['popup_rec_button_bg_hover_color']    = sanitize_hex_color($input['popup_rec_button_bg_hover_color'] ?? '#005177');
            $sanitized['popup_rec_button_text_hover_color']  = sanitize_hex_color($input['popup_rec_button_text_hover_color'] ?? '#ffffff');
            $sanitized['popup_rec_button_border_radius']     = absint($input['popup_rec_button_border_radius'] ?? 4);
        $sanitized['popup_padding_container']     = isset($input['popup_padding_container']) ? absint($input['popup_padding_container']) : 24;
        $sanitized['popup_margin_content_bottom'] = isset($input['popup_margin_content_bottom']) ? absint($input['popup_margin_content_bottom']) : 20;
        $sanitized['popup_gap_list_items']        = isset($input['popup_gap_list_items']) ? absint($input['popup_gap_list_items']) : 16;
        $sanitized['popup_gap_grid_items']        = isset($input['popup_gap_grid_items']) ? absint($input['popup_gap_grid_items']) : 24;
        }
        
        return $sanitized;
    }

    private function sanitize_order_array(array $order_data, array $allowed_keys): array
    {
        $sanitized = [];
        foreach ($order_data as $key) {
            if (in_array($key, $allowed_keys, true)) {
                $sanitized[] = sanitize_key($key);
            }
        }
        return $sanitized;
    }

    private function sanitize_visibility_array(array $visibility_data, array $allowed_keys): array
    {
        $sanitized = [];
        foreach ($allowed_keys as $key) {
            $sanitized[$key] = !empty($visibility_data[$key]) ? '1' : '0';
        }
        return $sanitized;
    }

    public function enable_callback(): void
    {
        $value = $this->options['popup_enable'] ?? '0';
        printf('<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s />', self::OPTION_NAME, checked('1', $value, false));
        echo ' <label for="popup_enable">' . esc_html__('Aktywuj popup na stronie.', 'pro_reader') . '</label>';
    }
    
    public function display_on_callback(): void
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected_types = $this->options['popup_display_on'] ?? [];
        
        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }
            $is_checked = in_array($post_type->name, $selected_types);
            printf(
                '<label style="margin-right: 15px; display: inline-block;"><input type="checkbox" name="%s[popup_display_on][]" value="%s" %s> %s</label>',
                self::OPTION_NAME,
                esc_attr($post_type->name),
                checked($is_checked, true, false),
                esc_html($post_type->label)
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Wybierz typy treści, na których ma być automatycznie wyświetlany popup.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_percent_enable_callback(): void
    {
        $value = $this->options['popup_trigger_scroll_percent_enable'] ?? '1';
        printf('<input type="checkbox" id="popup_trigger_scroll_percent_enable" name="%s[popup_trigger_scroll_percent_enable]" value="1" %s />', self::OPTION_NAME, checked('1', $value, false));
        echo ' <label for="popup_trigger_scroll_percent_enable">' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
    }

    public function trigger_scroll_percent_callback(): void
    {
        $value = $this->options['popup_trigger_scroll_percent'] ?? 85;
        printf('<input type="number" id="popup_trigger_scroll_percent" name="%s[popup_trigger_scroll_percent]" value="%d" min="1" max="100" /> %%', self::OPTION_NAME, esc_attr($value));
    }

    public function trigger_time_callback(): void
    {
        $value = $this->options['popup_trigger_time'] ?? 60;
        printf('<input type="number" id="popup_trigger_time" name="%s[popup_trigger_time]" value="%d" min="0" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Ustawienie wartości 0 wyłącza ten wyzwalacz.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_up_callback(): void
    {
        $value = $this->options['popup_trigger_scroll_up'] ?? '0';
        printf('<input type="checkbox" id="popup_trigger_scroll_up" name="%s[popup_trigger_scroll_up]" value="1" %s />', self::OPTION_NAME, checked('1', $value, false));
        echo ' <label for="popup_trigger_scroll_up">' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
    }

    public function content_main_callback(): void
    {
        $content = $this->options['popup_content_main'] ?? '';
        wp_editor($content, 'popup_content_main_editor', [
            'textarea_name' => self::OPTION_NAME . '[popup_content_main]',
            'media_buttons' => true,
            'teeny'         => false,
            'textarea_rows' => 8,
        ]);
    }

    public function recommendations_count_callback(): void
    {
        $value = $this->options['popup_recommendations_count'] ?? 3;
        printf('<input type="number" id="popup_recommendations_count" name="%s[popup_recommendations_count]" value="%d" min="1" max="10" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dla logiki "Mieszane" użyj parzystej liczby (2, 4 lub 6) dla najlepszych rezultatów.', 'pro_reader') . '</p>';
    }

    public function recommendation_post_types_callback(): void
    {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected_types = $this->options['popup_recommendation_post_types'] ?? ['post'];

        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }
            $is_checked = in_array($post_type->name, $selected_types, true);
            printf(
                '<label style="margin-right: 15px; display: inline-block;"><input type="checkbox" name="%s[popup_recommendation_post_types][]" value="%s" %s> %s</label>',
                self::OPTION_NAME,
                esc_attr($post_type->name),
                checked($is_checked, true, false),
                esc_html($post_type->label)
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Zaznacz typy treści, które mogą być używane w rekomendacjach.', 'pro_reader') . '</p>';
    }

    public function recommendation_logic_callback(): void
    {
        $value = $this->options['popup_recommendation_logic'] ?? 'hybrid_fill';
        $name = self::OPTION_NAME . '[popup_recommendation_logic]';
        
        $logics = [
            'date'        => __('Tylko najnowsze', 'pro_reader'),
            'popularity'  => __('Tylko popularne (wg linków)', 'pro_reader'),
            'hybrid_fill' => __('Popularne, uzupełnione najnowszymi (Rekomendowane)', 'pro_reader'),
            'hybrid_mix'  => __('Mieszane (połowa popularnych, połowa najnowszych)', 'pro_reader')
        ];

        echo '<select id="popup_recommendation_logic" name="' . esc_attr($name) . '">';
        foreach ($logics as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz, w jaki sposób sortować treści wybrane w polu "Źródło rekomendacji".', 'pro_reader') . '</p>';
        
    }

    public function recommendations_layout_callback(): void
    {
        $value = $this->options['popup_recommendations_layout'] ?? 'list';
        echo '<select id="popup_recommendations_layout" name="' . self::OPTION_NAME . '[popup_recommendations_layout]">';
        echo '<option value="list"' . selected($value, 'list', false) . '>' . esc_html__('Lista', 'pro_reader') . '</option>';
        echo '<option value="grid"' . selected($value, 'grid', false) . '>' . esc_html__('Siatka', 'pro_reader') . '</option>';
        echo '</select>';
    }

    public function recommendations_link_text_callback(): void
    {
        $content = $this->options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';
        wp_editor($content, 'popup_recommendations_link_text_editor', [
            'textarea_name' => self::OPTION_NAME . '[popup_recommendations_link_text]',
            'media_buttons' => false,
            'teeny'         => true,
            'textarea_rows' => 3,
        ]);
    }

    public function item_layout_callback(): void
    {
        $value = $this->options['popup_rec_item_layout'] ?? 'vertical';
        printf(
            '<fieldset><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="vertical" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="horizontal" %4$s> %5$s</label></fieldset>',
            self::OPTION_NAME,
            checked($value, 'vertical', false),
            esc_html__('Wertykalny', 'pro_reader'),
            checked($value, 'horizontal', false),
            esc_html__('Horyzontalny', 'pro_reader')
        );
    }

    public function components_order_callback(): void
    {
        $defaults   = ['thumbnail' => 'Miniaturka', 'meta' => 'Metadane', 'title' => 'Tytuł', 'excerpt' => 'Zajawka', 'link' => 'Link'];
        $order      = $this->options['popup_rec_components_order'] ?? array_keys($defaults);
        $visibility = $this->options['popup_rec_components_visibility'] ?? array_fill_keys(array_keys($defaults), '1');

        foreach (array_keys($defaults) as $key) {
            if (!in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        echo '<ul id="rep-layout-builder" style="border:1px solid #ccd0d4;padding:10px;max-width:400px;background:#fff;">';
        foreach ($order as $key) {
            if (!isset($defaults[$key])) {
                continue;
            }
            $label   = $defaults[$key];
            $is_visible = isset($visibility[$key]) && $visibility[$key] === '1';
            printf(
                '<li style="padding:8px 12px;border:1px solid #ddd;margin-bottom:5px;background:#f9f9f9;cursor:move;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <input type="checkbox" id="v_%1$s" name="%2$s[popup_rec_components_visibility][%1$s]" value="1" %3$s>
                        <label for="v_%1$s" style="user-select:none;padding-left:4px;">%4$s</label>
                    </div>
                    <span class="dashicons dashicons-menu" style="color:#999;"></span>
                    <input type="hidden" name="%2$s[popup_rec_components_order][]" value="%1$s">
                </li>',
                esc_attr($key),
                self::OPTION_NAME,
                checked($is_visible, true, false),
                esc_html($label)
            );
        }
        echo '</ul>';
    }

    public function excerpt_limit_type_callback(): void
    {
        $value = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
        printf(
            '<fieldset><label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="words" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="lines" %4$s> %5$s</label></fieldset>',
            self::OPTION_NAME,
            checked($value, 'words', false),
            esc_html__('Limit słów', 'pro_reader'),
            checked($value, 'lines', false),
            esc_html__('Limit linii', 'pro_reader')
        );
    }

    public function excerpt_length_callback(): void
    {
        $value = $this->options['popup_rec_excerpt_length'] ?? 15;
        printf('<input type="number" id="popup_rec_excerpt_length" name="%s[popup_rec_excerpt_length]" value="%d" min="0" />', self::OPTION_NAME, esc_attr($value));
    }
    
    public function excerpt_lines_callback(): void
    {
        $value = $this->options['popup_rec_excerpt_lines'] ?? 3;
        printf('<input type="number" id="popup_rec_excerpt_lines" name="%s[popup_rec_excerpt_lines]" value="%d" min="0" style="width: 80px;" />', self::OPTION_NAME, esc_attr($value));
    }

    public function thumb_size_callback(): void
    {
        $value = $this->options['popup_rec_thumb_size'] ?? 'medium';
        $sizes = $this->get_image_sizes_for_select();
        echo '<select id="popup_rec_thumb_size" name="' . self::OPTION_NAME . '[popup_rec_thumb_size]">';
        foreach ($sizes as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Większe rozmiary zapewniają lepszą jakość, ale wolniej się ładują.', 'pro_reader') . '</p>';
    }

    public function thumb_aspect_ratio_callback(): void
    {
        $value = $this->options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
        $ratios = [
            '16:9'  => '16:9 (Szeroki)', '4:3' => '4:3 (Standardowy)', '1:1' => '1:1 (Kwadrat)',
            '3:4' => '3:4 (Portret)', 'auto' => 'Auto (Dopasuj do wysokości)'
        ];
        echo '<select id="popup_rec_thumb_aspect_ratio" name="' . self::OPTION_NAME . '[popup_rec_thumb_aspect_ratio]">';
        foreach ($ratios as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Kontroluje kształt kontenera obrazka (tylko w układzie wertykalnym).', 'pro_reader') . '</p>';
    }

    public function thumb_fit_callback(): void
    {
        $value = $this->options['popup_rec_thumb_fit'] ?? 'cover';
        $fits = ['cover' => 'Wypełnij (Cover)', 'contain' => 'Dopasuj (Contain)'];
        echo '<select id="popup_rec_thumb_fit" name="' . self::OPTION_NAME . '[popup_rec_thumb_fit]">';
        foreach ($fits as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('"Wypełnij" przycina obraz. "Dopasuj" skaluje go, by był w pełni widoczny.', 'pro_reader') . '</p>';
    }

    private function get_image_sizes_for_select(): array
    {
        $sizes           = get_intermediate_image_sizes();
        $formatted_sizes = [];
        foreach ($sizes as $size) {
            $details = wp_get_additional_image_sizes()[$size] ?? null;
            if ($details) {
                $formatted_sizes[$size] = ucfirst(str_replace('_', ' ', $size)) . " ({$details['width']}x{$details['height']})";
            } else {
                $formatted_sizes[$size] = ucfirst(str_replace('_', ' ', $size));
            }
        }
        $formatted_sizes['full'] = __('Pełny rozmiar (Full)', 'pro_reader');
        return $formatted_sizes;
    }
        /**
     * Renderuje pole input dla opcji 'padding_container'.
     */
    public function padding_container_callback(): void
    {
        $value = $this->options['popup_padding_container'] ?? 24;
        printf('<input type="number" id="popup_padding_container" name="%s[popup_padding_container]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Wewnętrzny margines dla całego okna popupa.', 'pro_reader') . '</p>';
    }

    /**
     * Renderuje pole input dla opcji 'margin_content_bottom'.
     */
    public function margin_content_bottom_callback(): void
    {
        $value = $this->options['popup_margin_content_bottom'] ?? 20;
        printf('<input type="number" id="popup_margin_content_bottom" name="%s[popup_margin_content_bottom]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Odstęp między niestandardową treścią a listą rekomendacji.', 'pro_reader') . '</p>';
    }

    /**
     * Renderuje pole input dla opcji 'popup_gap_list_items'.
     */
    public function gap_list_items_callback(): void
    {
        $value = $this->options['popup_gap_list_items'] ?? 16;
        printf('<input type="number" id="popup_gap_list_items" name="%s[popup_gap_list_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Pionowy odstęp między artykułami w układzie listy.', 'pro_reader') . '</p>';
    }

    /**
     * Renderuje pole input dla opcji 'popup_gap_grid_items'.
     */
    public function gap_grid_items_callback(): void
    {
        $value = $this->options['popup_gap_grid_items'] ?? 24;
        printf('<input type="number" id="popup_gap_grid_items" name="%s[popup_gap_grid_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Poziomy odstęp między artykułami w układzie siatki.', 'pro_reader') . '</p>';
    }

    /**
     * Renderuje przycisk do resetowania wartości odstępów.
     */
    public function spacing_reset_callback(): void
    {
        echo '<button type="button" id="rep-spacing-reset-button" class="button button-secondary">' . esc_html__('Przywróć domyślne', 'pro_reader') . '</button>';
        echo '<p class="description">' . esc_html__('Resetuje wszystkie powyższe wartości odstępów do rekomendowanych ustawień domyślnych.', 'pro_reader') . '</p>';
    }
}
