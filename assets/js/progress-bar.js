(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const progressBarMask = document.getElementById('progress-bar');
        const gradientBackground = document.getElementById('progress-bar-gradient');
        
        // Zwróć błąd, jeśli kluczowe elementy paska nie istnieją
        if (!progressBarMask || !gradientBackground) {
            console.error('Pro Reader: Progress bar HTML elements not found.');
            return;
        }

        let percentageDisplay = null;
        let contentElement = null;
        let useCustomSelector = false;

        // Inicjalizacja ustawień z obiektu przekazanego przez PHP
        if (typeof REP_Progress_Settings !== 'undefined') {
            const settings = REP_Progress_Settings;
            
            // Ustaw gradient
            if (settings.colorStart && settings.colorEnd) {
                gradientBackground.style.background = `linear-gradient(to right, ${settings.colorStart}, ${settings.colorEnd})`;
            }

            // Znajdź element do wyświetlania procentów, jeśli opcja jest włączona
            if (settings.showPercentage === '1') {
                percentageDisplay = document.getElementById('rep-progress-percentage');
                
                // === POCZĄTEK ZMIANY ===
                if (percentageDisplay) {
                    // Odczytaj pozycję z ustawień (przekazaną z PHP jako `percentagePosition`)
                    const position = settings.percentagePosition || 'center'; // Użyj 'center' jako domyślnej
                    
                    // Usuń istniejące klasy pozycji, aby uniknąć konfliktów
                    percentageDisplay.classList.remove('position-left', 'position-center', 'position-right');
                    
                    // Dodaj nową klasę na podstawie odczytanego ustawienia
                    percentageDisplay.classList.add(`position-${position}`);
                }
                // === KONIEC ZMIANY ===
            }

            // Sprawdź, czy selektor treści jest zdefiniowany i znajdź ten element na stronie
            if (settings.contentSelector) {
                contentElement = document.querySelector(settings.contentSelector);
                if (contentElement) {
                    useCustomSelector = true; // Znaleziono element, użyjemy nowej logiki
                } else {
                    console.warn(`Pro Reader: Element with selector "${settings.contentSelector}" not found. Falling back to full page height.`);
                }
            }
        }

        const updateProgressBar = () => {
            let progressPercentage = 0;

            if (useCustomSelector && contentElement) {
                const elementRect = contentElement.getBoundingClientRect();
                const elementTop = elementRect.top + window.scrollY; 
                const elementHeight = contentElement.scrollHeight;

                const totalScrollableDistanceInElement = elementHeight - window.innerHeight;
                
                if (totalScrollableDistanceInElement <= 0) {

                    progressPercentage = (elementRect.top < 0) ? 100 : 0;
                } else {

                    const currentScrollInElement = window.scrollY - elementTop;
                    progressPercentage = (currentScrollInElement / totalScrollableDistanceInElement) * 100;
                }
                
            } else {

                const pageHeight = Math.max(
                    document.body.scrollHeight, document.documentElement.scrollHeight,
                    document.body.offsetHeight, document.documentElement.offsetHeight,
                    document.body.clientHeight, document.documentElement.clientHeight
                );
                const totalScrollableHeight = pageHeight - window.innerHeight;

                if (totalScrollableHeight <= 0) {
                    progressPercentage = 100;
                } else {
                    progressPercentage = (window.scrollY / totalScrollableHeight) * 100;
                }
            }


            const clampedProgress = Math.max(0, Math.min(100, progressPercentage));

            progressBarMask.style.width = `${100 - clampedProgress}%`;

            if (percentageDisplay) {
                percentageDisplay.textContent = `${Math.round(clampedProgress)}%`;
            }
        };

        window.addEventListener('scroll', updateProgressBar, { passive: true });
        window.addEventListener('resize', updateProgressBar, { passive: true });
        setTimeout(updateProgressBar, 150);
    });
})();