<?php
/**
 * Szablon głównego kontenera popupa.
 *
 * Oczekuje następujących zmiennych:
 * @var string $popup_content    Niestandardowa treść HTML do wyświetlenia.
 * @var string $layout_class     Klasa CSS dla układu listy (np. 'layout-list').
 * @var string $container_styles Atrybuty stylu inline zawierające zmienne CSS.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="rep-intelligent-popup__overlay"></div>
<div id="rep-intelligent-popup__container" 
     style="<?php echo $container_styles; ?>" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="rep-intelligent-popup__title-static"
     data-layout-desktop="<?php echo esc_attr(str_replace('layout-', '', $layout_class)); ?>"
     data-item-layout-desktop="<?php echo esc_attr($item_layout); ?>"
     data-layout-mobile="<?php echo esc_attr($layout_mobile); ?>"
     data-item-layout-mobile="<?php echo esc_attr($item_layout_mobile); ?>"
     data-is-mobile="<?php echo $is_mobile_initial ? 'true' : 'false'; ?>">
    <header id="rep-intelligent-popup__header">
        <h2 id="rep-intelligent-popup__title-static" class="screen-reader-text">Rekomendowane treści</h2>
        <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
    </header>
    
    <div id="rep-intelligent-popup__custom-content">
        <?php echo wp_kses_post($popup_content); ?>
    </div>

    <ul id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
        <li class="rep-rec-item-loading">Ładowanie rekomendacji...</li>
    </ul>
</div>