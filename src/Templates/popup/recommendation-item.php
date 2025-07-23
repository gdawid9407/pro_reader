<?php
/**
 * Szablon pojedynczego elementu rekomendacji.
 *
 * Oczekuje następujących zmiennych:
 * @var string $item_class      Główna klasa CSS dla elementu <li>.
 * @var string $item_layout     Typ układu ('vertical' lub 'horizontal').
 * @var array  $components_html Tablica asocjacyjna z kodem HTML poszczególnych komponentów.
 * @var array  $components_order Tablica z kolejnością komponentów.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<li class="<?php echo esc_attr($item_class); ?>">
    <?php
    // Dla układu horyzontalnego, miniaturka jest na zewnątrz głównego kontenera treści.
    if ($item_layout === 'horizontal' && !empty($components_html['thumbnail'])) {
        echo $components_html['thumbnail']; // Oczekuje się, że HTML jest już bezpieczny
        echo '<div class="rep-rec-content">';
        // Renderuj pozostałe komponenty wewnątrz diva.
        foreach ($components_order as $key) {
            if ($key !== 'thumbnail' && !empty($components_html[$key])) {
                echo $components_html[$key];
            }
        }
        echo '</div>';
    } else {
        // Dla układu wertykalnego, renderuj wszystkie komponenty po kolei.
        foreach ($components_order as $key) {
            if (!empty($components_html[$key])) {
                echo $components_html[$key];
            }
        }
    }
    ?>
</li>