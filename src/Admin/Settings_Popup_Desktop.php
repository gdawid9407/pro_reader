<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa zarządzająca zakładką "Wygląd - Desktop" dla modułu Popup.
 */
class Settings_Popup_Desktop
{
    private const OPTION_NAME = 'reader_engagement_pro_options';
    private array $options = [];

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME, []);
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * Rejestruje sekcje i pola ustawień.
     */
    public function page_init(): void
    {
        $page = 'reader-engagement-pro-popup-desktop';

        add_settings_section('popup_dimensions_section', __('Wymiary Popupa (Desktop)', 'pro_reader'), null, $page);
        add_settings_field('popup_max_width', __('Maksymalna szerokość (px)', 'pro_reader'), [$this, 'max_width_callback'], $page, 'popup_dimensions_section');
        add_settings_field('popup_max_height', __('Maksymalna wysokość (vh)', 'pro_reader'), [$this, 'max_height_callback'], $page, 'popup_dimensions_section');

        add_settings_section('popup_layout_spacing_section', __('Układ i Odstępy', 'pro_reader'), null, $page);
        add_settings_field('popup_padding_container', __('Padding kontenera (px)', 'pro_reader'), [$this, 'padding_container_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_margin_content_bottom', __('Odstęp pod treścią (px)', 'pro_reader'), [$this, 'margin_content_bottom_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_gap_list_items', __('Odstęp między elementami - Lista (px)', 'pro_reader'), [$this, 'gap_list_items_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_gap_grid_items', __('Odstęp między elementami - Siatka (px)', 'pro_reader'), [$this, 'gap_grid_items_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_spacing_reset', '', [$this, 'spacing_reset_callback'], $page, 'popup_layout_spacing_section');

        add_settings_section('popup_layout_builder_section', __('Konstruktor Układu Rekomendacji', 'pro_reader'), null, $page);
        add_settings_field('popup_recommendations_layout', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_item_layout', __('Struktura elementu', 'pro_reader'), [$this, 'item_layout_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_components_order', __('Kolejność i widoczność elementów', 'pro_reader'), [$this, 'components_order_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_limit_type', __('Typ limitu zajawki', 'pro_reader'), [$this, 'excerpt_limit_type_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_length', __('Limit słów zajawki', 'pro_reader'), [$this, 'excerpt_length_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_lines', __('Limit linii zajawki', 'pro_reader'), [$this, 'excerpt_lines_callback'], $page, 'popup_layout_builder_section');
        
        // --- POCZĄTEK ZMIAN ---
        // Usunięto sekcję ustawień przycisku.
        // --- KONIEC ZMIAN ---

        add_settings_section('popup_thumbnail_settings_section', __('Ustawienia Miniaturki', 'pro_reader'), null, $page);
        $this->register_thumbnail_fields($page, 'popup_thumbnail_settings_section');
    }

    // --- POCZĄTEK ZMIAN ---
    // Usunięto metodę register_button_fields.
    // --- KONIEC ZMIAN ---

    private function register_thumbnail_fields(string $page, string $section): void
    {
        add_settings_field('popup_rec_thumb_size', __('Rozmiar obrazka', 'pro_reader'), [$this, 'thumb_size_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_aspect_ratio', __('Proporcje obrazka', 'pro_reader'), [$this, 'thumb_aspect_ratio_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_fit', __('Dopasowanie obrazka', 'pro_reader'), [$this, 'thumb_fit_callback'], $page, $section);
    }

    /**
     * Sanitacja danych tylko dla tej zakładki.
     */
    public function sanitize(array $input, array $current_options): array
    {
        $sanitized = $current_options;

        $sanitized['popup_max_width']                 = isset($input['popup_max_width']) ? absint($input['popup_max_width']) : 800;
        $sanitized['popup_max_height']                = isset($input['popup_max_height']) ? absint($input['popup_max_height']) : 90;
        $sanitized['popup_padding_container']         = isset($input['popup_padding_container']) ? absint($input['popup_padding_container']) : 24;
        $sanitized['popup_margin_content_bottom']     = isset($input['popup_margin_content_bottom']) ? absint($input['popup_margin_content_bottom']) : 20;
        $sanitized['popup_gap_list_items']            = isset($input['popup_gap_list_items']) ? absint($input['popup_gap_list_items']) : 16;
        $sanitized['popup_gap_grid_items']            = isset($input['popup_gap_grid_items']) ? absint($input['popup_gap_grid_items']) : 24;
        $sanitized['popup_recommendations_layout']    = isset($input['popup_recommendations_layout']) && in_array($input['popup_recommendations_layout'], ['list', 'grid']) ? $input['popup_recommendations_layout'] : 'list';
        $sanitized['popup_rec_item_layout']           = isset($input['popup_rec_item_layout']) && in_array($input['popup_rec_item_layout'], ['vertical', 'horizontal']) ? $input['popup_rec_item_layout'] : 'vertical';
        $sanitized['popup_rec_excerpt_limit_type']    = isset($input['popup_rec_excerpt_limit_type']) && in_array($input['popup_rec_excerpt_limit_type'], ['words', 'lines']) ? $input['popup_rec_excerpt_limit_type'] : 'words';
        $sanitized['popup_rec_excerpt_length']        = isset($input['popup_rec_excerpt_length']) ? absint($input['popup_rec_excerpt_length']) : 15;
        $sanitized['popup_rec_excerpt_lines']         = isset($input['popup_rec_excerpt_lines']) ? absint($input['popup_rec_excerpt_lines']) : 3;
        
        $allowed_components = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $sanitized['popup_rec_components_order']      = $this->sanitize_order_array($input['popup_rec_components_order'] ?? [], $allowed_components);
        $sanitized['popup_rec_components_visibility'] = $this->sanitize_visibility_array($input['popup_rec_components_visibility'] ?? [], $allowed_components);

        $allowed_sizes = array_keys($this->get_image_sizes_for_select());
        $sanitized['popup_rec_thumb_size'] = isset($input['popup_rec_thumb_size']) && in_array($input['popup_rec_thumb_size'], $allowed_sizes) ? $input['popup_rec_thumb_size'] : 'medium';
        
        $allowed_ratios = ['16:9', '4:3', '1:1', '3:4', 'auto'];
        $sanitized['popup_rec_thumb_aspect_ratio'] = isset($input['popup_rec_thumb_aspect_ratio']) && in_array($input['popup_rec_thumb_aspect_ratio'], $allowed_ratios) ? $input['popup_rec_thumb_aspect_ratio'] : '16:9';

        $allowed_fits = ['cover', 'contain'];
        $sanitized['popup_rec_thumb_fit'] = isset($input['popup_rec_thumb_fit']) && in_array($input['popup_rec_thumb_fit'], $allowed_fits) ? $input['popup_rec_thumb_fit'] : 'cover';
        
        // --- POCZĄTEK ZMIAN ---
        // Usunięto sanitację dla opcji przycisku.
        // --- KONIEC ZMIAN ---

        return $sanitized;
    }

    private function sanitize_order_array(array $order_data, array $allowed_keys): array
    {
        $sanitized = [];
        foreach ($order_data as $key) {
            if (in_array($key, $allowed_keys, true)) $sanitized[] = sanitize_key($key);
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

    private function get_image_sizes_for_select(): array
    {
        $sizes = get_intermediate_image_sizes();
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

    // --- CALLBACK FUNCTIONS ---

    public function max_width_callback(): void
    {
        $value = $this->options['popup_max_width'] ?? 800;
        printf('<input type="number" id="popup_max_width" name="%s[popup_max_width]" value="%d" min="300" max="1600" style="width: 100px;" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dla układu "Lista" zalecana szerokość to maksymalnie 925px. Układ "Siatka" może wymagać większej szerokości.', 'pro_reader') . '</p>';
    }

    public function max_height_callback(): void
    {
        $value = $this->options['popup_max_height'] ?? 90;
        printf('<input type="number" id="popup_max_height" name="%s[popup_max_height]" value="%d" min="40" max="90" style="width: 100px;" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dotyczy głównie układu "Lista". Zalecana maksymalna wartość to 70, aby zapewnić pełną widoczność treści na niższych ekranach.', 'pro_reader') . '</p>';
    }

    public function padding_container_callback(): void
    {
        $value = $this->options['popup_padding_container'] ?? 24;
        printf('<input type="number" id="popup_padding_container" name="%s[popup_padding_container]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function margin_content_bottom_callback(): void
    {
        $value = $this->options['popup_margin_content_bottom'] ?? 20;
        printf('<input type="number" id="popup_margin_content_bottom" name="%s[popup_margin_content_bottom]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function gap_list_items_callback(): void
    {
        $value = $this->options['popup_gap_list_items'] ?? 16;
        printf('<input type="number" id="popup_gap_list_items" name="%s[popup_gap_list_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function gap_grid_items_callback(): void
    {
        $value = $this->options['popup_gap_grid_items'] ?? 24;
        printf('<input type="number" id="popup_gap_grid_items" name="%s[popup_gap_grid_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function spacing_reset_callback(): void
    {
        echo '<button type="button" id="rep-spacing-reset-button" class="button button-secondary">' . esc_html__('Przywróć domyślne', 'pro_reader') . '</button>';
    }
    
    public function recommendations_layout_callback(): void
    {
        $value = $this->options['popup_recommendations_layout'] ?? 'list';
        echo '<select id="popup_recommendations_layout" name="' . self::OPTION_NAME . '[popup_recommendations_layout]">';
        echo '<option value="list"' . selected($value, 'list', false) . '>' . esc_html__('Lista', 'pro_reader') . '</option>';
        echo '<option value="grid"' . selected($value, 'grid', false) . '>' . esc_html__('Siatka', 'pro_reader') . '</option>';
        echo '</select>';
    }

    // --- POCZĄTEK ZMIAN ---
    // Usunięto funkcje callback dla ustawień przycisku.
    // --- KONIEC ZMIAN ---

    public function item_layout_callback(): void
    {
        $value = $this->options['popup_rec_item_layout'] ?? 'vertical';
        printf(
            '<fieldset><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="vertical" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="horizontal" %4$s> %5$s</label></fieldset>',
            self::OPTION_NAME, checked($value, 'vertical', false), esc_html__('Wertykalny', 'pro_reader'),
            checked($value, 'horizontal', false), esc_html__('Horyzontalny', 'pro_reader')
        );
    }

    public function components_order_callback(): void
    {
        $defaults   = ['thumbnail' => 'Miniaturka', 'meta' => 'Metadane', 'title' => 'Tytuł', 'excerpt' => 'Zajawka', 'link' => 'Przycisk'];
        $order      = $this->options['popup_rec_components_order'] ?? array_keys($defaults);
        $visibility = $this->options['popup_rec_components_visibility'] ?? array_fill_keys(array_keys($defaults), '1');

        foreach (array_keys($defaults) as $key) {
            if (!in_array($key, $order, true)) $order[] = $key;
        }

        echo '<ul id="rep-layout-builder" style="border:1px solid #ccd0d4;padding:10px;max-width:400px;background:#fff;">';
        foreach ($order as $key) {
            if (!isset($defaults[$key])) continue;
            $label   = $defaults[$key];
            $is_visible = isset($visibility[$key]) && $visibility[$key] === '1';
            printf(
                '<li style="padding:8px 12px;border:1px solid #ddd;margin-bottom:5px;background:#f9f9f9;cursor:move;display:flex;align-items:center;justify-content:space-between;">
                    <div><input type="checkbox" id="v_%1$s" name="%2$s[popup_rec_components_visibility][%1$s]" value="1" %3$s><label for="v_%1$s" style="user-select:none;padding-left:4px;">%4$s</label></div>
                    <span class="dashicons dashicons-menu" style="color:#999;"></span><input type="hidden" name="%2$s[popup_rec_components_order][]" value="%1$s"></li>',
                esc_attr($key), self::OPTION_NAME, checked($is_visible, true, false), esc_html($label)
            );
        }
        echo '</ul>';
    }

    public function excerpt_limit_type_callback(): void
    {
        $value = $this->options['popup_rec_excerpt_limit_type'] ?? 'words';
        printf(
            '<fieldset><label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="words" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="lines" %4$s> %5$s</label></fieldset>',
            self::OPTION_NAME, checked($value, 'words', false), esc_html__('Limit słów', 'pro_reader'),
            checked($value, 'lines', false), esc_html__('Limit linii', 'pro_reader')
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
    }
}