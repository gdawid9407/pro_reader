<?php

namespace ReaderEngagementPro\Admin;

/**
 * Klasa zarządzająca polami ustawień dla modułu Popup "Czytaj Więcej".
 * Na razie jest to szkielet przygotowany pod przyszłą implementację.
 */
class Settings_Popup {

    /**
     * @var string Klucz opcji w bazie danych WordPress. Używamy tego samego, co dla paska.
     */
    private const OPTION_NAME = 'reader_engagement_pro_options';

    public function __construct() {
        add_action('admin_init', [$this, 'page_init']);
    }

    /**
     * Rejestruje ustawienia, sekcje i pola w WordPress Settings API dla zakładki popupu.
     */
    public function page_init() {
        // Rejestruj grupę ustawień dla zakładki "Popup".
        // Nazwa grupy musi pasować do tej użytej w Settings_Page.php.
        register_setting(
            'reader_engagement_pro_popup_group', // Grupa dla tej zakładki
            self::OPTION_NAME,                   // Zapisujemy do tej samej, wspólnej tablicy opcji
            ['type' => 'array', 'sanitize_callback' => [$this, 'sanitize']]
        );

        // Dodaj sekcję dla ustawień głównych popupa.
        add_settings_section(
            'popup_main_section',                   // Unikalne ID sekcji
            __('Ustawienia Główne Popupa', 'pro_reader'), // Tytuł sekcji widoczny na stronie
            [$this, 'render_section_info'],         // Funkcja renderująca opis pod tytułem sekcji
            'reader-engagement-pro-popup'           // Slug strony/zakładki, na której ma się pojawić
        );

        // === PRZYKŁADOWE, WYKOMENTOWANE POLE ===
        // Gdy będziemy dodawać nowe opcje, odkomentujemy i dostosujemy ten blok.
        /*
        add_settings_field(
            'popup_enable',                               // ID pola
            __('Włącz Popup', 'pro_reader'),              // Etykieta pola
            [$this, 'popup_enable_callback'],             // Funkcja renderująca HTML pola
            'reader-engagement-pro-popup',                // Slug strony/zakładki
            'popup_main_section'                          // Sekcja, do której należy pole
        );
        */
    }

    /**
     * Wyświetla informacyjny tekst pod tytułem sekcji.
     */
    public function render_section_info() {
        echo '<p>';
        esc_html_e('W tej sekcji skonfigurujesz wygląd i zachowanie okna popup z rekomendowanymi artykułami.', 'pro_reader');
        echo '</p>';
    }
    
    /**
     * Sanitizes i waliduje dane wejściowe dla tej zakładki.
     * @param array $input Dane z formularza.
     * @return array Przetworzone dane.
     */
    public function sanitize($input): array {
        // Pobieramy istniejące opcje, aby nie nadpisać ustawień z innych zakładek.
        $current_options = get_option(self::OPTION_NAME, []);
        $sanitized = $current_options;

        // Tutaj w przyszłości dodamy logikę sanitazyacji dla pól popupa.
        // Przykład:
        // if (isset($input['popup_enable'])) {
        //     $sanitized['popup_enable'] = $input['popup_enable'] === '1' ? '1' : '0';
        // }

        return $sanitized;
    }
    
    /**
     * Funkcja renderująca przykładowe pole (callback).
     * Zostanie zaimplementowana w przyszłości.
     */
    /*
    public function popup_enable_callback() {
        $options = get_option(self::OPTION_NAME, []);
        $value = $options['popup_enable'] ?? '0';
        printf('<input type="checkbox" id="popup_enable" name="%s[popup_enable]" value="1" %s />', self::OPTION_NAME, checked('1', $value, false));
        echo '<label for="popup_enable">Aktywuj moduł popupu na stronie.</label>';
    }
    */
}