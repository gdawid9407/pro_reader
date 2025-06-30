(function() {
    document.addEventListener('DOMContentLoaded', function() {

        // ZMIANA: Pobieramy teraz dwa elementy: maskę/kurtynę i tło z gradientem.
        const progressBarMask = document.getElementById('progress-bar');
        const gradientBackground = document.getElementById('progress-bar-gradient');

        // Sprawdzamy, czy oba kluczowe elementy istnieją.
        if (!progressBarMask || !gradientBackground) {
            console.error('[Pro Reader] Progress bar elements not found.');
            return;
        }

        // ZMIANA: Ustawiamy tło z gradientem na elemencie tła, a nie na masce.
        if (typeof REP_Progress_Settings !== 'undefined' && REP_Progress_Settings.colorStart && REP_Progress_Settings.colorEnd) {
            gradientBackground.style.background = `linear-gradient(to right, ${REP_Progress_Settings.colorStart}, ${REP_Progress_Settings.colorEnd})`;
        }

        // ZMIANA: Zaktualizowana funkcja do sterowania "kurtyną".
        const updateProgressBar = () => {
            const totalScrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
            const currentScrollTop = window.scrollY;

            if (totalScrollableHeight <= 0) {
                // Jeśli nie ma przewijania, kurtyna ma 0% szerokości (pasek jest pełny).
                progressBarMask.style.width = '0%';
                return;
            }

            const progressPercentage = (currentScrollTop / totalScrollableHeight) * 100;

            // Obliczamy szerokość kurtyny. Jeśli postęp to 10%, kurtyna ma 90% szerokości.
            // Jeśli postęp to 100%, kurtyna ma 0% szerokości.
            const maskWidthPercentage = 100 - progressPercentage;

            // Ustawiamy szerokość kurtyny, upewniając się, że jest w zakresie 0-100.
            progressBarMask.style.width = `${Math.max(0, Math.min(100, maskWidthPercentage))}%`;
        };

        window.addEventListener('scroll', updateProgressBar, { passive: true });
        window.addEventListener('resize', updateProgressBar, { passive: true });

        setTimeout(updateProgressBar, 100);
    });
})();