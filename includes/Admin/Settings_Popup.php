<?php

namespace ReaderEngagementPro\Admin;

/**
 * Klasa zarządzająca polami ustawień dla modułu Popup "Czytaj Więcej".
 */
class Settings_Popup {

    private const OPTION_NAME = 'reader_engagement_pro_options';

    public function __construct() {
        add_action('admin_init', [$this, 'page_init']);
    }

    public function page_init(): void {
        // SEKCJA 1: WYZWALACZE
        add_settings_section('popup_triggers_section', __('Ustawienia Wyzwalaczy', 'pro_reader'), [$this, 'render_triggers_section_info'], 'reader-engagement-pro-popup');
        add_settings_field('popup_enable', __('Włącz Moduł Popup', 'pro_reader'), [$this, 'popup_enable_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_percent_enable', __('Wyzwalacz: Procent przewinięcia', 'pro_reader'), [$this, 'trigger_scroll_percent_enable_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_percent', __('Wartość procentowa', 'pro_reader'), [$this, 'trigger_scroll_percent_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_time', __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'), [$this, 'trigger_time_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_up', __('Wyzwalacz: Scroll w górę', 'pro_reader'), [$this, 'trigger_scroll_up_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        
        // SEKCJA 2: TREŚĆ POPUPA
        add_settings_section('popup_content_section', __('Treść Popupa', 'pro_reader'), [$this, 'render_content_section_info'], 'reader-engagement-pro-popup');
        add_settings_field('popup_content_main', __('Edytor treści', 'pro_reader'), [$this, 'popup_content_main_callback'], 'reader-engagement-pro-popup', 'popup_content_section');

        // SEKCJA 3: REKOMENDACJE OGÓLNE
        add_settings_section('popup_recommendations_section', __('Ustawienia Ogólne Rekomendacji', 'pro_reader'), [$this, 'render_recommendations_section_info'], 'reader-engagement-pro-popup');
        add_settings_field('popup_recommendations_count', __('Liczba wpisów', 'pro_reader'), [$this, 'recommendations_count_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        add_settings_field('popup_recommendations_layout', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        add_settings_field('popup_recommendations_link_text', __('Treść linku', 'pro_reader'), [$this, 'recommendations_link_text_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        
        // SEKCJA 4: KONSTRUKTOR UKŁADU
        add_settings_section('popup_layout_builder_section', __('Konstruktor Układu Rekomendacji', 'pro_reader'), [$this, 'render_layout_builder_section_info'], 'reader-engagement-pro-popup');
        add_settings_field('popup_rec_item_layout', __('Struktura elementu', 'pro_reader'), [$this, 'layout_item_structure_callback'], 'reader-engagement-pro-popup', 'popup_layout_builder_section');
        add_settings_field('popup_rec_components_order', __('Kolejność i widoczność elementów', 'pro_reader'), [$this, 'layout_components_order_callback'], 'reader-engagement-pro-popup', 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_limit_type', __('Typ limitu zajawki', 'pro_reader'), [$this, 'layout_excerpt_limit_type_callback'], 'reader-engagement-pro-popup', 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_length', __('Długość zajawki', 'pro_reader'), [$this, 'layout_excerpt_length_callback'], 'reader-engagement-pro-popup', 'popup_layout_builder_section');
        add_settings_field('popup_rec_excerpt_lines', __('Ogranicz do liczby linii', 'pro_reader'), [$this, 'layout_excerpt_lines_callback'],'reader-engagement-pro-popup', 'popup_layout_builder_section');
        add_settings_field('popup_rec_group_meta', __('Grupowanie metadanych', 'pro_reader'), [$this, 'layout_group_meta_callback'], 'reader-engagement-pro-popup', 'popup_layout_builder_section');

        // NOWA SEKCJA 5: USTAWIENIA MINIATURKI
        add_settings_section(
            'popup_thumbnail_settings_section',
            __('Ustawienia Miniaturki', 'pro_reader'),
            [$this, 'render_thumbnail_settings_section_info'],
            'reader-engagement-pro-popup'
        );
        add_settings_field('popup_rec_thumb_size', __('Rozmiar obrazka', 'pro_reader'), [$this, 'thumb_size_callback'], 'reader-engagement-pro-popup', 'popup_thumbnail_settings_section');
        add_settings_field('popup_rec_thumb_aspect_ratio', __('Proporcje (dla układu wertykalnego)', 'pro_reader'), [$this, 'thumb_aspect_ratio_callback'], 'reader-engagement-pro-popup', 'popup_thumbnail_settings_section');
        add_settings_field('popup_rec_thumb_fit', __('Dopasowanie obrazka', 'pro_reader'), [$this, 'thumb_fit_callback'], 'reader-engagement-pro-popup', 'popup_thumbnail_settings_section');
    }

    public function sanitize(array $input): array {
        $sanitized = get_option(self::OPTION_NAME, []);

        if (isset($input['popup_trigger_time'])) {
            $sanitized['popup_enable'] = !empty($input['popup_enable']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_up'] = !empty($input['popup_trigger_scroll_up']) ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent_enable'] = !empty($input['popup_trigger_scroll_percent_enable']) ? '1' : '0';   
            $sanitized['popup_trigger_scroll_percent'] = max(1, min(100, absint($input['popup_trigger_scroll_percent'])));
            $sanitized['popup_trigger_time'] = absint($input['popup_trigger_time']);
            $sanitized['popup_content_main'] = wp_kses_post($input['popup_content_main']);
            $sanitized['popup_recommendations_count'] = max(1, min(10, absint($input['popup_recommendations_count'])));
            $sanitized['popup_recommendations_layout'] = in_array($input['popup_recommendations_layout'], ['list', 'grid']) ? $input['popup_recommendations_layout'] : 'list';
            $sanitized['popup_recommendations_link_text'] = wp_kses_post($input['popup_recommendations_link_text']);
        }

        if (isset($input['popup_rec_item_layout'])) {
            $sanitized['popup_rec_item_layout'] = in_array($input['popup_rec_item_layout'], ['vertical', 'horizontal']) ? $input['popup_rec_item_layout'] : 'vertical';
             $sanitized['popup_rec_excerpt_limit_type'] = in_array($input['popup_rec_excerpt_limit_type'], ['words', 'lines']) ? $input['popup_rec_excerpt_limit_type'] : 'words';
            $sanitized['popup_rec_excerpt_length'] = absint($input['popup_rec_excerpt_length']);
            $sanitized['popup_rec_excerpt_lines'] = isset($input['popup_rec_excerpt_lines']) ? absint($input['popup_rec_excerpt_lines']) : 0;
            $sanitized['popup_rec_group_meta'] = !empty($input['popup_rec_group_meta']) ? '1' : '0';

            $allowed_components = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
            $order = $input['popup_rec_components_order'] ?? [];
            $sanitized_order = [];
            if (is_array($order)) {
                foreach ($order as $component_key) {
                    if (in_array($component_key, $allowed_components, true)) { $sanitized_order[] = sanitize_key($component_key); }
                }
            }
            $sanitized['popup_rec_components_order'] = $sanitized_order;
            
            $visibility = $input['popup_rec_components_visibility'] ?? [];
            $sanitized_visibility = [];
             if (is_array($visibility)) {
                foreach ($allowed_components as $component) {
                     $sanitized_visibility[$component] = !empty($visibility[$component]) ? '1' : '0';
                }
            }
            $sanitized['popup_rec_components_visibility'] = $sanitized_visibility;
            
            // Sanitacja nowych pól miniaturki
            $allowed_sizes = array_keys($this->get_image_sizes_for_select());
            $sanitized['popup_rec_thumb_size'] = in_array($input['popup_rec_thumb_size'], $allowed_sizes) ? $input['popup_rec_thumb_size'] : 'medium';
            
            $allowed_ratios = ['16:9', '4:3', '1:1', '3:4', 'auto'];
            $sanitized['popup_rec_thumb_aspect_ratio'] = in_array($input['popup_rec_thumb_aspect_ratio'], $allowed_ratios) ? $input['popup_rec_thumb_aspect_ratio'] : '16:9';

            $allowed_fits = ['cover', 'contain'];
            $sanitized['popup_rec_thumb_fit'] = in_array($input['popup_rec_thumb_fit'], $allowed_fits) ? $input['popup_rec_thumb_fit'] : 'cover';
        }
        
        return $sanitized;
    }

    // === CALLBACKI DLA NOWEJ SEKCJI: USTAWIENIA MINIATURKI ===
    public function render_thumbnail_settings_section_info(): void {
        echo '<p>' . esc_html__('Zarządzaj wyglądem obrazka wyróżniającego w każdej rekomendacji.', 'pro_reader') . '</p>';
    }

    public function thumb_size_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_thumb_size'] ?? 'medium';
        $sizes = $this->get_image_sizes_for_select();

        echo '<select id="popup_rec_thumb_size" name="' . esc_attr(self::OPTION_NAME) . '[popup_rec_thumb_size]">';
        foreach ($sizes as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz rozmiar obrazka ładowany z biblioteki mediów. Większe rozmiary zapewniają lepszą jakość, ale wolniej się ładują.', 'pro_reader') . '</p>';
    }

    public function thumb_aspect_ratio_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
        $ratios = [
            '16:9'  => __('16:9 (Szeroki)', 'pro_reader'),
            '4:3'   => __('4:3 (Standardowy)', 'pro_reader'),
            '1:1'   => __('1:1 (Kwadrat)', 'pro_reader'),
            '3:4'   => __('3:4 (Portret)', 'pro_reader'),
            'auto'  => __('Auto (Dopasuj do wysokości obrazka)', 'pro_reader'),
        ];

        echo '<select id="popup_rec_thumb_aspect_ratio" name="' . esc_attr(self::OPTION_NAME) . '[popup_rec_thumb_aspect_ratio]">';
        foreach ($ratios as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Ta opcja kontroluje kształt kontenera obrazka tylko w układzie wertykalnym.', 'pro_reader') . '</p>';
    }

    public function thumb_fit_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_thumb_fit'] ?? 'cover';
        $fits = [
            'cover'   => __('Wypełnij (Cover)', 'pro_reader'),
            'contain' => __('Dopasuj (Contain)', 'pro_reader'),
        ];
        
        echo '<select id="popup_rec_thumb_fit" name="' . esc_attr(self::OPTION_NAME) . '[popup_rec_thumb_fit]">';
        foreach ($fits as $key => $name) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('"Wypełnij" przycina obraz, aby wypełnił cały kontener. "Dopasuj" skaluje go, aby był w pełni widoczny.', 'pro_reader') . '</p>';
    }

    private function get_image_sizes_for_select(): array {
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

    // === Istniejące callbacki (bez istotnych zmian) ===
    public function render_triggers_section_info(): void { echo '<p>' . esc_html__('Skonfiguruj, kiedy ma pojawić się popup.', 'pro_reader') . '</p>'; }
    public function render_content_section_info(): void { echo '<p>' . esc_html__('Zdefiniuj treść, która pojawi się nad listą rekomendacji.', 'pro_reader') . '</p>'; }
    public function render_recommendations_section_info(): void { echo '<p>' . esc_html__('Zarządzaj ogólnymi ustawieniami rekomendacji.', 'pro_reader') . '</p>'; }
    public function render_layout_builder_section_info(): void { echo '<p>' . esc_html__('Dostosuj wygląd i układ pojedynczego elementu na liście rekomendacji.', 'pro_reader') . '</p>'; }
    public function popup_enable_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_enable'] ?? '0'; printf('<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s /><label for="popup_enable"> %s</label>', esc_attr(self::OPTION_NAME), checked('1', $v, false), esc_html__('Aktywuj popup na stronie.', 'pro_reader')); }
    public function trigger_scroll_percent_enable_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_trigger_scroll_percent_enable'] ?? '1'; printf('<input type="checkbox" id="popup_trigger_scroll_percent_enable" name="%s[popup_trigger_scroll_percent_enable]" value="1" %s /><label for="popup_trigger_scroll_percent_enable"> %s</label>', esc_attr(self::OPTION_NAME), checked('1', $v, false), esc_html__('Aktywuj wyzwalacz', 'pro_reader')); }
    public function trigger_scroll_percent_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_trigger_scroll_percent'] ?? 85; printf('<input type="number" id="popup_trigger_scroll_percent" name="%s[popup_trigger_scroll_percent]" value="%d" min="1" max="100" /> %%', esc_attr(self::OPTION_NAME), esc_attr($v)); }
    public function trigger_time_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_trigger_time'] ?? 60; printf('<input type="number" id="popup_trigger_time" name="%s[popup_trigger_time]" value="%d" min="0" />', esc_attr(self::OPTION_NAME), esc_attr($v)); }
    public function trigger_scroll_up_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_trigger_scroll_up'] ?? '0'; printf('<input type="checkbox" id="popup_trigger_scroll_up" name="%s[popup_trigger_scroll_up]" value="1" %s /><label for="popup_trigger_scroll_up"> %s</label>', esc_attr(self::OPTION_NAME), checked('1', $v, false), esc_html__('Aktywuj wyzwalacz', 'pro_reader')); }
    public function popup_content_main_callback(): void { $o = get_option(self::OPTION_NAME, []); $c = $o['popup_content_main'] ?? ''; wp_editor($c, 'popup_content_main_editor', ['textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_content_main]', 'media_buttons' => true, 'teeny' => false, 'textarea_rows' => 8]); }
    public function recommendations_count_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_recommendations_count'] ?? 3; printf('<input type="number" id="popup_recommendations_count" name="%s[popup_recommendations_count]" value="%d" min="1" max="10" />', esc_attr(self::OPTION_NAME), esc_attr($v)); }
    public function recommendations_layout_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_recommendations_layout'] ?? 'list'; echo '<select id="popup_recommendations_layout" name="' . esc_attr(self::OPTION_NAME) . '[popup_recommendations_layout]"><option value="list" ' . selected($v, 'list', false) . '>' . esc_html__('Lista', 'pro_reader') . '</option><option value="grid" ' . selected($v, 'grid', false) . '>' . esc_html__('Siatka', 'pro_reader') . '</option></select>'; }
    public function recommendations_link_text_callback(): void { $o = get_option(self::OPTION_NAME, []); $c = $o['popup_recommendations_link_text'] ?? 'Zobacz więcej →'; wp_editor($c, 'popup_recommendations_link_text_editor', ['textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_recommendations_link_text]', 'media_buttons' => false, 'teeny' => true, 'textarea_rows' => 3]); }
    public function layout_item_structure_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_rec_item_layout'] ?? 'vertical'; printf('<fieldset><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="vertical" %2$s> %3$s</label><br><label><input type="radio" name="%1$s[popup_rec_item_layout]" value="horizontal" %4$s> %5$s</label></fieldset>', esc_attr(self::OPTION_NAME), checked($v, 'vertical', false), esc_html__('Wertykalny (obraz pod treścią)', 'pro_reader'), checked($v, 'horizontal', false), esc_html__('Horyzontalny (obraz obok treści)', 'pro_reader')); }
    public function layout_components_order_callback(): void { $o=get_option(self::OPTION_NAME,[]);$g=!empty($o['popup_rec_group_meta']);$d=['thumbnail'=>__('Miniaturka','pro_reader'),'meta'=>$g?__('Data i Kategoria','pro_reader'):__('Metadane','pro_reader'),'title'=>__('Tytuł','pro_reader'),'excerpt'=>__('Zajawka','pro_reader'),'link'=>__('Link','pro_reader')];$r=$o['popup_rec_components_order']??array_keys($d);$v=$o['popup_rec_components_visibility']??array_fill_keys(array_keys($d),'1');foreach(array_keys($d)as$k){if(!in_array($k,$r,true)){$r[]=$k;}}echo '<ul id="rep-layout-builder" style="border:1px solid #ccd0d4;padding:10px;max-width:400px;background:#fff;">';foreach($r as$k){if(!isset($d[$k]))continue;$l=$d[$k];$c=isset($v[$k])&&$v[$k]==='1';printf('<li style="padding:8px 12px;border:1px solid #ddd;margin-bottom:5px;background:#f9f9f9;cursor:move;display:flex;align-items:center;justify-content:space-between;"><div><input type="checkbox" id="v_%1$s" name="%2$s[popup_rec_components_visibility][%1$s]" value="1" %3$s><label for="v_%1$s" style="user-select:none;">%4$s</label></div><span class="dashicons dashicons-menu" style="color:#999;"></span><input type="hidden" name="%2$s[popup_rec_components_order][]" value="%1$s"></li>',esc_attr($k),esc_attr(self::OPTION_NAME),checked($c,true,false),esc_html($l));}echo '</ul>'; }
    public function layout_excerpt_length_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_rec_excerpt_length'] ?? 15; printf('<input type="number" id="popup_rec_excerpt_length" name="%s[popup_rec_excerpt_length]" value="%d" min="0" />', esc_attr(self::OPTION_NAME), esc_attr($v)); }
    
    
    public function layout_group_meta_callback(): void { $o = get_option(self::OPTION_NAME, []); $v = $o['popup_rec_group_meta'] ?? '1'; printf('<input type="checkbox" id="popup_rec_group_meta" name="%s[popup_rec_group_meta]" value="1" %s /><label for="popup_rec_group_meta"> %s</label>', esc_attr(self::OPTION_NAME), checked('1', $v, false), esc_html__('Grupuj datę i kategorię', 'pro_reader')); }
    public function layout_excerpt_limit_type_callback(): void {
    $options = get_option(self::OPTION_NAME, []);
    $value = $options['popup_rec_excerpt_limit_type'] ?? 'words'; // Domyślnie 'słowa'
    
    printf(
        '<fieldset>
            <label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="words" %2$s> %3$s</label><br>
            <label><input type="radio" name="%1$s[popup_rec_excerpt_limit_type]" value="lines" %4$s> %5$s</label>
        </fieldset>',
        esc_attr(self::OPTION_NAME),
        checked($value, 'words', false),
        __('Limit słów', 'pro_reader'),
        checked($value, 'lines', false),
        __('Limit linii', 'pro_reader')
    );
    echo '<p class="description">' . esc_html__('Wybierz, w jaki sposób ma być ograniczana długość zajawki.', 'pro_reader') . '</p>';
}
    
    public function layout_excerpt_lines_callback(): void {
    $options = get_option(self::OPTION_NAME, []);
    $value = $options['popup_rec_excerpt_lines'] ?? 0;
    printf(
        '<input type="number" id="popup_rec_excerpt_lines" name="%s[popup_rec_excerpt_lines]" value="%d" min="0" style="width: 80px;" />',
        esc_attr(self::OPTION_NAME),
        esc_attr($value)
    );
    echo '<p class="description">' . esc_html__('Ustawia maksymalną liczbę widocznych linii tekstu dla zajawki. Ustaw na 0, aby wyłączyć tę opcję i polegać tylko na limicie słów.', 'pro_reader') . '</p>';
}
}

