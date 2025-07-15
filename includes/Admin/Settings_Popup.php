<?php

namespace ReaderEngagementPro\Admin;

/**
 * Klasa zarządzająca polami ustawień dla modułu Popup "Czytaj Więcej".
 */
class Settings_Popup {

    /**
     * @var string Klucz opcji w bazie danych WordPress. Używamy tego samego, co dla paska.
     */
    private const OPTION_NAME = 'reader_engagement_pro_options';

    public function __construct() {
        add_action('admin_init', [$this, 'page_init']);
    }

    
    public function page_init(): void {
        
        // === SEKCJA 1: WYZWALACZE (bez zmian) ===
        add_settings_section(
            'popup_triggers_section',               
            __('Ustawienia Wyzwalaczy', 'pro_reader'), 
            [$this, 'render_triggers_section_info'],
            'reader-engagement-pro-popup'
        );
        add_settings_field('popup_enable', __('Włącz Moduł Popup', 'pro_reader'), [$this, 'popup_enable_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_percent_enable', __('Wyzwalacz: Procent przewinięcia', 'pro_reader'), [$this, 'trigger_scroll_percent_enable_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_percent', __('Wartość procentowa', 'pro_reader'), [$this, 'trigger_scroll_percent_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_time', __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'), [$this, 'trigger_time_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        add_settings_field('popup_trigger_scroll_up', __('Wyzwalacz: Scroll w górę', 'pro_reader'), [$this, 'trigger_scroll_up_callback'], 'reader-engagement-pro-popup', 'popup_triggers_section');
        
        // === SEKCJA 2: TREŚĆ POPUPA (bez zmian) ===
        add_settings_section(
            'popup_content_section',
            __('Treść Popupa', 'pro_reader'),
            [$this, 'render_content_section_info'],
            'reader-engagement-pro-popup'
        );
        add_settings_field('popup_content_main', __('Edytor treści', 'pro_reader'), [$this, 'popup_content_main_callback'], 'reader-engagement-pro-popup', 'popup_content_section');

        // === SEKCJA 3: REKOMENDACJE (zmieniona) ===
        add_settings_section(
            'popup_recommendations_section',
            __('Ustawienia Ogólne Rekomendacji', 'pro_reader'),
            [$this, 'render_recommendations_section_info'],
            'reader-engagement-pro-popup'
        );
        add_settings_field('popup_recommendations_count', __('Liczba rekomendowanych wpisów', 'pro_reader'), [$this, 'recommendations_count_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        add_settings_field('popup_recommendations_layout', __('Domyślny układ (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        add_settings_field('popup_recommendations_link_text', __('Treść linku do artykułu', 'pro_reader'), [$this, 'recommendations_link_text_callback'], 'reader-engagement-pro-popup', 'popup_recommendations_section');
        
        // === NOWA SEKCJA 4: KONSTRUKTOR UKŁADU REKOMENDACJI ===
        add_settings_section(
            'popup_layout_builder_section',
            __('Konstruktor Układu Rekomendacji', 'pro_reader'),
            [$this, 'render_layout_builder_section_info'],
            'reader-engagement-pro-popup'
        );
        
        add_settings_field(
            'popup_rec_item_layout',
            __('Struktura pojedynczej rekomendacji', 'pro_reader'),
            [$this, 'layout_item_structure_callback'],
            'reader-engagement-pro-popup',
            'popup_layout_builder_section'
        );
        
        add_settings_field(
            'popup_rec_components_order',
            __('Kolejność i widoczność elementów', 'pro_reader'),
            [$this, 'layout_components_order_callback'],
            'reader-engagement-pro-popup',
            'popup_layout_builder_section'
        );

        add_settings_field(
            'popup_rec_excerpt_length',
            __('Długość zajawki (w słowach)', 'pro_reader'),
            [$this, 'layout_excerpt_length_callback'],
            'reader-engagement-pro-popup',
            'popup_layout_builder_section'
        );

        add_settings_field(
            'popup_rec_group_meta',
            __('Grupowanie metadanych', 'pro_reader'),
            [$this, 'layout_group_meta_callback'],
            'reader-engagement-pro-popup',
            'popup_layout_builder_section'
        );
    }

    public function sanitize(array $input): array {
        $sanitized = get_option(self::OPTION_NAME, []);

        // Sanitacja starych pól (jeśli formularz został wysłany z innej zakładki, te klucze nie będą obecne)
        if (isset($input['popup_trigger_time'])) {
            $sanitized['popup_enable'] = (isset($input['popup_enable']) && $input['popup_enable'] === '1') ? '1' : '0';
            $sanitized['popup_trigger_scroll_up'] = (isset($input['popup_trigger_scroll_up']) && $input['popup_trigger_scroll_up'] === '1') ? '1' : '0';
            $sanitized['popup_trigger_scroll_percent_enable'] = (isset($input['popup_trigger_scroll_percent_enable']) && $input['popup_trigger_scroll_percent_enable'] === '1') ? '1' : '0';   
            $scroll_percent = absint($input['popup_trigger_scroll_percent']);
            $sanitized['popup_trigger_scroll_percent'] = max(1, min(100, $scroll_percent));
            $sanitized['popup_trigger_time'] = absint($input['popup_trigger_time']);
            $sanitized['popup_content_main'] = wp_kses_post($input['popup_content_main']);
            $count = absint($input['popup_recommendations_count']);
            $sanitized['popup_recommendations_count'] = max(1, min(10, $count));
            $layout = sanitize_key($input['popup_recommendations_layout']);
            $sanitized['popup_recommendations_layout'] = in_array($layout, ['list', 'grid']) ? $layout : 'list';
            $sanitized['popup_recommendations_link_text'] = wp_kses_post($input['popup_recommendations_link_text']);
        }

        // NOWOŚĆ: Sanitacja pól konstruktora układu
        if (isset($input['popup_rec_item_layout'])) {
            $item_layout = sanitize_key($input['popup_rec_item_layout']);
            $sanitized['popup_rec_item_layout'] = in_array($item_layout, ['vertical', 'horizontal']) ? $item_layout : 'vertical';
            
            $sanitized['popup_rec_excerpt_length'] = absint($input['popup_rec_excerpt_length']);
            $sanitized['popup_rec_group_meta'] = (isset($input['popup_rec_group_meta']) && $input['popup_rec_group_meta'] === '1') ? '1' : '0';

            // Sanitacja kolejności i widoczności komponentów
            $allowed_components = ['thumbnail', 'meta', 'title', 'excerpt', 'link'];
            $order = $input['popup_rec_components_order'] ?? [];
            $sanitized_order = [];
            if (is_array($order)) {
                foreach ($order as $component_key) {
                    if (in_array($component_key, $allowed_components, true)) {
                        $sanitized_order[] = sanitize_key($component_key);
                    }
                }
            }
            $sanitized['popup_rec_components_order'] = $sanitized_order;
            
            // Sanitacja widoczności komponentów
            $visibility = $input['popup_rec_components_visibility'] ?? [];
            $sanitized_visibility = [];
                if (is_array($visibility)) {
                    foreach ($allowed_components as $component) {
                        $sanitized_visibility[$component] = isset($visibility[$component]) && $visibility[$component] === '1' ? '1' : '0';
                }
            }
            $sanitized['popup_rec_components_visibility'] = $sanitized_visibility;
        }
        
        return $sanitized;
    }

    // === CALLBACKI DLA NOWEJ SEKCJI: KONSTRUKTOR UKŁADU ===

    public function render_layout_builder_section_info(): void {
        echo '<p>' . esc_html__('W tej sekcji możesz precyzyjnie dostosować wygląd i układ pojedynczego elementu na liście rekomendacji.', 'pro_reader') . '</p>';
    }

    public function layout_item_structure_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_item_layout'] ?? 'vertical';
        ?>
        <fieldset>
            <label>
                <input type="radio" name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_rec_item_layout]" value="vertical" <?php checked($value, 'vertical'); ?>>
                <?php esc_html_e('Wertykalny (elementy jeden pod drugim)', 'pro_reader'); ?>
                <p class="description"><?php esc_html_e('Idealny dla układu siatki (grid). Miniaturka na górze, treść pod nią.', 'pro_reader'); ?></p>
            </label>
            <br>
            <label>
                <input type="radio" name="<?php echo esc_attr(self::OPTION_NAME); ?>[popup_rec_item_layout]" value="horizontal" <?php checked($value, 'horizontal'); ?>>
                <?php esc_html_e('Horyzontalny (obrazek po lewej, treść po prawej)', 'pro_reader'); ?>
                <p class="description"><?php esc_html_e('Klasyczny układ listy.', 'pro_reader'); ?></p>
            </label>
        </fieldset>
        <?php
    }

    public function layout_components_order_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $is_meta_grouped = !empty($options['popup_rec_group_meta']);

        $default_components = [
            'thumbnail' => __('Miniaturka', 'pro_reader'),
            'meta'      => $is_meta_grouped ? __('Data i Kategoria', 'pro_reader') : __('Metadane (Data/Kategoria)', 'pro_reader'),
            'title'     => __('Tytuł', 'pro_reader'),
            'excerpt'   => __('Zajawka', 'pro_reader'),
            'link'      => __('Link "Zobacz więcej"', 'pro_reader'),
        ];
        
        $order = $options['popup_rec_components_order'] ?? array_keys($default_components);
        $visibility = $options['popup_rec_components_visibility'] ?? array_fill_keys(array_keys($default_components), '1');

        // Upewnienie się, że wszystkie komponenty są na liście, nawet jeśli zostały dodane w nowej wersji wtyczki
        foreach (array_keys($default_components) as $key) {
            if (!in_array($key, $order, true)) {
                $order[] = $key;
            }
        }
        
        echo '<p class="description" style="margin-bottom: 15px;">' . esc_html__('Przeciągnij elementy, aby zmienić ich kolejność. Odznacz pole, aby ukryć element.', 'pro_reader') . '</p>';
        echo '<ul id="rep-layout-builder" style="border:1px solid #ccd0d4; padding: 10px; max-width: 400px; background: #fff;">';

        foreach ($order as $key) {
            if (!isset($default_components[$key])) continue;

            $label = $default_components[$key];
            $is_checked = isset($visibility[$key]) && $visibility[$key] === '1';

            printf(
                '<li style="padding: 8px 12px; border: 1px solid #ddd; margin-bottom: 5px; background: #f9f9f9; cursor: move; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <input type="checkbox" id="visibility_%1$s" name="%2$s[popup_rec_components_visibility][%1$s]" value="1" %3$s>
                        <label for="visibility_%1$s" style="user-select: none;">%4$s</label>
                    </div>
                    <span class="dashicons dashicons-menu" style="color: #999;"></span>
                    <input type="hidden" name="%2$s[popup_rec_components_order][]" value="%1$s">
                </li>',
                esc_attr($key),
                esc_attr(self::OPTION_NAME),
                checked($is_checked, true, false),
                esc_html($label)
            );
        }
        echo '</ul>';
    }

    public function layout_excerpt_length_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_excerpt_length'] ?? 15;
        printf(
            '<input type="number" id="popup_rec_excerpt_length" name="%s[popup_rec_excerpt_length]" value="%d" min="0" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Ustaw 0, aby wyłączyć przycinanie i wyświetlić pełną, automatyczną zajawkę.', 'pro_reader') . '</p>';
    }

    public function layout_group_meta_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_rec_group_meta'] ?? '1';
        printf(
            '<input type="checkbox" id="popup_rec_group_meta" name="%s[popup_rec_group_meta]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_rec_group_meta"> ' . esc_html__('Grupuj datę i kategorię w jednej linii', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Wymaga odświeżenia strony po zmianie, aby zaktualizować etykietę na liście powyżej.', 'pro_reader') . '</p>';
    }

  
    public function render_triggers_section_info(): void
    { echo '<p>' . esc_html__('W tej sekcji skonfigurujesz, kiedy i w jakich okolicznościach ma pojawić się popup.', 'pro_reader') . '</p>'; }
    public function render_content_section_info(): void 
    { echo '<p>' . esc_html__('Tutaj możesz zdefiniować treść, która pojawi się nad listą polecanych artykułów.', 'pro_reader') . '</p>'; }
    public function render_recommendations_section_info(): void { echo '<p>' . esc_html__('Zarządzaj ogólnymi ustawieniami dotyczącymi rekomendowanych artykułów i stron.', 'pro_reader') . '</p>'; }
    public function popup_enable_callback(): void 
    { $options = get_option(self::OPTION_NAME, []);
    $value = $options['popup_enable'] ?? '0'; printf('<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s /><label for="popup_enable"> %s</label><p class="description">%s</p>', esc_attr(self::OPTION_NAME),
    checked('1', $value, false), esc_html__('Aktywuj popup na stronie.', 'pro_reader'), esc_html__('Główny włącznik modułu popup.', 'pro_reader')); }
    public function trigger_scroll_percent_enable_callback(): void 
    { $options = get_option(self::OPTION_NAME, []);
    $value = $options['popup_trigger_scroll_percent_enable'] ?? '1'; printf('<input type="checkbox" id="popup_trigger_scroll_percent_enable" name="%s[popup_trigger_scroll_percent_enable]" value="1" %s /><label for="popup_trigger_scroll_percent_enable"> %s</label><p class="description">%s</p>', esc_attr(self::OPTION_NAME),
    checked('1', $value, false), esc_html__('Aktywuj wyzwalacz', 'pro_reader'), esc_html__('Popup pojawi się, gdy użytkownik przewinie określoną część strony.', 'pro_reader')); }
    public function trigger_scroll_percent_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $value = $options['popup_trigger_scroll_percent'] ?? 85; printf('<input type="number" id="popup_trigger_scroll_percent" name="%s[popup_trigger_scroll_percent]" value="%d" min="1" max="100" /> %%<p class="description">%s</p>', esc_attr(self::OPTION_NAME),
    esc_attr($value), esc_html__('Rekomendowane: 70-90.', 'pro_reader')); }
    public function trigger_time_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $value = $options['popup_trigger_time'] ?? 60; printf('<input type="number" id="popup_trigger_time" name="%s[popup_trigger_time]" value="%d" min="0" /><p class="description">%s</p>', esc_attr(self::OPTION_NAME), 
    esc_attr($value), esc_html__('Wpisz 0, aby wyłączyć ten wyzwalacz.', 'pro_reader')); }
    public function trigger_scroll_up_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $value = $options['popup_trigger_scroll_up'] ?? '0'; printf('<input type="checkbox" id="popup_trigger_scroll_up" name="%s[popup_trigger_scroll_up]" value="1" %s /><label for="popup_trigger_scroll_up"> %s</label><p class="description">%s</p>', esc_attr(self::OPTION_NAME),
    checked('1', $value, false), esc_html__('Aktywuj wyzwalacz', 'pro_reader'), esc_html__('Popup pojawi się, gdy użytkownik zacznie przewijać stronę w górę.', 'pro_reader')); }
    public function popup_content_main_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $content = $options['popup_content_main'] ?? ''; $settings = ['textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_content_main]', 'media_buttons' => true, 'teeny' => false, 'textarea_rows' => 10,]; wp_editor($content, 'popup_content_main_editor', $settings); echo '<p class="description">' . esc_html__('Ta treść zostanie wyświetlona w oknie popup nad listą rekomendacji.', 'pro_reader') . '</p>'; }
    public function recommendations_count_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $value = $options['popup_recommendations_count'] ?? 3; printf('<input type="number" id="popup_recommendations_count" name="%s[popup_recommendations_count]" value="%d" min="1" max="10" /><p class="description">%s</p>', esc_attr(self::OPTION_NAME),
    esc_attr($value), esc_html__('Wybierz, ile wpisów ma się pojawić w rekomendacjach (1-10).', 'pro_reader')); }
    public function recommendations_layout_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $value = $options['popup_recommendations_layout'] ?? 'list'; echo '<select id="popup_recommendations_layout" name="' . esc_attr(self::OPTION_NAME) . '[popup_recommendations_layout]"><option value="list" ' . selected($value, 'list', false) . '>' . esc_html__('Lista (jeden pod drugim)', 'pro_reader') . '</option><option value="grid" ' . selected($value, 'grid', false) . '>' . esc_html__('Siatka (jeden obok drugiego)', 'pro_reader') . '</option></select><p class="description">' . esc_html__('Wybierz, jak mają być wyświetlane rekomendowane artykuły w popupie.', 'pro_reader') . '</p>'; }
    public function recommendations_link_text_callback(): void 
    { $options = get_option(self::OPTION_NAME, []); 
    $content = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →'; $settings = ['textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_recommendations_link_text]', 'media_buttons' => false, 'teeny' => true, 'textarea_rows' => 5,]; wp_editor($content, 'popup_recommendations_link_text_editor', $settings); echo '<p class="description">' . esc_html__('Wprowadź treść, która będzie wyświetlana jako link.', 'pro_reader') . '</p>'; }
}