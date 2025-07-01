jQuery(function($) {
    'use strict';

    // --- 1. INICJALIZACJA I POBRANIE ELEMENTÓW ---

    // Obiekt z ustawieniami przekazany z PHP przez wp_localize_script
    const settings = window.REP_Popup_Settings || {};

    // Główne elementy DOM popupa
    const $popupContainer = $('#rep-intelligent-popup__container');
    const $overlay = $('#rep-intelligent-popup__overlay');
    const $closeButton = $('#rep-intelligent-popup__close');
    const $recommendationList = $('#rep-intelligent-popup__list');

    // Jeśli na stronie nie ma kontenera popupa, przerwij działanie skryptu.
    if (!$popupContainer.length) {
        return;
    }
    
    // Flagi stanu, aby zapewnić jednorazowe wykonanie akcji
    let popupHasBeenShown = false;
    let ajaxRequestSent = false;
    
    // Zmienne do śledzenia scrolla
    let lastScrollTop = 0;
    let hasScrolledDown = false;
    const scrollDownThreshold = 500; // Ilość pikseli, którą trzeba przewinąć w dół, aby uznać to za "celowe" działanie


    // --- 2. GŁÓWNE FUNKCJE KONTROLUJĄCE POPUP ---

    /**
     * Pokazuje popup i blokuje scrollowanie strony.
     * Wywołuje funkcję pobierającą rekomendacje.
     */
    function showPopup() {
        if (popupHasBeenShown) {
            return; // Nie pokazuj popupa ponownie
        }
        popupHasBeenShown = true;
        
        // Pobierz rekomendacje, jeśli jeszcze tego nie zrobiono
        fetchRecommendations();

        // Płynne pojawienie się popupa i nakładki
        $overlay.fadeIn(300);
        $popupContainer.css('display', 'block').animate({ opacity: 1 }, 300);

        // Zapobiegaj scrollowaniu tła, gdy popup jest otwarty
        $('body').addClass('rep-popup-is-open');
    }

    /**
     * Ukrywa popup i przywraca scrollowanie strony.
     */
    function hidePopup() {
        $overlay.fadeOut(300);
        $popupContainer.animate({ opacity: 0 }, 300, function() {
            $(this).css('display', 'none');
        });
        
        $('body').removeClass('rep-popup-is-open');
    }

    /**
     * Wykonuje żądanie AJAX w celu pobrania rekomendacji artykułów.
     */
    function fetchRecommendations() {
        if (ajaxRequestSent) {
            return;
        }
        ajaxRequestSent = true;
        
        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_recommendations', // Nazwa akcji musi pasować do tej w PHP
                nonce: settings.nonce,
                // Można tu dodać ID bieżącego posta, aby go wykluczyć z rekomendacji
                // current_post_id: settings.postId 
            },
            beforeSend: function() {
                // Komunikat ładowania jest już w HTML, więc nie trzeba nic robić.
                // Można tu dodać np. bardziej rozbudowany loader.
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
        // Wyzwalacz czasowy
    const timeInMs = parseInt(settings.triggerByTime, 10) * 1000;
    if (timeInMs > 0) {
        setTimeout(showPopup, timeInMs);
    }
    
    // Nasłuchiwanie na scrollowanie strony dla dwóch pozostałych wyzwalaczy
    $(window).on('scroll', function() {
        if (popupHasBeenShown) {
            $(window).off('scroll'); // Jeśli popup został pokazany, usuń listener, aby oszczędzić zasoby
            return;
        }
        
        const scrollTop = $(this).scrollTop();
    // Wyzwalacz: Procent przescrollowania
        const docHeight = $(document).height();
        const winHeight = $(window).height();
        const scrollableHeight = docHeight - winHeight;
        if (scrollableHeight > 0) {
            const scrollPercent = (scrollTop / scrollableHeight) * 100;
            if (scrollPercent >= parseFloat(settings.triggerByScrollPercent)) {
                showPopup();
                return; // Zatrzymaj dalsze sprawdzanie po aktywacji
            }
        }
    // Wyzwalacz: Scroll w górę po scrollu w dół
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
        lastScrollTop = scrollTop;
    });


    // --- 4. OBSŁUGA ZAMYKANIA POPUPA ---
    
    $closeButton.on('click', hidePopup);
    $overlay.on('click', hidePopup); // Zamykanie po kliknięciu w tło
    
    // Zamykanie po naciśnięciu klawisza "Escape"
    $(document).on('keyup', function(e) {
        if (e.key === "Escape" && $popupContainer.is(':visible')) {
            hidePopup();
        }
    });
});