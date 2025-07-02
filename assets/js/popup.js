jQuery(function($) {
    'use strict';

    // --- 1. INICJALIZACJA I POBRANIE ELEMENTÓW ---

    const settings = window.REP_Popup_Settings || {};

    // KRYTYCZNA ZMIANA: Sprawdź, czy popup jest w ogóle włączony.
    // Jeśli nie, zakończ działanie skryptu natychmiast.
    // To respektuje główny włącznik z panelu admina.
    if (!settings.popupEnable || settings.popupEnable !== '1') {
        return;
    }

    const $popupContainer = $('#rep-intelligent-popup__container');
    const $overlay = $('#rep-intelligent-popup__overlay');
    const $closeButton = $('#rep-intelligent-popup__close');
    const $recommendationList = $('#rep-intelligent-popup__list');

    if (!$popupContainer.length) {
        return;
    }

    let popupHasBeenShown = false;
    let ajaxRequestSent = false;
    let lastScrollTop = 0;
    let hasScrolledDown = false;
    const scrollDownThreshold = 500;


    // --- 2. GŁÓWNE FUNKCJE KONTROLUJĄCE POPUP ---

    function showPopup() {
        if (popupHasBeenShown) {
            return;
        }
        popupHasBeenShown = true;
        
        fetchRecommendations();

        $overlay.add($popupContainer).addClass('is-visible');
        
        // Zablokuj scrollowanie tła
        $('body').addClass('rep-popup-is-open');
    }
    
    /**
     * Ukrywa popup i przywraca scrollowanie strony.
     */
    function hidePopup() {
        $overlay.add($popupContainer).removeClass('is-visible');
        
        // ZMIANA: Zamiast .css(), usuwamy klasę z <body>
        $('body').removeClass('rep-popup-is-open');
    }
    /**
     * Wykonuje żądanie AJAX w celu pobrania rekomendacji artykułów.
     */
    function fetchRecommendations() {
        if (ajaxRequestSent || !settings.ajaxUrl) { // Dodatkowe zabezpieczenie
            return;
        }
        ajaxRequestSent = true;
        
        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_recommendations',
                nonce: settings.nonce,
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $recommendationList.html(response.data.html);
                } else {
                    $recommendationList.html('<li class="rep-rec-item-error">Nie udało się wczytać rekomendacji.</li>');
                }
            },
            error: function() {
                $recommendationList.html('<li class="rep-rec-item-error">Wystąpił błąd serwera. Spróbuj ponownie później.</li>');
            }
        });
    }


    // --- 3. WYZWALACZE (TRIGGERS) ---
    // Funkcja do inicjalizacji wyzwalaczy.
    function initializeTriggers() {
        // Wyzwalacz czasowy
        const timeInMs = parseInt(settings.triggerByTime, 10) * 1000;
        if (timeInMs > 0) {
            setTimeout(showPopup, timeInMs);
        }
        // ZMIANA: Logika scrollowania jest teraz w jednej funkcji dla optymalizacji.
        // Listener jest dodawany tylko jeśli którykolwiek z wyzwalaczy scrollowania jest potrzebny.
        const scrollPercent = parseFloat(settings.triggerByScrollPercent);
        const scrollUpEnabled = settings.triggerByScrollUp === '1';

        if (scrollPercent > 0 || scrollUpEnabled) {
             $(window).on('scroll.repPopup', handleScroll);
        }
    }
    // ZMIANA: Wydzielona funkcja obsługi scrollowania
    function handleScroll() {
        if (popupHasBeenShown) {
            $(window).off('scroll.repPopup'); // Użycie namespace pozwala usunąć tylko ten konkretny event.
            return;
        }
        
        const scrollTop = $(this).scrollTop();
        
        // Wyzwalacz: Procent przescrollowania
        const docHeight = $(document).height();
        const winHeight = $(window).height();
        const scrollableHeight = docHeight - winHeight;

        if (scrollableHeight > 0) {
            const currentScrollPercent = (scrollTop / scrollableHeight) * 100;
            if (currentScrollPercent >= parseFloat(settings.triggerByScrollPercent)) {
                showPopup();
                return; // Zatrzymaj dalsze sprawdzanie po aktywacji
            }
        }
        // Wyzwalacz: Scroll w górę po scrollu w dół
        // KRYTYCZNA ZMIANA: Sprawdzamy, czy ten wyzwalacz jest włączony w ustawieniach.
        if (settings.triggerByScrollUp === '1') {
            if (scrollTop > lastScrollTop) {
                // Użytkownik scrolluje w dół
                if (scrollTop > scrollDownThreshold) {
                     hasScrolledDown = true;
                }
            } else if (scrollTop < lastScrollTop) {
                // Użytkownik scrolluje w górę
                if (hasScrolledDown) {
                    showPopup();
                    return; // Zatrzymaj dalsze sprawdzanie po aktywacji
                }
            }
        }
            lastScrollTop = scrollTop;
    }
    // --- 4. OBSŁUGA ZDARZEŃ ---
    // Inicjalizacja wyzwalaczy
    initializeTriggers();

    // Obsługa zamykania popupa
    $closeButton.on('click', hidePopup);
    $overlay.on('click', hidePopup);
    
    $(document).on('keyup', function(e) {
        if (e.key === "Escape" && $popupContainer.is(':visible')) {
            hidePopup();
        }
    });
});