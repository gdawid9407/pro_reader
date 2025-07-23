<?php
/**
 * Szablon podglądu na żywo dla popupa w panelu admina.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobieramy najnowsze opcje, aby podgląd był zawsze aktualny.
$options = get_option('reader_engagement_pro_options', []);

// Ustawienie wartości domyślnych dla podglądu.
$posts_count    = (int) ($options['popup_recommendations_count'] ?? 3);
$popup_content  = $options['popup_content_main'] ?? '<h3>Spodobał Ci się ten artykuł?</h3><p>Czytaj dalej i odkryj więcej ciekawych treści, które dla Ciebie przygotowaliśmy!</p>';
$layout_setting = $options['popup_recommendations_layout'] ?? 'list';
$layout_class   = 'layout-' . sanitize_html_class($layout_setting);
$item_layout    = $options['popup_rec_item_layout'] ?? 'vertical';
$item_class     = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
$link_text      = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

$bg_color       = $options['popup_rec_button_bg_color'] ?? '#0073aa';
$text_color     = $options['popup_rec_button_text_color'] ?? '#ffffff';
$border_radius  = $options['popup_rec_button_border_radius'] ?? 4;
$button_style   = sprintf(
    'background-color: %s; color: %s; border-radius: %dpx;',
    esc_attr($bg_color),
    esc_attr($text_color),
    esc_attr($border_radius)
);
?>

<div id="rep-intelligent-popup__overlay-preview" class="is-visible" style="position: absolute; opacity: 0.1; top:0; left:0; right:0; bottom:0; z-index: -1;"></div>
<div id="rep-intelligent-popup__container" class="is-visible" style="position: relative; top: auto; left: auto; transform: none; max-width: 800px; z-index: 1;">
    <header id="rep-intelligent-popup__header">
        <h2 id="rep-intelligent-popup__title-static" class="screen-reader-text">Rekomendowane treści</h2>
        <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
    </header>
    
    <div id="rep-intelligent-popup__custom-content">
        <?php echo wp_kses_post($popup_content); ?>
    </div>

    <ul id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
        <?php
        // Generujemy pętlę z przykładowymi elementami na podstawie ustawionej liczby.
        for ($i = 0; $i < $posts_count; $i++) :
        ?>
        <li class="<?php echo esc_attr($item_class); ?>">
            <a href="#" onclick="return false;" class="rep-rec-thumb-link" style="aspect-ratio: 16 / 9;">
                <img src="<?php echo esc_url(REP_PLUGIN_URL . 'assets/images/placeholder.png'); ?>" alt="placeholder" class="rep-rec-thumb thumb-fit-cover">
            </a>
            <div class="rep-rec-content">
                <p class="rep-rec-meta"><span class="rep-rec-date">1 Styczeń, 2025</span> <span class="rep-rec-meta-separator">•</span> <span class="rep-rec-category">Kategoria</span></p>
                <h3 class="rep-rec-title"><a href="#" onclick="return false;">Przykładowy Tytuł Rekomendacji</a></h3>
                <p class="rep-rec-excerpt">To jest przykład zajawki artykułu, aby pokazać jak będzie wyglądać w popupie i jak tekst może się zawijać.</p>
                <a href="#" onclick="return false;" class="rep-rec-button" style="<?php echo esc_attr($button_style); ?>"><?php echo wp_kses_post($link_text); ?></a>
            </div>
        </li>
        <?php endfor; ?>
    </ul>
</div>