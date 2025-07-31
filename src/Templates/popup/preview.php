<?php
/**
 * Szablon podglądu na żywo dla popupa w panelu admina.
 * Wersja: 2.0 - W pełni izolowany w iframe, sterowany przez postMessage.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobieramy opcje, aby ustawić stan początkowy podglądu.
// Zakładamy, że podgląd domyślnie pokazuje wersję desktopową.
$options = get_option('reader_engagement_pro_options', []);
$settings = $options['desktop'] ?? [];

// Ustawienie wartości domyślnych dla KAŻDEJ opcji używanej w podglądzie.
$posts_count    = (int) ($options['popup_recommendations_count'] ?? 3);
$popup_content  = $options['popup_content_main'] ?? '<h3>Spodobał Ci się ten artykuł?</h3><p>Czytaj dalej i odkryj więcej ciekawych treści, które dla Ciebie przygotowaliśmy!</p>';
$layout_setting = $settings['popup_recommendations_layout'] ?? 'grid';
$layout_class   = 'layout-' . sanitize_html_class($layout_setting);
$item_layout    = $settings['popup_rec_item_layout'] ?? 'vertical';
$item_class     = 'rep-rec-item item-layout-' . sanitize_html_class($item_layout);
$link_text      = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

$padding_y = $settings['popup_padding_y'] ?? 24;
$padding_x = $settings['popup_padding_x'] ?? 40;

// Przygotowanie zmiennych CSS dla podglądu
$spacing_styles = [
    '--rep-popup-max-width'         => ($settings['popup_max_width'] ?? 670) . 'px',
    '--rep-popup-max-height'        => ($settings['popup_max_height'] ?? 81) . 'vh',
    '--rep-popup-padding'           => "{$padding_y}px {$padding_x}px",
    '--rep-content-margin-bottom'   => ($settings['popup_margin_content_bottom'] ?? 20) . 'px',
    '--rep-list-item-gap'           => ($settings['popup_gap_list_items'] ?? 50) . 'px',
    '--rep-grid-item-gap'           => ($settings['popup_gap_grid_items'] ?? 45) . 'px',
    '--rep-grid-item-width'         => ($settings['popup_grid_item_width'] ?? 234) . 'px',
    '--rep-rec-thumb-margin-right'  => ($settings['popup_rec_thumb_margin_right'] ?? 25) . 'px',
    '--rep-rec-thumb-margin-bottom' => ($settings['popup_rec_thumb_margin_right'] ?? 25) . 'px',
    '--rep-rec-thumb-width-horizontal' => ($settings['popup_rec_thumb_width_horizontal'] ?? 200) . 'px',
    '--rep-rec-thumb-width-list-vertical' => ($settings['popup_rec_thumb_width_list_vertical'] ?? 100) . '%',
    '--rep-btn-bg'                  => $options['popup_rec_button_bg_color'] ?? '#0073aa',
    '--rep-btn-text'                => $options['popup_rec_button_text_color'] ?? '#ffffff',
    '--rep-btn-border-radius'       => ($options['popup_rec_button_border_radius'] ?? 4) . 'px',
    '--rep-rec-meta-margin-bottom'    => ($settings['popup_rec_margin_meta_bottom'] ?? 8) . 'px',
    '--rep-rec-title-margin-bottom'   => ($settings['popup_rec_margin_title_bottom'] ?? 12) . 'px',
    '--rep-rec-excerpt-margin-bottom' => ($settings['popup_rec_margin_excerpt_bottom'] ?? 12) . 'px',
];

$container_styles = '';
foreach ($spacing_styles as $key => $value) {
    $container_styles .= esc_attr($key) . ':' . esc_attr($value) . ';';
}

$preview_images = ['placeholder-1.jpg', 'placeholder-2.jpg', 'placeholder-3.jpg'];
$images_total = count($preview_images);
?>
<!DOCTYPE html>
<html style="background: #f0f0f1;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podgląd Popupa</title>
    <?php
        // Kolejkujemy tylko niezbędne style, izolując od panelu admina
        wp_print_styles(['rep-popup-style', 'rep-popup-mobile-style']);
    ?>
    <style>
        /* Dodatkowe style tylko dla podglądu */
        body {
            padding: 20px;
            height: auto;
            min-height: 100vh;
        }
        #rep-intelligent-popup__container {
            position: relative;
            top: auto;
            left: auto;
            transform: none;
            margin: 0 auto;
            z-index: 1;
            /* Symulacja trybu mobilnego dla testów */
        }
        .preview-mobile #rep-intelligent-popup__container {
            max-width: 360px !important;
            width: 100%;
        }
    </style>
