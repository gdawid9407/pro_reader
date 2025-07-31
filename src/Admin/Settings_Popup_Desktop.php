<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zarządza zakładką "Wygląd - Desktop" dla modułu Popup.
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
        add_settings_field('popup_max_width_desktop', __('Maksymalna szerokość (px)', 'pro_reader'), [$this, 'max_width_callback'], $page, 'popup_dimensions_section');
        add_settings_field('popup_max_height_desktop', __('Maksymalna wysokość (vh)', 'pro_reader'), [$this, 'max_height_callback'], $page, 'popup_dimensions_section');

        add_settings_section('popup_layout_spacing_section', __('Układ i Odstępy', 'pro_reader'), null, $page);
        add_settings_field('popup_padding_y_desktop', __('Padding pionowy (góra/dół) (px)', 'pro_reader'), [$this, 'padding_y_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_padding_x_desktop', __('Padding poziomy (lewo/prawo) (px)', 'pro_reader'), [$this, 'padding_x_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_margin_content_bottom_desktop', __('Odstęp pod treścią (px)', 'pro_reader'), [$this, 'margin_content_bottom_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_gap_list_items_desktop', __('Odstęp między elementami - Lista (px)', 'pro_reader'), [$this, 'gap_list_items_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_gap_grid_items_desktop', __('Odstęp między elementami - Siatka (px)', 'pro_reader'), [$this, 'gap_grid_items_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_grid_item_width_desktop', __('Szerokość elementu - Siatka (px)', 'pro_reader'), [$this, 'grid_item_width_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_rec_thumb_margin_right_desktop', __('Odstęp zdjęcia od tekstu (px)', 'pro_reader'), [$this, 'thumb_margin_right_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_rec_thumb_width_horizontal_desktop', __('Szerokość miniaturki - Horyzontalny (px)', 'pro_reader'), [$this, 'thumb_width_horizontal_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_rec_thumb_width_list_vertical_desktop', __('Szerokość miniaturki - Wertykalny (%)', 'pro_reader'), [$this, 'thumb_width_list_vertical_callback'], $page, 'popup_layout_spacing_section');
        
        add_settings_field('popup_rec_margin_meta_bottom_desktop', __('Odstęp pod metadanymi (px)', 'pro_reader'), [$this, 'margin_meta_bottom_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_rec_margin_title_bottom_desktop', __('Odstęp pod tytułem (px)', 'pro_reader'), [$this, 'margin_title_bottom_callback'], $page, 'popup_layout_spacing_section');
        add_settings_field('popup_rec_margin_excerpt_bottom_desktop', __('Odstęp pod zajawką (px)', 'pro_reader'), [$this, 'margin_excerpt_bottom_callback'], $page, 'popup_layout_spacing_section');

        add_settings_field('popup_spacing_reset_desktop', '', [$this, 'spacing_reset_callback'], $page, 'popup_layout_spacing_section');

        add_settings_section('popup_layout_builder_section', __('Konstruktor Układu Rekomendacji', 'pro_reader'), null, $page);
        add_settings_field('popup_recommendations_layout_desktop', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_item_layout_desktop', __('Struktura elementu', 'pro_reader'), [$this, 'item_layout_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_components_order_desktop', __('Kolejność i widoczność elementów', 'pro_reader'), [$this, 'components_order_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_limit_type_desktop', __('Typ limitu zajawki', 'pro_reader'), [$this, 'excerpt_limit_type_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_length_desktop', __('Limit słów zajawki', 'pro_reader'), [$this, 'excerpt_length_callback'], $page, 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_lines_desktop', __('Limit linii zajawki', 'pro_reader'), [$this, 'excerpt_lines_callback'], $page, 'popup_layout_builder_section');

        add_settings_section('popup_thumbnail_settings_section', __('Ustawienia Miniaturki', 'pro_reader'), null, $page);
        $this->register_thumbnail_fields($page, 'popup_thumbnail_settings_section');

        add_settings_section('popup_template_section', __('Zarządzanie szablonami', 'pro_reader'), null, $page);
        add_settings_field('popup_save_template_1_desktop', __('Zapisz jako szablon 1', 'pro_reader'), [$this, 'save_template_1_callback'], $page, 'popup_template_section');
        add_settings_field('popup_save_template_2_desktop', __('Zapisz jako szablon 2', 'pro_reader'), [$this, 'save_template_2_callback'], $page, 'popup_template_section');
    }


    private function register_thumbnail_fields(string $page, string $section): void
    {
        add_settings_field('popup_rec_thumb_size_desktop', __('Rozmiar obrazka', 'pro_reader'), [$this, 'thumb_size_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_aspect_ratio_desktop', __('Proporcje obrazka', 'pro_reader'), [$this, 'thumb_aspect_ratio_callback'], $page, $section);
        add_settings_field('popup_rec_thumb_fit_desktop', __('Dopasowanie obrazka', 'pro_reader'), [$this, 'thumb_fit_callback'], $page, $section);
    }

    /**
     * Sanitacja danych dla zakładki "Wygląd - Desktop".
     */
    public function sanitize(array $input, array $current_options): array
    {
        $sanitized = $current_options;
        $desktop_options = $sanitized['desktop'] ?? [];
        $input_desktop = $input['desktop'] ?? [];

        $desktop_options['popup_max_width']                 = isset($input_desktop['popup_max_width']) ? absint($input_desktop['popup_max_width']) : 670;
        $desktop_options['popup_max_height']                = isset($input_desktop['popup_max_height']) ? absint($input_desktop['popup_max_height']) : 81;
        $desktop_options['popup_padding_y']                 = isset($input_desktop['popup_padding_y']) ? absint($input_desktop['popup_padding_y']) : 24;
        $desktop_options['popup_padding_x']                 = isset($input_desktop['popup_padding_x']) ? absint($input_desktop['popup_padding_x']) : 40;
        $desktop_options['popup_margin_content_bottom']     = isset($input_desktop['popup_margin_content_bottom']) ? absint($input_desktop['popup_margin_content_bottom']) : 20;
        $desktop_options['popup_gap_list_items']            = isset($input_desktop['popup_gap_list_items']) ? absint($input_desktop['popup_gap_list_items']) : 50;
        $desktop_options['popup_gap_grid_items']            = isset($input_desktop['popup_gap_grid_items']) ? absint($input_desktop['popup_gap_grid_items']) : 45;
        $desktop_options['popup_grid_item_width']           = isset($input_desktop['popup_grid_item_width']) ? absint($input_desktop['popup_grid_item_width']) : 234;
        
         $desktop_options['popup_rec_thumb_margin_right']    = isset($input_desktop['popup_rec_thumb_margin_right']) ? absint($input_desktop['popup_rec_thumb_margin_right']) : 25;
         $desktop_options['popup_rec_thumb_width_horizontal'] = isset($input_desktop['popup_rec_thumb_width_horizontal']) ? absint($input_desktop['popup_rec_thumb_width_horizontal']) : 200;
         $desktop_options['popup_rec_thumb_width_list_vertical'] = isset($input_desktop['popup_rec_thumb_width_list_vertical']) ? absint($input_desktop['popup_rec_thumb_width_list_vertical']) : 100;
        
         $desktop_options['popup_rec_margin_meta_bottom']    = isset($input_desktop['popup_rec_margin_meta_bottom']) ? absint($input_desktop['popup_rec_margin_meta_bottom']) : 8;
         $desktop_options['popup_rec_margin_title_bottom']   = isset($input_desktop['popup_rec_margin_title_bottom']) ? absint($input_desktop['popup_rec_margin_title_bottom']) : 12;
         $desktop_options['popup_rec_margin_excerpt_bottom'] = isset($input_desktop['popup_rec_margin_excerpt_bottom']) ? absint($input_desktop['popup_rec_margin_excerpt_bottom']) : 12;

        $desktop_options['popup_recommendations_layout']    = isset($input_desktop['popup_recommendations_layout']) && in_array($input_desktop['popup_recommendations_layout'], ['list', 'grid']) ? $input_desktop['popup_recommendations_layout'] : 'grid';
        $desktop_options['popup_rec_item_layout']           = isset($input_desktop['popup_rec_item_layout']) && in_array($input_desktop['popup_rec_item_layout'], ['vertical', 'horizontal']) ? $input_desktop['popup_rec_item_layout'] : 'vertical';
        $desktop_options['popup_rec_excerpt_limit_type']    = isset($input_desktop['popup_rec_excerpt_limit_type']) && in_array($input_desktop['popup_rec_excerpt_limit_type'], ['words', 'lines']) ? $input_desktop['popup_rec_excerpt_limit_type'] : 'words';
        $desktop_options['popup_rec_excerpt_length']        = isset($input_desktop['popup_rec_excerpt_length']) ? absint($input_desktop['popup_rec_excerpt_length']) : 15;
        $desktop_options['popup_rec_excerpt_lines']         = isset($input_desktop['popup_rec_excerpt_lines']) ? absint($input_desktop['popup_rec_excerpt_lines']) : 3;
        
        $allowed_components = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
        $desktop_options['popup_rec_components_order']      = $this->sanitize_order_array($input_desktop['popup_rec_components_order'] ?? [], $allowed_components);
        $desktop_options['popup_rec_components_visibility'] = $this->sanitize_visibility_array($input_desktop['popup_rec_components_visibility'] ?? [], $allowed_components);

        $allowed_sizes = array_keys($this->get_image_sizes_for_select());
        $desktop_options['popup_rec_thumb_size'] = isset($input_desktop['popup_rec_thumb_size']) && in_array($input_desktop['popup_rec_thumb_size'], $allowed_sizes) ? $input_desktop['popup_rec_thumb_size'] : 'medium';
        
        $allowed_ratios = ['16:9', '4:3', '1:1', '3:4', 'auto'];
        $desktop_options['popup_rec_thumb_aspect_ratio'] = isset($input_desktop['popup_rec_thumb_aspect_ratio']) && in_array($input_desktop['popup_rec_thumb_aspect_ratio'], $allowed_ratios) ? $input_desktop['popup_rec_thumb_aspect_ratio'] : '16:9';

        $allowed_fits = ['cover', 'contain'];
        $desktop_options['popup_rec_thumb_fit'] = isset($input_desktop['popup_rec_thumb_fit']) && in_array($input_desktop['popup_rec_thumb_fit'], $allowed_fits) ? $input_desktop['popup_rec_thumb_fit'] : 'cover';
        
        $sanitized['desktop'] = $desktop_options;
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
        $value = $this->options['desktop']['popup_max_width'] ?? 670;
        printf('<input type="number" id="popup_max_width_desktop" name="%s[desktop][popup_max_width]" value="%d" min="300" max="1600" style="width: 100px;" />', self::OPTION_NAME, esc_attr($value));
    }

    public function max_height_callback(): void
    {
        $value = $this->options['desktop']['popup_max_height'] ?? 81;
        printf('<input type="number" id="popup_max_height_desktop" name="%s[desktop][popup_max_height]" value="%d" min="40" max="90" style="width: 100px;" />', self::OPTION_NAME, esc_attr($value));
    }

    public function padding_y_callback(): void
    {
        $value = $this->options['desktop']['popup_padding_y'] ?? 24;
        printf('<input type="number" id="popup_padding_y_desktop" name="%s[desktop][popup_padding_y]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function padding_x_callback(): void
    {
        $value = $this->options['desktop']['popup_padding_x'] ?? 40;
        printf('<input type="number" id="popup_padding_x_desktop" name="%s[desktop][popup_padding_x]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function margin_content_bottom_callback(): void
    {
        $value = $this->options['desktop']['popup_margin_content_bottom'] ?? 20;
        printf('<input type="number" id="popup_margin_content_bottom_desktop" name="%s[desktop][popup_margin_content_bottom]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function gap_list_items_callback(): void
    {
        $value = $this->options['desktop']['popup_gap_list_items'] ?? 50;
        printf('<input type="number" id="popup_gap_list_items_desktop" name="%s[desktop][popup_gap_list_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function gap_grid_items_callback(): void
    {
        $value = $this->options['desktop']['popup_gap_grid_items'] ?? 45;
        printf('<input type="number" id="popup_gap_grid_items_desktop" name="%s[desktop][popup_gap_grid_items]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
    }

    public function grid_item_width_callback(): void
    {
        $value = $this->options['desktop']['popup_grid_item_width'] ?? 234;
        printf('<input type="number" id="popup_grid_item_width_desktop" name="%s[desktop][popup_grid_item_width]" value="%d" min="150" max="500" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Kontroluje całkowitą szerokość pojedynczego elementu w układzie siatki.', 'pro_reader') . '</p>';
    }

    public function thumb_margin_right_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_margin_right'] ?? 25;
        printf('<input type="number" id="popup_rec_thumb_margin_right_desktop" name="%s[desktop][popup_rec_thumb_margin_right]" value="%d" min="0" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dla układu horyzontalnego kontroluje margines prawy, a dla wertykalnego - margines dolny miniaturki.', 'pro_reader') . '</p>';
    }

    public function thumb_width_horizontal_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_width_horizontal'] ?? 200;
        printf('<input type="number" id="popup_rec_thumb_width_horizontal_desktop" name="%s[desktop][popup_rec_thumb_width_horizontal]" value="%d" min="50" max="500" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dotyczy tylko horyzontalnego układu elementu w liście.', 'pro_reader') . '</p>';
    }

    public function thumb_width_list_vertical_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_width_list_vertical'] ?? 100;
        printf('<input type="number" id="popup_rec_thumb_width_list_vertical_desktop" name="%s[desktop][popup_rec_thumb_width_list_vertical]" value="%d" min="10" max="100" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Dotyczy tylko wertykalnego układu elementu w liście. Wartość w %.', 'pro_reader') . '</p>';
    }

    public function margin_meta_bottom_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_margin_meta_bottom'] ?? 8;
        printf('<input type="number" id="popup_rec_margin_meta_bottom_desktop" name="%s[desktop][popup_rec_margin_meta_bottom]" value="%d" min="0" max="50" />', self::OPTION_NAME, esc_attr($value));
    }

    public function margin_title_bottom_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_margin_title_bottom'] ?? 12;
        printf('<input type="number" id="popup_rec_margin_title_bottom_desktop" name="%s[desktop][popup_rec_margin_title_bottom]" value="%d" min="0" max="50" />', self::OPTION_NAME, esc_attr($value));
    }

    public function margin_excerpt_bottom_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_margin_excerpt_bottom'] ?? 12;
        printf('<input type="number" id="popup_rec_margin_excerpt_bottom_desktop" name="%s[desktop][popup_rec_margin_excerpt_bottom]" value="%d" min="0" max="50" />', self::OPTION_NAME, esc_attr($value));
    }

    public function spacing_reset_callback(): void
    {
        echo '<button type="button" id="rep-spacing-reset-button-desktop" class="button button-secondary">' . esc_html__('Przywróć domyślne', 'pro_reader') . '</button>';
    }
    
    public function recommendations_layout_callback(): void
    {
        $value = $this->options['desktop']['popup_recommendations_layout'] ?? 'grid';
        echo '<select id="popup_recommendations_layout_desktop" name="' . self::OPTION_NAME . '[desktop][popup_recommendations_layout]">';
        echo '<option value="list"' . selected($value, 'list', false) . '>' . esc_html__('Lista', 'pro_reader') . '</option>';
        echo '<option value="grid"' . selected($value, 'grid', false) . '>' . esc_html__('Siatka', 'pro_reader') . '</option>';
        echo '</select>';
    }

    public function item_layout_callback(): void
    {
        $layout = $this->options['desktop']['popup_rec_item_layout'] ?? 'vertical';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo self::OPTION_NAME; ?>[desktop][popup_rec_item_layout]" value="vertical" <?php checked($layout, 'vertical'); ?>>
                <?php esc_html_e('Wertykalny', 'pro_reader'); ?>
            </label>
            <br>
            <label>
                <input type="radio" name="<?php echo self::OPTION_NAME; ?>[desktop][popup_rec_item_layout]" value="horizontal" <?php checked($layout, 'horizontal'); ?>>
                <?php esc_html_e('Horyzontalny', 'pro_reader'); ?>
            </label>
        </fieldset>
        <?php
    }

    public function components_order_callback(): void
    {
        $defaults   = ['thumbnail' => 'Miniaturka', 'meta' => 'Metadane', 'title' => 'Tytuł', 'excerpt' => 'Zajawka', 'link' => 'Przycisk'];
        $order      = $this->options['desktop']['popup_rec_components_order'] ?? array_keys($defaults);
        $visibility = $this->options['desktop']['popup_rec_components_visibility'] ?? array_fill_keys(array_keys($defaults), '1');

        // Dodatkowe zabezpieczenie na wypadek, gdyby w bazie danych była nieprawidłowa wartość
        if (!is_array($order)) {
            $order = array_keys($defaults);
        }
        if (!is_array($visibility)) {
            $visibility = array_fill_keys(array_keys($defaults), '1');
        }

        foreach (array_keys($defaults) as $key) {
            if (!in_array($key, $order, true)) $order[] = $key;
        }

        echo '<ul id="rep-layout-builder-desktop" class="rep-layout-builder" style="border:1px solid #ccd0d4;padding:10px;max-width:400px;background:#fff;">';
        foreach ($order as $key) {
            if (!isset($defaults[$key])) continue;
            $label   = $defaults[$key];
            $is_visible = isset($visibility[$key]) && $visibility[$key] === '1';
            printf(
                '<li style="padding:8px 12px;border:1px solid #ddd;margin-bottom:5px;background:#f9f9f9;cursor:move;display:flex;align-items:center;justify-content:space-between;">
                    <div><input type="checkbox" id="v_%1$s_desktop" name="%2$s[desktop][popup_rec_components_visibility][%1$s]" value="1" %3$s><label for="v_%1$s_desktop" style="user-select:none;padding-left:4px;">%4$s</label></div>
                    <span class="dashicons dashicons-menu" style="color:#999;"></span><input type="hidden" name="%2$s[desktop][popup_rec_components_order][]" value="%1$s"></li>',
                esc_attr($key), self::OPTION_NAME, checked($is_visible, true, false), esc_html($label)
            );
        }
        echo '</ul>';
    }

    public function excerpt_limit_type_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_excerpt_limit_type'] ?? 'words';
        printf(
            '<fieldset><label><input type="radio" name="%1$s[desktop][popup_rec_excerpt_limit_type]" value="words" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[desktop][popup_rec_excerpt_limit_type]" value="lines" %4$s> %5$s</label></fieldset>',
            self::OPTION_NAME, checked($value, 'words', false), esc_html__('Limit słów', 'pro_reader'),
            checked($value, 'lines', false), esc_html__('Limit linii', 'pro_reader')
        );
    }

    public function excerpt_length_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_excerpt_length'] ?? 15;
        printf('<input type="number" id="popup_rec_excerpt_length_desktop" name="%s[desktop][popup_rec_excerpt_length]" value="%d" min="0" />', self::OPTION_NAME, esc_attr($value));
    }
    
    public function excerpt_lines_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_excerpt_lines'] ?? 3;
        printf('<input type="number" id="popup_rec_excerpt_lines_desktop" name="%s[desktop][popup_rec_excerpt_lines]" value="%d" min="0" style="width: 80px;" />', self::OPTION_NAME, esc_attr($value));
    }
    
    public function thumb_size_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_size'] ?? 'medium';
        $sizes = $this->get_image_sizes_for_select();
        echo '<select id="popup_rec_thumb_size_desktop" name="' . self::OPTION_NAME . '[desktop][popup_rec_thumb_size]">';
        foreach ($sizes as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    public function thumb_aspect_ratio_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_aspect_ratio'] ?? '16:9';
        $ratios = [
            '16:9'  => '16:9 (Szeroki)', '4:3' => '4:3 (Standardowy)', '1:1' => '1:1 (Kwadrat)',
            '3:4' => '3:4 (Portret)', 'auto' => 'Auto (Dopasuj do wysokości)'
        ];
        echo '<select id="popup_rec_thumb_aspect_ratio_desktop" name="' . self::OPTION_NAME . '[desktop][popup_rec_thumb_aspect_ratio]">';
        foreach ($ratios as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    public function thumb_fit_callback(): void
    {
        $value = $this->options['desktop']['popup_rec_thumb_fit'] ?? 'cover';
        $fits = ['cover' => 'Wypełnij (Cover)', 'contain' => 'Dopasuj (Contain)'];
        echo '<select id="popup_rec_thumb_fit_desktop" name="' . self::OPTION_NAME . '[desktop][popup_rec_thumb_fit]">';
        foreach ($fits as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }

    public function save_template_1_callback(): void
    {
        echo '<button type="button" id="rep-save-template-1-desktop" class="button button-secondary rep-save-template-btn" data-template-id="1">' . esc_html__('Zapisz bieżące ustawienia jako Szablon 1', 'pro_reader') . '</button>';
        echo '<span id="save-template-1-feedback-desktop" class="rep-template-feedback" style="margin-left: 10px; display: none;"></span>';
    }

    public function save_template_2_callback(): void
    {
        echo '<button type="button" id="rep-save-template-2-desktop" class="button button-secondary rep-save-template-btn" data-template-id="2">' . esc_html__('Zapisz bieżące ustawienia jako Szablon 2', 'pro_reader') . '</button>';
        echo '<span id="save-template-2-feedback-desktop" class="rep-template-feedback" style="margin-left: 10px; display: none;"></span>';
    }
}