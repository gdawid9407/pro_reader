<?php

namespace ReaderEngagementPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zarządza zakładką "Wygląd - Mobilny" dla modułu Popup.
 * Na razie jest to placeholder do przyszłej rozbudowy.
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
     * Rejestruje sekcje i pola ustawień.
     */
    public function page_init(): void
    {
        $page = 'reader-engagement-pro-popup-mobile';

        add_settings_section(
            'popup_mobile_placeholder_section', 
            __('Ustawienia mobilne', 'pro_reader'), 
            function() {
                echo '<p>' . esc_html__('Ustawienia wyglądu dla urządzeń mobilnych zostaną dodane w przyszłości.', 'pro_reader') . '</p>';
            }, 
            $page
        );
    }

    /**
     * Sanitacja danych dla tej zakładki (na razie pusta).
     */
    public function sanitize(array $input, array $current_options): array
    {
        // Na razie nie ma żadnych opcji do sanitacji.
        return $current_options;
    }
}
