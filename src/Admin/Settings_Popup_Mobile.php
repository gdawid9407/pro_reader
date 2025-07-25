<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa zarządzająca zakładką "Wygląd - Mobilny" dla modułu Popup.
 */
class Settings_Popup_Mobile
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
        $page = 'reader-engagement-pro-popup-mobile';

        add_settings_section(
            'popup_mobile_dimensions_section', 
            __('Ustawienia Wymiarów dla Urządzeń Mobilnych', 'pro_reader'), 
            function() {
                echo '<p>' . esc_html__('Poniższe ustawienia nadpisują wartości z zakładki "Desktop" na ekranach o szerokości do 767px.', 'pro_reader') . '</p>';
            }, 
            $page
        );

        add_settings_field(
            'popup_max_width_mobile', 
            __('Maksymalna szerokość popupa (vw)', 'pro_reader'), 
            [$this, 'max_width_mobile_callback'], 
            $page, 
            'popup_mobile_dimensions_section'
        );
        
        add_settings_field(
            'popup_padding_container_mobile', 
            __('Wewnętrzny padding (px)', 'pro_reader'), 
            [$this, 'padding_container_mobile_callback'], 
            $page, 
            'popup_mobile_dimensions_section'
        );

        add_settings_section('popup_layout_builder_mobile_section', __('Konstruktor Układu Rekomendacji (Mobilny)', 'pro_reader'), null, $page);
        add_settings_field('popup_recommendations_layout_mobile', __('Układ ogólny (Lista/Siatka)', 'pro_reader'), [$this, 'recommendations_layout_mobile_callback'], $page, 'popup_layout_builder_mobile_section');
        add_settings_field('popup_rec_item_layout_mobile', __('Struktura elementu', 'pro_reader'), [$this, 'item_layout_mobile_callback'], $page, 'popup_layout_builder_mobile_section');
    }

    /**
     * Sanitacja danych tylko dla tej zakładki.
     */
    public function sanitize(array $input, array $current_options): array
    {
        $sanitized = $current_options;

        $sanitized['popup_max_width_mobile'] = isset($input['popup_max_width_mobile']) 
            ? max(50, min(100, absint($input['popup_max_width_mobile']))) 
            : 90;

        $sanitized['popup_padding_container_mobile'] = isset($input['popup_padding_container_mobile']) 
            ? max(0, min(50, absint($input['popup_padding_container_mobile']))) 
            : 16;

        $sanitized['popup_recommendations_layout_mobile'] = isset($input['popup_recommendations_layout_mobile']) && in_array($input['popup_recommendations_layout_mobile'], ['list', 'grid']) ? $input['popup_recommendations_layout_mobile'] : 'list';
        $sanitized['popup_rec_item_layout_mobile'] = isset($input['popup_rec_item_layout_mobile']) && in_array($input['popup_rec_item_layout_mobile'], ['vertical', 'horizontal']) ? $input['popup_rec_item_layout_mobile'] : 'vertical';

        return $sanitized;
    }

    // --- CALLBACK FUNCTIONS ---

    public function max_width_mobile_callback(): void
    {
        $value = $this->options['popup_max_width_mobile'] ?? 90;
        printf('<input type="number" id="popup_max_width_mobile" name="%s[popup_max_width_mobile]" value="%d" min="50" max="100" style="width: 100px;" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Domyślnie 90. Jednostka "vw" oznacza % szerokości okna przeglądarki.', 'pro_reader') . '</p>';
    }

    public function padding_container_mobile_callback(): void
    {
        $value = $this->options['popup_padding_container_mobile'] ?? 16;
        printf('<input type="number" id="popup_padding_container_mobile" name="%s[popup_padding_container_mobile]" value="%d" min="0" max="50" />', self::OPTION_NAME, esc_attr($value));
        echo '<p class="description">' . esc_html__('Wewnętrzny margines dla okna popupa na urządzeniach mobilnych.', 'pro_reader') . '</p>';
    }

    public function recommendations_layout_mobile_callback(): void
    {
        $value = $this->options['popup_recommendations_layout_mobile'] ?? 'list';
        echo '<select id="popup_recommendations_layout_mobile" name="' . self::OPTION_NAME . '[popup_recommendations_layout_mobile]">';
        echo '<option value="list"' . selected($value, 'list', false) . '>' . esc_html__('Lista', 'pro_reader') . '</option>';
        echo '<option value="grid"' . selected($value, 'grid', false) . '>' . esc_html__('Siatka', 'pro_reader') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz układ rekomendacji dla urządzeń mobilnych.', 'pro_reader') . '</p>';
    }

    public function item_layout_mobile_callback(): void
    {
        $value = $this->options['popup_rec_item_layout_mobile'] ?? 'vertical';
        echo '<select id="popup_rec_item_layout_mobile" name="' . self::OPTION_NAME . '[popup_rec_item_layout_mobile]">';
        echo '<option value="vertical"' . selected($value, 'vertical', false) . '>' . esc_html__('Wertykalny', 'pro_reader') . '</option>';
        echo '<option value="horizontal"' . selected($value, 'horizontal', false) . '>' . esc_html__('Horyzontalny', 'pro_reader') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Wybierz strukturę pojedynczego elementu rekomendacji dla urządzeń mobilnych.', 'pro_reader') . '</p>';
    }
}