<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa zarządzająca zakładką "Ustawienia Ogólne" dla modułu Popup.
 */
class Settings_Popup_General
{
    private const OPTION_NAME = 'reader_engagement_pro_options';
    private array $options = [];

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME, []);
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * Rejestruje sekcje i pola ustawień dla tej zakładki.
     */
    public function page_init(): void
    {
        $page = 'reader-engagement-pro-popup-general';

        // Sekcja 1: Wyzwalacze
        add_settings_section('popup_triggers_section', __('Ustawienia Wyzwalaczy i Widoczności', 'pro_reader'), null, $page);
        $this->register_trigger_fields($page, 'popup_triggers_section');

        // Sekcja 2: Treść
        add_settings_section('popup_content_section', __('Treść Popupa', 'pro_reader'), null, $page);
        add_settings_field('popup_content_main', __('Edytor treści', 'pro_reader'), [$this, 'content_main_callback'], $page, 'popup_content_section');
        
        // Sekcja 3: Rekomendacje Ogólne
        add_settings_section('popup_recommendations_section', __('Ustawienia Ogólne Rekomendacji', 'pro_reader'), null, $page);
        $this->register_recommendation_fields($page, 'popup_recommendations_section');
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

    private function register_recommendation_fields(string $page, string $section): void
    {
        add_settings_field('popup_recommendations_count', __('Liczba wpisów', 'pro_reader'), [$this, 'recommendations_count_callback'], $page, $section);
        add_settings_field('popup_recommendation_post_types', __('Źródło rekomendacji', 'pro_reader'), [$this, 'recommendation_post_types_callback'], $page, $section);
        add_settings_field('popup_recommendation_logic', __('Kolejność rekomendacji', 'pro_reader'), [$this, 'recommendation_logic_callback'], $page, $section);
    }

    /**
     * Sanitacja danych tylko dla tej zakładki.
     */
    public function sanitize(array $input, array $current_options): array
    {
        // Rozpoczynamy z aktualnymi opcjami, aby nie nadpisać ustawień z innych zakładek.
        $sanitized = $current_options;

        $sanitized['popup_enable'] = !empty($input['popup_enable']) ? '1' : '0';
        if (!empty($input['popup_display_on']) && is_array($input['popup_display_on'])) {
            $sanitized['popup_display_on'] = array_map('sanitize_key', $input['popup_display_on']);
        } else {
            $sanitized['popup_display_on'] = [];
        }
        $sanitized['popup_trigger_scroll_up']           = !empty($input['popup_trigger_scroll_up']) ? '1' : '0';
        $sanitized['popup_trigger_scroll_percent_enable'] = !empty($input['popup_trigger_scroll_percent_enable']) ? '1' : '0';
        $sanitized['popup_trigger_scroll_percent']      = isset($input['popup_trigger_scroll_percent']) ? max(1, min(100, absint($input['popup_trigger_scroll_percent']))) : 85;
        $sanitized['popup_trigger_time']                = isset($input['popup_trigger_time']) ? absint($input['popup_trigger_time']) : 60;
        $sanitized['popup_content_main']                = isset($input['popup_content_main']) ? wp_kses_post($input['popup_content_main']) : '';
        
        $sanitized['popup_recommendations_count']     = isset($input['popup_recommendations_count']) ? max(1, min(10, absint($input['popup_recommendations_count']))) : 3;
            
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

        return $sanitized;
    }

    // --- CALLBACK FUNCTIONS ---

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
            if ($post_type->name === 'attachment') continue;
            $is_checked = in_array($post_type->name, $selected_types);
            printf(
                '<label style="margin-right: 15px; display: inline-block;"><input type="checkbox" name="%s[popup_display_on][]" value="%s" %s> %s</label>',
                self::OPTION_NAME, esc_attr($post_type->name), checked($is_checked, true, false), esc_html($post_type->label)
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
            'media_buttons' => true, 'teeny' => false, 'textarea_rows' => 8,
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
            if ($post_type->name === 'attachment') continue;
            $is_checked = in_array($post_type->name, $selected_types, true);
            printf(
                '<label style="margin-right: 15px; display: inline-block;"><input type="checkbox" name="%s[popup_recommendation_post_types][]" value="%s" %s> %s</label>',
                self::OPTION_NAME, esc_attr($post_type->name), checked($is_checked, true, false), esc_html($post_type->label)
            );
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Zaznacz typy treści, które mogą być używane w rekomendacjach.', 'pro_reader') . '</p>';
    }

    public function recommendation_logic_callback(): void
    {
        $value = $this->options['popup_recommendation_logic'] ?? 'hybrid_fill';
        $logics = [
            'date'        => __('Tylko najnowsze', 'pro_reader'),
            'popularity'  => __('Tylko popularne (wg linków)', 'pro_reader'),
            'hybrid_fill' => __('Popularne, uzupełnione najnowszymi (Rekomendowane)', 'pro_reader'),
            'hybrid_mix'  => __('Mieszane (połowa popularnych, połowa najnowszych)', 'pro_reader')
        ];
        echo '<select id="popup_recommendation_logic" name="' . self::OPTION_NAME . '[popup_recommendation_logic]">';
        foreach ($logics as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz, w jaki sposób sortować treści wybrane w polu "Źródło rekomendacji".', 'pro_reader') . '</p>';
    }
}