</head>
<body>

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
        for ($i = 0; $i < $posts_count; $i++) :
            $current_image_file = $preview_images[$i % $images_total];
            $image_url = REP_PLUGIN_URL . 'assets/images/' . $current_image_file;
            $thumb_fit_class = 'thumb-fit-' . ($settings['popup_rec_thumb_fit'] ?? 'cover');
            $aspect_ratio = $settings['popup_rec_thumb_aspect_ratio'] ?? '16:9';
            $thumb_link_style = ($aspect_ratio !== 'auto') ? 'aspect-ratio: ' . str_replace(':', ' / ', $aspect_ratio) . ';' : '';
        ?>
        <li class="<?php echo esc_attr($item_class); ?>" data-key="<?php echo $i; ?>">
            <a href="#" onclick="return false;" class="rep-rec-thumb-link" style="<?php echo esc_attr($thumb_link_style); ?>">
                <img src="<?php echo esc_url($image_url); ?>" alt="placeholder-<?php echo ($i % $images_total) + 1; ?>" class="rep-rec-thumb <?php echo esc_attr($thumb_fit_class); ?>">
            </a>
            <div class="rep-rec-content">
                <p class="rep-rec-meta"><span class="rep-rec-date">1 Styczeń, 2025</span> <span class="rep-rec-meta-separator">•</span> <span class="rep-rec-category">Kategoria</span></p>
                <h3 class="rep-rec-title"><a href="#" onclick="return false;">Przykładowy Tytuł Rekomendacji</a></h3>
                <p class="rep-rec-excerpt">To jest przykład zajawki artykułu, aby pokazać jak będzie wyglądać w popupie i jak tekst może się zawijać.</p>
                <a href="#" onclick="return false;" class="rep-rec-button"><?php echo wp_kses_post($link_text); ?></a>
            </div>
        </li>
        <?php endfor; ?>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popupContainer = document.getElementById('rep-intelligent-popup__container');
    const popupList = document.getElementById('rep-intelligent-popup__list');
    const excerpts = popupList.querySelectorAll('.rep-rec-excerpt');

    // Zapisz oryginalne zajawki
    excerpts.forEach(el => {
        el.dataset.originalText = el.textContent;
    });

    const styleMap = {
        'popup_max_width_desktop': { var: '--rep-popup-max-width', unit: 'px' },
        'popup_max_height_desktop': { var: '--rep-popup-max-height', unit: 'vh' },
        'popup_padding_y_desktop': { var: '--rep-popup-padding-y', unit: 'px' },
        'popup_padding_x_desktop': { var: '--rep-popup-padding-x', unit: 'px' },
        'popup_margin_content_bottom_desktop': { var: '--rep-content-margin-bottom', unit: 'px' },
        'popup_gap_list_items_desktop': { var: '--rep-list-item-gap', unit: 'px' },
        'popup_gap_grid_items_desktop': { var: '--rep-grid-item-gap', unit: 'px' },
        'popup_grid_item_width_desktop': { var: '--rep-grid-item-width', unit: 'px' },
        'popup_rec_thumb_margin_right_desktop': { var: '--rep-rec-thumb-margin-right', unit: 'px' },
        'popup_rec_thumb_width_horizontal_desktop': { var: '--rep-rec-thumb-width-horizontal', unit: 'px' },
        'popup_rec_thumb_width_list_vertical_desktop': { var: '--rep-rec-thumb-width-list-vertical', unit: '%' },
        'popup_rec_margin_meta_bottom_desktop': { var: '--rep-rec-meta-margin-bottom', unit: 'px' },
        'popup_rec_margin_title_bottom_desktop': { var: '--rep-rec-title-margin-bottom', unit: 'px' },
        'popup_rec_margin_excerpt_bottom_desktop': { var: '--rep-rec-excerpt-margin-bottom', unit: 'px' },
        
        'popup_max_width_mobile': { var: '--rep-popup-max-width', unit: 'px' },
        'popup_max_height_mobile': { var: '--rep-popup-max-height', unit: 'vh' },
        'popup_padding_y_mobile': { var: '--rep-popup-padding-y', unit: 'px' },
        'popup_padding_x_mobile': { var: '--rep-popup-padding-x', unit: 'px' },
        'popup_margin_content_bottom_mobile': { var: '--rep-content-margin-bottom', unit: 'px' },
        'popup_gap_list_items_mobile': { var: '--rep-list-item-gap', unit: 'px' },
        'popup_gap_grid_items_mobile': { var: '--rep-grid-item-gap', unit: 'px' },
        'popup_grid_item_width_mobile': { var: '--rep-grid-item-width', unit: 'px' },
        'popup_rec_thumb_margin_right_mobile': { var: '--rep-rec-thumb-margin-right', unit: 'px' },
        'popup_rec_thumb_width_horizontal_mobile': { var: '--rep-rec-thumb-width-horizontal', unit: 'px' },
        'popup_rec_thumb_width_list_vertical_mobile': { var: '--rep-rec-thumb-width-list-vertical', unit: '%' },
        'popup_rec_margin_meta_bottom_mobile': { var: '--rep-rec-meta-margin-bottom', unit: 'px' },
        'popup_rec_margin_title_bottom_mobile': { var: '--rep-rec-title-margin-bottom', unit: 'px' },
        'popup_rec_margin_excerpt_bottom_mobile': { var: '--rep-rec-excerpt-margin-bottom', unit: 'px' },
    };

    let currentSettings = {
        popup_rec_excerpt_limit_type: 'words',
        popup_rec_excerpt_length: 15,
        popup_rec_excerpt_lines: 3
    };

    function updatePadding() {
        const y = currentSettings.popup_padding_y || '24';
        const x = currentSettings.popup_padding_x || '40';
        popupContainer.style.setProperty('--rep-popup-padding', `${y}px ${x}px`);
    }

    function updateExcerptPreview() {
        const limitType = currentSettings.popup_rec_excerpt_limit_type;
        const wordLimit = currentSettings.popup_rec_excerpt_length;
        const lineLimit = currentSettings.popup_rec_excerpt_lines;

        excerpts.forEach(el => {
            const originalText = el.dataset.originalText;
            if (limitType === 'words') {
                el.style.cssText = ''; // Usuń style dla line-clamp
                const words = originalText.split(/\s+/);
                const trimmedText = words.slice(0, wordLimit).join(' ') + (words.length > wordLimit ? '...' : '');
                el.textContent = trimmedText;
            } else { // lines
                el.textContent = originalText;
                el.style.webkitLineClamp = lineLimit;
                el.style.display = '-webkit-box';
                el.style.webkitBoxOrient = 'vertical';
                el.style.overflow = 'hidden';
                el.style.textOverflow = 'ellipsis';
            }
        });
    }

    window.addEventListener('message', function(event) {
        // Dla bezpieczeństwa można dodać: if (event.origin !== 'oczekiwana_domena_admina') return;
        const { type, payload } = event.data;

        if (type === 'setting_update') {
            const { id, value } = payload;
            const key = id.replace(/_desktop$|_mobile$/, '');
            currentSettings[key] = value;

            // Obsługa mapowania stylów
            const styleInfo = styleMap[id];
            if (styleInfo) {
                popupContainer.style.setProperty(styleInfo.var, value + styleInfo.unit);
            }
            
            // Obsługa specjalnych przypadków
            if (key === 'popup_padding_y' || key === 'popup_padding_x') {
                updatePadding();
            } else if (key.startsWith('popup_rec_excerpt')) {
                updateExcerptPreview();
            } else if (id.includes('popup_recommendations_layout')) {
                popupList.className = 'layout-' + value;
            } else if (id.includes('popup_rec_item_layout')) {
                popupList.querySelectorAll('.rep-rec-item').forEach(item => {
                    item.className = 'rep-rec-item item-layout-' + value;
                });
            } else if (id.includes('popup_rec_thumb_aspect_ratio')) {
                const ratio = (value === 'auto') ? 'auto' : value.replace(':', ' / ');
                popupList.querySelectorAll('.rep-rec-thumb-link').forEach(link => link.style.aspectRatio = ratio);
            } else if (id.includes('popup_rec_thumb_fit')) {
                popupList.querySelectorAll('.rep-rec-thumb').forEach(img => {
                    img.classList.remove('thumb-fit-cover', 'thumb-fit-contain');
                    img.classList.add('thumb-fit-' + value);
                });
            }

        } else if (type === 'batch_update') {
            // Logika do obsługi pełnej synchronizacji (przydatne przy pierwszym ładowaniu)
            // Ta część może być bardziej rozbudowana w przyszłości
            console.log('Batch update received:', payload);
            document.body.className = payload.popup_max_width_mobile ? 'preview-mobile' : '';
        }
    });
});
</script>

</body>
</html>