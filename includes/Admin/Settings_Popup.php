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
        add_settings_section('popup_triggers_section', __('Ustawienia Wyzwalaczy', 'pro_reader'), null, $page);
        $this->register_trigger_fields($page, 'popup_triggers_section');

        // Sekcja 2: Treść
        add_settings_section('popup_content_section', __('Treść Popupa', 'pro_reader'), null, $page);
        add_settings_field('popup_content_main', __('Edytor treści', 'pro_reader'), [$this, 'content_main_callback'], $page, 'popup_content_section');

        // Sekcja 3: Rekomendacje Ogólne
        add_settings_section('popup_recommendations_section', __('Ustawienia Ogólne Rekomendacji', 'pro_reader'), null, $page);
        $this->register_recommendation_fields($page, 'popup_recommendations_section');

        // Sekcja 4: Konstruktor Układu
        add_settings_section('popup_layout_builder_section', __('Konstruktor Układu Rekomendacji', 'pro_reader'), null, $page);
        $this->register_layout_builder_fields($page, 'popup_layout_builder_section');

        // Sekcja 5: Ustawienia Miniaturki
        add_settings_section('popup_thumbnail_settings_section', __('Ustawienia Miniaturki', 'pro_reader'), null, $page);
        $this->register_thumbnail_fields($page, 'popup_thumbnail_settings_section');
    }

    private function register_trigger_fields(string $page, string $section): void
    {
        add_settings_field('popup_enable', __('Włącz Moduł Popup', 'pro_reader'), [$this, 'enable_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_percent_enable', __('Wyzwalacz: Procent przewinięcia', 'pro_reader'), [$this, 'trigger_scroll_percent_enable_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_percent', __('Wartość procentowa', 'pro_reader'), [$this, 'trigger_scroll_percent_callback'], $page, $section);
        add_settings_field('popup_trigger_time', __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'), [$this, 'trigger_time_callback'], $page, $section);
        add_settings_field('popup_trigger_scroll_up', __('Wyzwalacz: Scroll w górę', 'pro_reader'), [$this, 'trigger_scroll_up_callback'], $page, $section);
    }

    private function register_recommendation_fields(string $page, string $section): void
    {
        add_settings_field('popup_recommendations_count', __('Liczba wpisów', 'pro_reader'), [$this, 'recommendations_count_callback'], $page, $section);
        add_settings_field('popup_recommendation_logic', __('Logika rekomendacji', 'pro_reader'), [$this, 'recommendation_logic_callback'], $page, $section);
        add_settings_field('popup_recommendations_layout', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], $page, $section);
        add_settings_field('popup_recommendations_link_text', __('Treść linku "Czytaj dalej"', 'pro_reader'), [$this, 'recommendations_link_text_callback'], $page, $section);
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

    public function sanitize(array $input): array
    {
        $sanitized = get_option(self::OPTION_NAME, []);
        
        // Sanitacja pól z sekcji Wyzwalacze i Treść
        if (isset($input['popup_trigger_time'])) {
            $sanitized['popup_enable']                      = !empty($input['popup_enable']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_up']           = !empty($input['popup_trigger_scroll_up']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent_enable'] = !empty($input['popup_trigger_scroll_percent_enable']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent']      = max(1, min(100, absint($input['popup_trigger_scroll_percent'] ?? 85)));
            $sanitized['popup_trigger_time']                = absint($input['popup_trigger_time'] ?? 60);
            $sanitized['popup_content_main']                = wp_kses_post($input['popup_content_main'] ?? '');
        }

        // Sanitacja pól z sekcji Rekomendacje, Układ i Miniaturka
        if (isset($input['popup_rec_item_layout'])) {
            $sanitized['popup_recommendations_count']     = max(1, min(10, absint($input['popup_recommendations_count'] ?? 3)));
            
            // Sanitacja nowej opcji logiki rekomendacji.
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

    /**
     * Wyświetla pole wyboru dla logiki rekomendacji.
     */
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
        echo '<p class="description">' . esc_html__('Wybierz, w jaki sposób wtyczka ma dobierać artykuły do rekomendacji.', 'pro_reader') . '</p>';
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
}