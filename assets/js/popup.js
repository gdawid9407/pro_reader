jQuery(function($) {
    'use strict';

    // --- 1. INICJALIZACJA I POBRANIE ELEMENTÓW ---

    const settings = window.REP_Popup_Settings || {};
    
    // Sprawdzenie, czy popup jest włączony.
    if (!settings.popupEnable || settings.popupEnable !== '1') {
        return;
    }

    const $popupContainer = $('#rep-intelligent-popup__container');
    const $overlay = $('#rep-intelligent-popup__overlay');
    const $closeButton = $('#rep-intelligent-popup__close');
    const $recommendationList = $('#rep-intelligent-popup__list');

    // Przerwij działanie, jeśli kluczowe elementy nie istnieją w DOM.
    if (!$popupContainer.length) {
        return;
    }

    let popupHasBeenShown = false;
    let ajaxRequestSent = false;
    let lastScrollTop = 0;
    let hasScrolledDown = false;
    const scrollDownThreshold = 500; // Minimalna odległość przewinięcia w dół, aby uznać to za znaczący ruch.


    // --- 2. GŁÓWNE FUNKCJE KONTROLUJĄCE POPUP ---

    function showPopup() {
        if (popupHasBeenShown) {
            return;
        }
        popupHasBeenShown = true;
        
        fetchRecommendations();

        $overlay.add($popupContainer).addClass('is-visible');
        
        // Zablokuj scrollowanie głównej strony, gdy popup jest otwarty.
        $('body').addClass('rep-popup-is-open');
    }
    
    function hidePopup() {
        $overlay.add($popupContainer).removeClass('is-visible');
        
        // Odblokuj scrollowanie.
        $('body').removeClass('rep-popup-is-open');
    }
    
    function fetchRecommendations() {
        if (ajaxRequestSent || !settings.ajaxUrl) {
            return;
        }
        ajaxRequestSent = true;
        
        $.ajax({
            url: settings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_recommendations',
                nonce: settings.nonce,
                current_post_id: settings.currentPostId // WAŻNA ZMIANA: Przekazanie ID posta do backendu.
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

    function initializeTriggers() {
        // Wyzwalacz czasowy
        const timeInMs = parseInt(settings.triggerByTime, 10) * 1000;
        if (timeInMs > 0) {
            setTimeout(showPopup, timeInMs);
        }

        const scrollPercentEnabled = settings.triggerByScrollPercentEnable === '1';
        const scrollUpEnabled = settings.triggerByScrollUp === '1';

        // Nasłuchuj na scroll tylko jeśli którykolwiek z wyzwalaczy jest włączony.
        if (scrollPercentEnabled || scrollUpEnabled) {
            $(window).on('scroll.repPopup', handleScroll);
        }
    }

    function handleScroll() {
        if (popupHasBeenShown) {
            $(window).off('scroll.repPopup'); // Wyłącz nasłuchiwanie po pokazaniu popupa, aby oszczędzić zasoby.
            return;
        }
        
        const scrollTop = $(this).scrollTop();
        
        // Wyzwalacz: Procent przewinięcia
        if (settings.triggerByScrollPercentEnable === '1') {
            const docHeight = $(document).height();
            const winHeight = $(window).height();
            const scrollableHeight = docHeight - winHeight;

            if (scrollableHeight > 0) {
                const currentScrollPercent = (scrollTop / scrollableHeight) * 100;
                if (currentScrollPercent >= parseFloat(settings.triggerByScrollPercent)) {
                    showPopup();
                    return; // Zatrzymaj dalsze sprawdzanie po aktywacji.
                }
            }
        }
        
        // Wyzwalacz: Scroll w górę (exit intent)
        if (settings.triggerByScrollUp === '1') {
            // Sprawdzanie, czy użytkownik przewinął wystarczająco w dół przed scrollowaniem w górę.
            if (scrollTop > lastScrollTop) {
                // Użytkownik scrolluje w dół.
                if (scrollTop > scrollDownThreshold) {
                    hasScrolledDown = true;
                }
            } else if (scrollTop < lastScrollTop) {
                // Użytkownik scrolluje w górę.
                if (hasScrolledDown) {
                    showPopup();
                    return; // Zatrzymaj dalsze sprawdzanie po aktywacji.
                }
            }
        }

        lastScrollTop = scrollTop;
    }


    // --- 4. OBSŁUGA ZDARZEŃ ---
    
    initializeTriggers();

    // Zamykanie popupa
    $closeButton.on('click', hidePopup);
    $overlay.on('click', hidePopup);
    
    // Zamykanie popupa klawiszem Escape.
    $(document).on('keyup', function(e) {
        if (e.key === "Escape" && $popupContainer.hasClass('is-visible')) {
            hidePopup();
        }
    });
});