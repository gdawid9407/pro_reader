<?php
/**
 * Szablon podglądu na żywo dla popupa w panelu admina.
 * Wersja: 1.2 - Poprawiono dynamiczne wczytywanie stylu aspect-ratio.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobieramy najnowsze opcje, aby podgląd był zawsze aktualny.
$options = get_option('reader_engagement_pro_options', []);

// Ustawienie wartości domyślnych dla KAŻDEJ opcji używanej w podglądzie.
$posts_count    = (int) ($options['popup_recommendations_count'] ?? 3);
$popup_content  = $options['popup_content_main'] ?? '<h3>Spodobał Ci się ten artykuł?</h3><p>Czytaj dalej i odkryj więcej ciekawych treści, które dla Ciebie przygotowaliśmy!</p>';
$layout_setting = $options['popup_recommendations_layout'] ?? 'list';
$layout_class   = 'layout-' . sanitize_html_class($layout_setting);
$item_layout    = $options['popup_rec_item_layout'] ?? 'vertical';
$item_class     = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
$link_text      = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

// Domyślne wartości dla stylów przycisku
$bg_color       = $options['popup_rec_button_bg_color'] ?? '#0073aa';
$text_color     = $options['popup_rec_button_text_color'] ?? '#ffffff';
$border_radius  = $options['popup_rec_button_border_radius'] ?? 4;
$button_style   = sprintf(
    'background-color: %s; color: %s; border-radius: %dpx;',
    esc_attr($bg_color),
    esc_attr($text_color),
    (int) $border_radius
);

// --- POCZĄTEK ZMIAN ---
// Dynamiczne generowanie stylu dla linka miniaturki, uwzględniając proporcje.
$aspect_ratio_setting = $options['popup_rec_thumb_aspect_ratio'] ?? '16:9';
$thumb_link_style = '';
if ($aspect_ratio_setting !== 'auto') {
    // Formatujemy '16:9' na '16 / 9' dla CSS
    $thumb_link_style = 'aspect-ratio: ' . str_replace(':', ' / ', $aspect_ratio_setting) . ';';
}
// --- KONIEC ZMIAN ---

// Przygotowanie zmiennych CSS dla podglądu
$spacing_styles = [
    // Desktop
    '--rep-popup-max-width'         => ($options['popup_max_width'] ?? 800) . 'px',
    '--rep-popup-max-height'        => ($options['popup_max_height'] ?? 90) . 'vh',
    '--rep-popup-padding'           => ($options['popup_padding_container'] ?? 24) . 'px',
    '--rep-content-margin-bottom'   => ($options['popup_margin_content_bottom'] ?? 20) . 'px',
    '--rep-list-item-gap'           => ($options['popup_gap_list_items'] ?? 16) . 'px',
    '--rep-grid-item-gap'           => ($options['popup_gap_grid_items'] ?? 24) . 'px',
    // Mobile
    '--rep-popup-width-mobile'      => ($options['popup_max_width_mobile'] ?? 90) . 'vw',
    '--rep-popup-padding-mobile'    => ($options['popup_padding_container_mobile'] ?? 16) . 'px',
];

$container_styles = 'position: relative; top: auto; left: auto; transform: none; z-index: 1;';
foreach ($spacing_styles as $key => $value) {
    $container_styles .= esc_attr($key) . ':' . esc_attr($value) . ';';
}

// Tablica z nazwami plików obrazów do podglądu.
$preview_images = ['placeholder-1.jpg', 'placeholder-2.jpg', 'placeholder-3.jpg'];
$images_total = count($preview_images);
?>

<div id="rep-intelligent-popup__overlay-preview" class="is-visible" style="position: absolute; opacity: 0.1; top:0; left:0; right:0; bottom:0; z-index: -1;"></div>
<div id="rep-intelligent-popup__container" class="is-visible" style="<?php echo $container_styles; ?>">
    <header id="rep-intelligent-popup__header">
        <h2 id="rep-intelligent-popup__title-static" class="screen-reader-text">Rekomendowane treści</h2>
        <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
    </header>
    
    <div id="rep-intelligent-popup__custom-content">
        <?php echo wp_kses_post($popup_content); ?>
    </div>

    <ul id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
        <?php
        // Pętla renderująca podgląd rekomendacji
        for ($i = 0; $i < $posts_count; $i++) :
            $current_image_file = $preview_images[$i % $images_total];
            $image_url = REP_PLUGIN_URL . 'assets/images/' . $current_image_file;
        ?>
        <li class="<?php echo esc_attr($item_class); ?>">
            <?php // --- POCZĄTEK ZMIAN --- ?>
            <a href="#" onclick="return false;" class="rep-rec-thumb-link" style="<?php echo esc_attr($thumb_link_style); ?>">
            <?php // --- KONIEC ZMIAN --- ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="placeholder-<?php echo ($i % $images_total) + 1; ?>" class="rep-rec-thumb thumb-fit-cover">
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