<?php

namespace ReaderEngagementPro\Admin;

/**
 * Klasa zarządzająca polami ustawień dla modułu Paska Postępu.
 */
class Settings_Progress_Bar {

    private const OPTION_NAME = 'reader_engagement_pro_options';
    private array $options = [];
    

    public function __construct() {
        // Wczytanie opcji raz, aby uniknąć wielokrotnego odpytywania bazy danych.
        $this->options = get_option(self::OPTION_NAME, []);
        add_action('admin_init', [$this, 'page_init']);
    }

    
    public function page_init() {

        add_settings_section(
            'progress_bar_main_section',                   // Unikalne ID sekcji
            __('Ustawienia Wyglądu i Pozycji', 'pro_reader'), // Tytuł sekcji
            null,                                          // Callback renderujący opis sekcji (opcjonalny)
            'reader-engagement-pro-progress-bar'           // Slug strony/zakładki, na której ma się pojawić
        );

        add_settings_section(
            'progress_bar_advanced_section',
            __('Ustawienia Zaawansowane', 'pro_reader'),
            null,
            'reader-engagement-pro-progress-bar'
        );
        $this->register_fields();
    }
    
    private function register_fields() {
        // Pola w sekcji 'main'
        add_settings_field('position', __('Pozycja paska', 'pro_reader'), [$this, 'position_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('bar_height', __('Wysokość paska', 'pro_reader'), [$this, 'bar_height_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('bar_width', __('Szerokość paska', 'pro_reader'), [$this, 'bar_width_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('color_start', __('Kolor startowy paska', 'pro_reader'), [$this, 'color_start_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('color_end', __('Kolor końcowy paska', 'pro_reader'), [$this, 'color_end_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('opacity', __('Przezroczystość paska', 'pro_reader'), [$this, 'opacity_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('label_start', __('Tekst początkowy', 'pro_reader'), [$this, 'label_start_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        add_settings_field('label_end', __('Tekst końcowy', 'pro_reader'), [$this, 'label_end_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_main_section');
        
        add_settings_field('show_percentage', __('Pokaż procent postępu', 'pro_reader'), [$this, 'show_percentage_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_advanced_section');
        add_settings_field('percentage_position', __('Pozycja licznika procentowego', 'pro_reader'), [$this, 'percentage_position_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_advanced_section');
        add_settings_field('content_selector', __('Selektor treści', 'pro_reader'), [$this, 'content_selector_callback'], 'reader-engagement-pro-progress-bar', 'progress_bar_advanced_section');
    }

/**
     * KLUCZOWA ZMIANA: Ta funkcja sanituje TYLKO swoje pola,
     * ale łączy je z istniejącymi opcjami, aby nie skasować ustawień z innych zakładek.
     *
     * @param array $input Dane z formularza.
     * @return array Przetworzone i bezpieczne dane.
     */
    public function sanitize(array $input): array {
    // Zawsze zaczynamy od aktualnych, zapisanych opcji, aby nie skasować ustawień z innych zakładek.
    $sanitized = get_option(self::OPTION_NAME, []);
    if (!isset($input['position'])) {
        return $sanitized;
    }

    $sanitized['position'] = sanitize_key($input['position']);
     // Sanitacja nowych pól
        $sanitized['bar_height'] = isset($input['bar_height']) ? absint($input['bar_height']) : 20;
        $sanitized['bar_width'] = isset($input['bar_width']) ? max(1, min(100, intval($input['bar_width']))) : 100;

    
        $sanitized['color_start'] = isset($input['color_start']) ? sanitize_hex_color($input['color_start']) : '#4facfe';
        $sanitized['color_end'] = isset($input['color_end']) ? sanitize_hex_color($input['color_end']) : '#43e97b';
        if (isset($input['opacity'])) {
            $opacity = str_replace(',', '.', $input['opacity']);
            $sanitized['opacity'] = (string) max(0.0, min(1.0, floatval($opacity)));
        }
        $sanitized['label_start'] = isset($input['label_start']) ? sanitize_text_field($input['label_start']) : 'Start';
        $sanitized['label_end'] = isset($input['label_end']) ? sanitize_text_field($input['label_end']) : 'Meta';
        $sanitized['content_selector'] = isset($input['content_selector']) ? sanitize_text_field($input['content_selector']) : '';
        $sanitized['show_percentage'] = (isset($input['show_percentage']) && $input['show_percentage'] === '1') ? '1' : '0';
        
        if (isset($input['percentage_position']) && in_array($input['percentage_position'], ['left', 'center', 'right'])) {
        $sanitized['percentage_position'] = sanitize_key($input['percentage_position']);
        } else {
        $sanitized['percentage_position'] = 'center'; // Wartość domyślna
        }
    return $sanitized;
}

    // --- CALLBACKS --- //
    
    public function position_callback() {
        $current_position = esc_attr($this->options['position'] ?? 'top');
        echo '<select id="position" name="' . self::OPTION_NAME . '[position]">';
        echo '<option value="top"' . selected($current_position, 'top', false) . '>Góra</option>';
        echo '<option value="bottom"' . selected($current_position, 'bottom', false) . '>Dół</option>';
        echo '</select>';
    }
    
    
    public function bar_height_callback() {
        printf(
            '<input type="number" id="bar_height" name="%s[bar_height]" value="%s" min="1" step="1" /> px
            <p class="description">Domyślna wysokość to 20px.</p>',
            self::OPTION_NAME,
            esc_attr($this->options['bar_height'] ?? '20')
        );
    }

    public function bar_width_callback() {
        printf(
            '<input type="number" id="bar_width" name="%s[bar_width]" value="%s" min="1" max="100" step="1" /> %%
            <p class="description">Szerokość paska w procentach (1-100). Pasek zostanie wyśrodkowany, jeśli szerokość jest mniejsza niż 100%%.</p>', // <-- TUTAJ JEST ZMIANA (100%%)
            self::OPTION_NAME,
            esc_attr($this->options['bar_width'] ?? '100')
        );
    }

    public function color_start_callback() {
        printf(
            '<input type="text" id="color_start" name="%s[color_start]" value="%s" class="wp-color-picker-field" data-default-color="#4facfe" />',
            self::OPTION_NAME,
            esc_attr($this->options['color_start'] ?? '#4facfe')
        );
    }

    public function color_end_callback() {
        printf(
            '<input type="text" id="color_end" name="%s[color_end]" value="%s" class="wp-color-picker-field" data-default-color="#43e97b" />',
            self::OPTION_NAME,
            esc_attr($this->options['color_end'] ?? '#43e97b')
        );
    }

    public function opacity_callback() {
        printf(
            '<input type="number" id="opacity" name="%s[opacity]" value="%s" min="0" max="1" step="0.1" />
            <p class="description">Wprowadź wartość od 0.0 (przezroczysty) do 1.0 (widoczny).</p>',
            self::OPTION_NAME,
            esc_attr($this->options['opacity'] ?? '1.0')
        );
    }
    
    public function percentage_position_callback(): void {
    $value = $this->options['percentage_position'] ?? 'center'; // Domyślnie 'center'
    $name = self::OPTION_NAME . '[percentage_position]';
     echo '<select id="percentage_position" name="' . esc_attr($name) . '">';
    echo '<option value="left"' . selected($value, 'left', false) . '>' . esc_html__('Do lewej', 'pro_reader') . '</option>';
    echo '<option value="center"' . selected($value, 'center', false) . '>' . esc_html__('Na środku', 'pro_reader') . '</option>';
    echo '<option value="right"' . selected($value, 'right', false) . '>' . esc_html__('Do prawej', 'pro_reader') . '</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__('Wybierz, gdzie na pasku ma być wyświetlany licznik procentowy. Działa tylko, gdy opcja "Pokaż procent postępu" jest włączona.', 'pro_reader') . '</p>';
}
    
    public function label_start_callback(): void {
        printf(
            '<input type="text" id="label_start" name="%s[label_start]" value="%s" />',
            self::OPTION_NAME,
            esc_attr($this->options['label_start'] ?? 'Start')
        );
    }

    public function label_end_callback(): void {
        printf(
            '<input type="text" id="label_end" name="%s[label_end]" value="%s" />',
            self::OPTION_NAME,
            esc_attr($this->options['label_end'] ?? 'Meta')
        );
    }
    
    public function content_selector_callback(): void {
        printf(
            '<input type="text" id="content_selector" name="%s[content_selector]" value="%s" class="regular-text" placeholder=".entry-content" />',
            self::OPTION_NAME,
            esc_attr($this->options['content_selector'] ?? '')
        );
        echo '<p class="description">Podaj selektor CSS dla kontenera treści (np. <code>.entry-content</code>, <code>#main</code>). Poprawia dokładność obliczeń. Pozostaw puste, aby mierzyć postęp dla całej strony.</p>';
    }

    public function show_percentage_callback(): void {
        $value = $this->options['show_percentage'] ?? '0';
        printf(
            '<input type="checkbox" id="show_percentage" name="%s[show_percentage]" value="1" %s /> <label for="show_percentage">%s</label>',
            self::OPTION_NAME,
            checked('1', $value, false),
            esc_html__('Wyświetlaj procentowy postęp na pasku.', 'pro_reader')
        );
    }
}