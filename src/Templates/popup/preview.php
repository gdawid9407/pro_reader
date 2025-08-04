<?php
/**
 * Szablon podglądu na żywo dla popupa.
 * Ten plik jest ładowany wewnątrz iframe'a w panelu admina.
 * Wersja ostateczna, ładująca style bezpośrednio.
 *
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobieramy bazowe opcje, aby mieć dostęp do przykładowego tekstu przycisku.
$options = get_option('reader_engagement_pro_options', []);
$link_text = $options['popup_recommendations_link_text'] ?? 'Zobacz więcej →';

$sample_image_urls = [
    esc_url(REP_PLUGIN_URL . 'assets/images/sample-1.jpg'),
    esc_url(REP_PLUGIN_URL . 'assets/images/sample-2.jpg'),
    esc_url(REP_PLUGIN_URL . 'assets/images/sample-3.jpg'),
];
?>
<!DOCTYPE html>
<html style="overflow: hidden;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('Podgląd Popupa', 'pro_reader'); ?></title>

    <?php
    // --- POCZĄTEK ZMIANY ---
    // Zamiast wp_print_styles(), ładujemy pliki CSS bezpośrednio za pomocą tagu <link>.
    // To jest bardziej niezawodne w środowisku admin-ajax.php.
    // Używamy wersji pliku, aby zapobiec problemom z cache przeglądarki.
    $plugin_version = '1.4.0'; // Wersja z pliku Popup.php
    ?>
    <link rel='stylesheet' id='rep-popup-style-css' href='<?php echo esc_url(REP_PLUGIN_URL . 'assets/css/popup.css?ver=' . $plugin_version); ?>' media='all' />
    <link rel='stylesheet' id='rep-popup-mobile-style-css' href='<?php echo esc_url(REP_PLUGIN_URL . 'assets/css/popup-mobile.css?ver=' . $plugin_version); ?>' media='all' />
    <?php // --- KONIEC ZMIANY --- ?>

    <style>
        /* Te style są specyficzne TYLKO dla środowiska podglądu i muszą tu zostać. */
        body {
            background-image:
                linear-gradient(45deg, #f0f0f1 25%, transparent 25%),
                linear-gradient(-45deg, #f0f0f1 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #f0f0f1 75%),
                linear-gradient(-45deg, transparent 75%, #f0f0f1 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        }
        
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #rep-intelligent-popup__overlay { display: none; }
        #rep-intelligent-popup__container {
            position: relative; 
            top: auto;
            left: auto;
            transform: none; 
            opacity: 1;
            visibility: visible;
        }

        #rep-intelligent-popup__list .rep-rec-item-loading { display: none; }

        /* --- ZMIANA: USUNĘLIŚMY STĄD ZDUPLIKOWANE STYLE DLA OBRAZKÓW --- */
        /* Teraz będą one wczytywane z oryginalnego pliku popup.css */
    </style>
</head>
<body class="rep-popup-is-open">

    <?php
    // --- POCZĄTEK ZMIANY: Pobieranie treści z opcji ---
    $popup_content = $options['popup_content_main'] ?? '<h3>Przykładowy Nagłówek</h3><p>To jest przykładowa treść, którą można dodać nad listą rekomendacji.</p>';
    $layout_class = 'layout-grid';
    // --- KONIEC ZMIANY ---
    ?>

    <div id="rep-intelligent-popup__container" 
        style="" 
        role="dialog" 
        aria-modal="true" 
        aria-labelledby="rep-intelligent-popup__title-static">
        <header id="rep-intelligent-popup__header">
            <button id="rep-intelligent-popup__close" aria-label="Zamknij">×</button>
        </header>
        
        <div id="rep-intelligent-popup__custom-content">
            <?php echo wp_kses_post($popup_content); ?>
        </div>

        <div id="rep-intelligent-popup__list" class="<?php echo esc_attr($layout_class); ?>">
            <?php for ($i = 0; $i < 3; $i++) : ?>
                <li class="rep-rec-item item-layout-vertical">
                    <div class="rep-rec-content">
                        <a href="#" class="rep-rec-thumb-link" style="aspect-ratio: 16 / 9;" onclick="return false;">
                            <img src="<?php echo esc_url($sample_image_urls[$i % 3]); ?>" class="rep-rec-thumb thumb-fit-cover">
                        </a>
                        <p class="rep-rec-meta">
                            <span class="rep-rec-date">1 Styczeń, 2025</span>
                            <span class="rep-rec-meta-separator">•</span>
                            <span class="rep-rec-category">Kategoria</span>
                        </p>
                        <h3 class="rep-rec-title"><a href="#" onclick="return false;">Przykładowy Tytuł Artykułu #<?php echo $i + 1; ?></a></h3>
                        <p class="rep-rec-excerpt">To jest krótka zajawka przykładowego artykułu, aby zademonstrować wygląd rekomendacji w popupie.</p>
                        <a href="#" class="rep-rec-button" onclick="return false;"><?php echo esc_html($link_text); ?></a>
                    </div>
                </li>
            <?php endfor; ?>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const recommendationList = document.getElementById('rep-intelligent-popup__list');
            if (recommendationList) {
                let sampleHtml = '';
                const linkText = '<?php echo wp_kses_post($link_text); ?>';
                const sampleImages = <?php echo json_encode($sample_image_urls); ?>;

                for (let i = 0; i < 3; i++) {
                    const imageUrl = sampleImages[i % sampleImages.length];
                    sampleHtml += `
                    <li class="rep-rec-item item-layout-vertical">
                        <div class="rep-rec-content">
                            <a href="#" class="rep-rec-thumb-link" style="aspect-ratio: 16 / 9;" onclick="return false;">
                                <img src="${imageUrl}" class="rep-rec-thumb thumb-fit-cover">
                            </a>
                            <p class="rep-rec-meta">
                                <span class="rep-rec-date">1 Styczeń, 2025</span>
                                <span class="rep-rec-meta-separator">•</span>
                                <span class="rep-rec-category">Kategoria</span>
                            </p>
                            <h3 class="rep-rec-title"><a href="#" onclick="return false;">Przykładowy Tytuł Artykułu #${i + 1}</a></h3>
                            <p class="rep-rec-excerpt">To jest krótka zajawka przykładowego artykułu, aby zademonstrować wygląd rekomendacji w popupie.</p>
                            <a href="#" class="rep-rec-button" onclick="return false;">${linkText}</a>
                        </div>
                    </li>`;
                }
                recommendationList.innerHTML = sampleHtml;
            }
        });
    </script>

</body>
</html>