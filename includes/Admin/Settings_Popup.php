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
        
        // SEKCJA 1: WYZWALACZE
        add_settings_section(
            'popup_triggers_section',               
            __('Ustawienia Wyzwalaczy', 'pro_reader'), 
            [$this, 'render_triggers_section_info'],
            'reader-engagement-pro-popup'
        );

        add_settings_field(
            'popup_enable',
            __('Włącz Moduł Popup', 'pro_reader'),
            [$this, 'popup_enable_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );
        
        add_settings_field(
            'popup_trigger_scroll_percent_enable',
            __('Wyzwalacz: Procent przewinięcia', 'pro_reader'),
            [$this, 'trigger_scroll_percent_enable_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );

        add_settings_field(
            'popup_trigger_scroll_percent',
            __('Wartość procentowa', 'pro_reader'), // Zmieniono etykietę dla jasności
            [$this, 'trigger_scroll_percent_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );

        add_settings_field(
            'popup_trigger_time',
            __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'),
            [$this, 'trigger_time_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );
        
        add_settings_field(
            'popup_trigger_scroll_up',
            __('Wyzwalacz: Scroll w górę', 'pro_reader'),
            [$this, 'trigger_scroll_up_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );
        
        // SEKCJA 2: TREŚĆ POPUPA
        add_settings_section(
            'popup_content_section',
            __('Treść Popupa', 'pro_reader'),
            [$this, 'render_content_section_info'],
            'reader-engagement-pro-popup'
        );

        add_settings_field(
            'popup_content_main',
            __('Edytor treści', 'pro_reader'),
            [$this, 'popup_content_main_callback'],
            'reader-engagement-pro-popup',
            'popup_content_section'
        );

        // NOWA SEKCJA 3: REKOMENDACJE
        add_settings_section(
            'popup_recommendations_section',
            __('Rekomendacje', 'pro_reader'),
            [$this, 'render_recommendations_section_info'], // Nowa funkcja
            'reader-engagement-pro-popup'
        );
        
        // NOWE POLE: Liczba rekomendacji
        add_settings_field(
            'popup_recommendations_count',
            __('Liczba rekomendowanych wpisów', 'pro_reader'),
            [$this, 'recommendations_count_callback'], // Nowy callback
            'reader-engagement-pro-popup',
            'popup_recommendations_section'
        );
    }

    public function render_triggers_section_info(): void {
        echo '<p>' . esc_html__('W tej sekcji skonfigurujesz, kiedy i w jakich okolicznościach ma pojawić się popup.', 'pro_reader') . '</p>';
    }

    public function render_content_section_info(): void {
        echo '<p>' . esc_html__('Tutaj możesz zdefiniować treść, która pojawi się nad listą polecanych artykułów. Możesz używać formatowania tekstu, a nawet dodawać obrazy.', 'pro_reader') . '</p>';
    }

    /**
     * NOWOŚĆ: Wyświetla informacyjny tekst pod tytułem sekcji rekomendacji.
     */
    public function render_recommendations_section_info(): void {
        echo '<p>' . esc_html__('Zarządzaj ustawieniami dotyczącymi rekomendowanych artykułów i stron.', 'pro_reader') . '</p>';
    }
    
    public function sanitize(array $input): array {
        $sanitized = get_option(self::OPTION_NAME, []);

        if (!isset($input['popup_trigger_time'])) {
            return $sanitized;
        }

        $sanitized['popup_enable'] = (isset($input['popup_enable']) && $input['popup_enable'] === '1') ? '1' : '0';
        $sanitized['popup_trigger_scroll_up'] = (isset($input['popup_trigger_scroll_up']) && $input['popup_trigger_scroll_up'] === '1') ? '1' : '0';
        $sanitized['popup_trigger_scroll_percent_enable'] = (isset($input['popup_trigger_scroll_percent_enable']) && $input['popup_trigger_scroll_percent_enable'] === '1') ? '1' : '0';   
        
        if (isset($input['popup_trigger_scroll_percent'])) {
            $scroll_percent = absint($input['popup_trigger_scroll_percent']);
            $sanitized['popup_trigger_scroll_percent'] = max(1, min(100, $scroll_percent));
        }
        
        if (isset($input['popup_trigger_time'])) {
            $sanitized['popup_trigger_time'] = absint($input['popup_trigger_time']);
        }
        
        if (isset($input['popup_content_main'])) {
            $sanitized['popup_content_main'] = wp_kses_post($input['popup_content_main']);
        }

        // NOWOŚĆ: Sanitacja pola liczby rekomendacji.
        if (isset($input['popup_recommendations_count'])) {
            // absint() zamienia wartość na dodatnią liczbę całkowitą.
            $count = absint($input['popup_recommendations_count']);
            // Ograniczamy wartość do rozsądnego przedziału, np. od 1 do 10.
            $sanitized['popup_recommendations_count'] = max(1, min(10, $count));
        }
        
        return $sanitized;
    }
    
    /*
     * CALLBACKI RENDERUJĄCE POLA FORMULARZA
     */
     
    public function popup_enable_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_enable'] ?? '0';
        printf(
            '<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_enable"> ' . esc_html__('Aktywuj popup na stronie.', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Główny włącznik modułu popup. Pozostałe opcje działają tylko, gdy jest zaznaczony.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_percent_enable_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_percent_enable'] ?? '1';
        printf(
            '<input type="checkbox" id="popup_trigger_scroll_percent_enable" name="%s[popup_trigger_scroll_percent_enable]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_trigger_scroll_percent_enable"> ' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Popup pojawi się, gdy użytkownik przewinie określoną część strony.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_percent_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_percent'] ?? 85;
        printf(
            '<input type="number" id="popup_trigger_scroll_percent" name="%s[popup_trigger_scroll_percent]" value="%d" min="1" max="100" /> %%',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Rekomendowane: 70-90.', 'pro_reader') . '</p>';
    }

    public function trigger_time_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_time'] ?? 60;
        printf(
            '<input type="number" id="popup_trigger_time" name="%s[popup_trigger_time]" value="%d" min="0" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Popup pojawi się po upływie określonej liczby sekund. Wpisz 0, aby wyłączyć ten wyzwalacz.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_up_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_up'] ?? '0';
        printf(
            '<input type="checkbox" id="popup_trigger_scroll_up" name="%s[popup_trigger_scroll_up]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_trigger_scroll_up"> ' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Popup pojawi się, gdy użytkownik zacznie przewijać stronę w górę (sugerując zamiar wyjścia).', 'pro_reader') . '</p>';
    }
    
    public function popup_content_main_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $content = $options['popup_content_main'] ?? '<h2>Może Cię zainteresować?</h2><p>Sprawdź inne artykuły na naszym blogu.</p>';
        
        $settings = [
            'textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_content_main]',
            'media_buttons' => true,
            'teeny'         => false,
            'textarea_rows' => 10,
        ];
        wp_editor($content, 'popup_content_main_editor', $settings);
        echo '<p class="description">' . esc_html__('Ta treść zostanie wyświetlona w oknie popup nad listą rekomendacji.', 'pro_reader') . '</p>';
    }

    /**
     * NOWOŚĆ: Renderuje pole numeryczne dla liczby rekomendacji.
     */
    public function recommendations_count_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        // Ustawienie domyślnej wartości na 3, jeśli opcja nie istnieje.
        $value = $options['popup_recommendations_count'] ?? 3; 
        printf(
            '<input type="number" id="popup_recommendations_count" name="%s[popup_recommendations_count]" value="%d" min="1" max="10" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Wybierz, ile najnowszych wpisów i stron ma się pojawić w rekomendacjach. (Wartość od 1 do 10)', 'pro_reader') . '</p>';
    }
}