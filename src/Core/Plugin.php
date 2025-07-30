<?php

namespace ReaderEngagementPro\Core;

if (!defined('ABSPATH')) {
    exit;
}

use ReaderEngagementPro\Database\Installer;
use ReaderEngagementPro\Database\LinkIndexer;
use ReaderEngagementPro\Frontend\ProgressBar;
use ReaderEngagementPro\Frontend\Popup;
use ReaderEngagementPro\Admin\Settings_Page;
use ReaderEngagementPro\Core\AjaxHandler;

/**
 * Główna klasa wtyczki.
 */
final class Plugin
{
    public function __construct()
    {
        register_activation_hook(REP_PLUGIN_FILE, [Installer::class, 'activate']);
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Inicjalizuje komponenty wtyczki.
     */
    public function init(): void
    {
        new ProgressBar();
        new Popup();
        new AjaxHandler();

        if (is_admin()) {
            new Settings_Page();
        }

        $this->register_save_post_hooks();
    }

    /**
     * Rejestruje hooki 'save_post' dla wybranych typów treści.
     */
    private function register_save_post_hooks(): void
    {
        $options = get_option('reader_engagement_pro_options', []);
        $post_types_to_index = $options['popup_display_on'] ?? ['post'];

        if (!empty($post_types_to_index) && is_array($post_types_to_index)) {
            foreach ($post_types_to_index as $post_type) {
                add_action('save_post_' . $post_type, [$this, 'handle_post_save'], 10, 2);
            }
        }
    }

    /**
     * Obsługuje zapisywanie posta i uruchamia indeksowanie.
     *
     * @param int      $post_id ID zapisanego posta.
     * @param \WP_Post $post    Obiekt zapisanego posta.
     */
    public function handle_post_save(int $post_id, \WP_Post $post): void
    {

        if (
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            wp_is_post_revision($post_id) ||
            'publish' !== $post->post_status
        ) {
            return;
        }

        $options = get_option('reader_engagement_pro_options', []);
        $post_types_to_index = $options['popup_display_on'] ?? ['post'];

        if (!in_array($post->post_type, $post_types_to_index, true)) {
            return;
        }

        $indexer = new LinkIndexer();
        $indexer->index_post($post_id);
    }
}