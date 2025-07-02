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
        

        add_settings_section(
            'popup_triggers_section',               // Unikalne ID sekcji
            __('Ustawienia Wyzwalaczy', 'pro_reader'), // Tytuł sekcji
            [$this, 'render_triggers_section_info'],         // Funkcja renderująca opis pod tytułem sekcji
            'reader-engagement-pro-popup'           // Slug strony/zakładki, na której ma się pojawić
        );

         // Pole: Włącz/Wyłącz moduł Popup
        add_settings_field(
            'popup_enable',                               // ID pola
            __('Włącz Moduł Popup', 'pro_reader'),        // Etykieta pola
            [$this, 'popup_enable_callback'],             // Funkcja renderująca HTML pola
            'reader-engagement-pro-popup',                // Slug strony/zakładki
            'popup_triggers_section'                      // Sekcja, do której należy pole
        );
        
        add_settings_field(
            'popup_trigger_scroll_percent_enable',
            __('Wyzwalacz: Procent przewinięcia', 'pro_reader'),
            [$this, 'trigger_scroll_percent_enable_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );

          // Pole: Wyzwalacz procentowy
        add_settings_field(
            'popup_trigger_scroll_percent',
            __('Wyzwalacz: Procent przewinięcia', 'pro_reader'),
            [$this, 'trigger_scroll_percent_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );

         // Pole: Wyzwalacz czasowy
        add_settings_field(
            'popup_trigger_time',
            __('Wyzwalacz: Czas na stronie (sekundy)', 'pro_reader'),
            [$this, 'trigger_time_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );
        
         // Pole: Wyzwalacz kierunku scrolla
        add_settings_field(
            'popup_trigger_scroll_up',
            __('Wyzwalacz: Scroll w górę', 'pro_reader'),
            [$this, 'trigger_scroll_up_callback'],
            'reader-engagement-pro-popup',
            'popup_triggers_section'
        );
        
         // --- NOWA SEKCJA 2: TREŚĆ POPUPA ---
        add_settings_section(
            'popup_content_section',
            __('Treść Popupa', 'pro_reader'),
            [$this, 'render_content_section_info'],
            'reader-engagement-pro-popup'
        );

        add_settings_field(
            'popup_content_main',
            __('Edytor treści', 'pro_reader'),
            [$this, 'popup_content_main_callback'], // Nowy callback
            'reader-engagement-pro-popup',
            'popup_content_section'
        );


    }

     /**
     * Wyświetla informacyjny tekst pod tytułem sekcji wyzwalaczy.
     */
    public function render_triggers_section_info(): void {
        echo '<p>';
        esc_html_e('W tej sekcji skonfigurujesz, kiedy i w jakich okolicznościach ma pojawić się popup.', 'pro_reader');
        echo '</p>';
    }

    /**
     * NOWOŚĆ: Wyświetla informacyjny tekst pod tytułem sekcji treści.
     */
    public function render_content_section_info(): void {
        echo '<p>';
        esc_html_e('Tutaj możesz zdefiniować treść, która pojawi się nad listą polecanych artykułów. Możesz używać formatowania tekstu, a nawet dodawać obrazy.', 'pro_reader');
        echo '</p>';
    }

    /**
     * @param array $input Dane z formularza.
     * @return array Przetworzone i bezpieczne dane.
     */
    
    public function sanitize(array $input): array {
    // Zawsze zaczynamy od aktualnych, zapisanych opcji.
    $sanitized = get_option(self::OPTION_NAME, []);

    if (!isset($input['popup_trigger_time'])) {
        // Zwracamy opcje bez żadnych zmian, aby niczego przypadkiem nie nadpisać.
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
        
         // NOWOŚĆ: Sanitacja pola edytora WYSIWYG
        if (isset($input['popup_content_main'])) {
            // Używamy wp_kses_post, aby pozwolić na bezpieczny HTML (jak w postach), ale usunąć groźne skrypty.
            $sanitized['popup_content_main'] = wp_kses_post($input['popup_content_main']);
        }
        
        return $sanitized;
    }
    
    /*
     * CALLBACKI RENDERUJĄCE POLA FORMULARZA
     */

    /** Renderuje checkbox do włączania modułu. */
    public function popup_enable_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_enable'] ?? '0'; // Domyślnie wyłączony
        printf(
            '<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_enable">' . esc_html__('Aktywuj popup na stronie.', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Główny włącznik modułu popup. Pozostałe opcje działają tylko, gdy jest zaznaczony.', 'pro_reader') . '</p>';
    }

    public function trigger_scroll_percent_enable_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_percent_enable'] ?? '1'; // Domyślnie włączony
        printf(
            '<input type="checkbox" id="popup_trigger_scroll_percent_enable" name="%s[popup_trigger_scroll_percent_enable]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_trigger_scroll_percent_enable">' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
    }

    /** Renderuje pole numeryczne dla procentu przewinięcia. */
    public function trigger_scroll_percent_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_percent'] ?? 85; // Domyślnie 85%
        printf(
            '<input type="number" id="popup_trigger_scroll_percent" name="%s[popup_trigger_scroll_percent]" value="%d" min="1" max="100" /> %%',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Popup pojawi się, gdy użytkownik przewinie określoną część strony. Rekomendowane: 70-90.', 'pro_reader') . '</p>';
    }

    /** Renderuje pole numeryczne dla czasu spędzonego na stronie. */
    public function trigger_time_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_time'] ?? 60; // Domyślnie 60 sekund
        printf(
            '<input type="number" id="popup_trigger_time" name="%s[popup_trigger_time]" value="%d" min="0" />',
            esc_attr(self::OPTION_NAME),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__('Popup pojawi się po upływie określonej liczby sekund. Wpisz 0, aby wyłączyć ten wyzwalacz.', 'pro_reader') . '</p>';
    }

    /** Renderuje checkbox dla wyzwalacza scrolla w górę. */
    public function trigger_scroll_up_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_trigger_scroll_up'] ?? '0'; // Domyślnie wyłączony
        printf(
            '<input type="checkbox" id="popup_trigger_scroll_up" name="%s[popup_trigger_scroll_up]" value="1" %s />',
            esc_attr(self::OPTION_NAME),
            checked('1', $value, false)
        );
        echo '<label for="popup_trigger_scroll_up">' . esc_html__('Aktywuj wyzwalacz', 'pro_reader') . '</label>';
        echo '<p class="description">' . esc_html__('Popup pojawi się, gdy użytkownik zacznie przewijać stronę w górę (sugerując zamiar wyjścia).', 'pro_reader') . '</p>';
    }
    
    /**
     * NOWOŚĆ: Renderuje edytor treści WordPress (WYSIWYG).
     */
    public function popup_content_main_callback(): void {
        $options = get_option(self::OPTION_NAME, []);
        // Pobieramy zapisaną treść lub ustawiamy domyślną.
        $content = $options['popup_content_main'] ?? '<h2>Może Cię zainteresować?</h2><p>Sprawdź inne artykuły na naszym blogu.</p>';
        
        // Kluczowe ustawienia dla edytora.
        $settings = [
            // To jest najważniejsze: 'name' musi pasować do struktury tablicy opcji.
            'textarea_name' => esc_attr(self::OPTION_NAME) . '[popup_content_main]',
            'media_buttons' => true,  // Pozwól na dodawanie mediów.
            'teeny'         => false, // Użyj pełnej wersji edytora.
            'textarea_rows' => 10,    // Początkowa wysokość.
        ];

        // Wywołanie funkcji WordPressa, która generuje cały edytor.
        wp_editor($content, 'popup_content_main_editor', $settings);

        echo '<p class="description">' . esc_html__('Ta treść zostanie wyświetlona w oknie popup nad listą rekomendacji.', 'pro_reader') . '</p>';
    }
}