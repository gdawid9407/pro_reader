(function() {
    document.addEventListener('DOMContentLoaded', function() {

        const progressBarMask = document.getElementById('progress-bar');
        const gradientBackground = document.getElementById('progress-bar-gradient');
        let percentageDisplay = null;

        // Sprawdzenie, czy obiekt z ustawieniami w ogóle istnieje
        if (typeof REP_Progress_Settings === 'undefined') {
            console.error('Reader Engagement Pro: Settings object not found.');
            return;
        }

        // Ustawienie gradientu paska z opcji
        if (REP_Progress_Settings.colorStart && REP_Progress_Settings.colorEnd) {
            gradientBackground.style.background = `linear-gradient(to right, ${REP_Progress_Settings.colorStart}, ${REP_Progress_Settings.colorEnd})`;
        }

        // Inicjalizacja licznika procentowego, jeśli opcja jest włączona
        if (REP_Progress_Settings.showPercentage === '1') {
            percentageDisplay = document.getElementById('rep-progress-percentage');
        }
        
        // Funkcja do obliczania postępu na podstawie całego dokumentu (fallback)
        const updateProgressByPage = () => {
             const pageHeight = Math.max(
                document.body.scrollHeight, document.documentElement.scrollHeight,
                document.body.offsetHeight, document.documentElement.offsetHeight,
                document.body.clientHeight, document.documentElement.clientHeight
            );
            const totalScrollableHeight = pageHeight - window.innerHeight;
            const currentScrollTop = window.scrollY;

            if (totalScrollableHeight <= 0) return 0;
            
            const progress = (currentScrollTop / totalScrollableHeight) * 100;
            return progress;
        };
        
        // NOWA FUNKCJA: Obliczanie postępu na podstawie konkretnego elementu treści
        const updateProgressByContent = (contentElement) => {
            const rect = contentElement.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // Dystans, jaki użytkownik musi przewinąć od momentu, gdy góra elementu
            // pojawi się na górze okna, do momentu, gdy dół elementu zniknie na górze okna.
            const totalScrollableHeightForContent = rect.height;
            
            // Ilość przewiniętej treści. Zaczyna się od 0, gdy góra elementu jest na górze okna.
            const scrolledPastTop = -rect.top;
            
            if (totalScrollableHeightForContent <= 0) return 0;

            const progress = (scrolledPastTop / totalScrollableHeightForContent) * 100;
            return progress;
        };


        const updateProgressBar = () => {
            const contentSelector = REP_Progress_Settings.contentSelector;
            const contentElement = contentSelector ? document.querySelector(contentSelector) : null;
            
            let progressPercentage = 0;

            // 1. Sprawdź, czy selektor treści został podany i czy element istnieje.
            if (contentElement) {
                // Jeśli tak, użyj nowej, precyzyjnej metody obliczeń.
                progressPercentage = updateProgressByContent(contentElement);
            } else {
                // Jeśli nie, użyj starej metody (cała strona).
                // Jest to fallback dla stron, gdzie element nie istnieje (np. strona główna bez artykułów).
                progressPercentage = updateProgressByPage();
            }

            // 2. Ogranicz wartość procentową do przedziału [0, 100].
            // To zapobiega wartościom ujemnym (przed dojechaniem do treści)
            // i wartościom > 100 (po przewinięciu za treść).
            const clampedProgress = Math.max(0, Math.min(100, progressPercentage));

            // 3. Oblicz szerokość maski (kurtyny).
            // Przy 0% postępu, maska ma 100% szerokości. Przy 100% postępu, ma 0% szerokości.
            const maskWidthPercentage = 100 - clampedProgress;
            
            // 4. Zastosuj obliczone wartości do elementów DOM.
            progressBarMask.style.width = `${maskWidthPercentage}%`;

            if (percentageDisplay) {
                percentageDisplay.textContent = `${Math.round(clampedProgress)}%`;
            }
        };

        // Nasłuchuj na zdarzenia scroll i resize.
        window.addEventListener('scroll', updateProgressBar, { passive: true });
        window.addEventListener('resize', updateProgressBar, { passive: true });
        
        // Jednorazowe wywołanie po załadowaniu strony, aby ustawić prawidłowy stan początkowy.
        // Użycie setTimeout daje pewność, że wszystkie elementy (szczególnie obrazy) zdążyły wpłynąć na wysokość strony.
        setTimeout(updateProgressBar, 150);
    });
})();