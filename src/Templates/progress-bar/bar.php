<?php
/**
 * Szablon paska postępu.
 *
 * Oczekuje następujących zmiennych:
 * @var string $posClass        Klasa CSS dla pozycji (position-top/position-bottom).
 * @var string $style_attr      Atrybuty stylu dla kontenera.
 * @var string $show_percentage Czy pokazywać procenty ('1' lub '0').
 * @var int    $bar_height      Wysokość paska w pikselach.
 * @var string $label_start     Etykieta początkowa.
 * @var string $label_end       Etykieta końcowa.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="progress-bar-container-wrapper" class="proreader-container <?php echo esc_attr($posClass); ?>" style="<?php echo esc_attr($style_attr); ?>">
    <div id="progress-bar-gradient" class="proreader-gradient">
        <?php if ($show_percentage === '1') : ?>
            <span id="rep-progress-percentage" style="line-height: <?php echo esc_attr($bar_height); ?>px;">0%</span>
        <?php endif; ?>

        <div class="proreader-labels">
            <span class="label-start"><?php echo esc_html($label_start); ?></span>
            <span class="label-end"><?php echo esc_html($label_end); ?></span>
        </div>
        
        <div id="progress-bar" class="proreader-bar"></div>
    </div>
</div>