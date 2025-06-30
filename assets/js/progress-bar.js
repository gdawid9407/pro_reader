(function() {
    document.addEventListener('DOMContentLoaded', function() {

        const progressBarMask = document.getElementById('progress-bar');
        const gradientBackground = document.getElementById('progress-bar-gradient');
        let percentageDisplay = null;

        if (typeof REP_Progress_Settings !== 'undefined') {
            // Ustawianie gradientu z kolorów zdefiniowanych w opcjach.
            if (REP_Progress_Settings.colorStart && REP_Progress_Settings.colorEnd) {
                gradientBackground.style.background = `linear-gradient(to right, ${REP_Progress_Settings.colorStart}, ${REP_Progress_Settings.colorEnd})`;
            }

            // ZMIANA: Sprawdzenie, czy opcja 'showPercentage' jest włączona.
            // Jeśli tak, znajdujemy element licznika w dokumencie.
            if (REP_Progress_Settings.showPercentage === '1') {
                percentageDisplay = document.getElementById('rep-progress-percentage');
            }
        }


        const updateProgressBar = () => {
            // ZMIANA: Bardziej niezawodna metoda obliczania całkowitej wysokości strony.
            // Porównujemy wysokości kilku kluczowych elementów, aby znaleźć faktyczną pełną wysokość.
            const pageHeight = Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.offsetHeight,
                document.body.clientHeight,
                document.documentElement.clientHeight
            );

            const totalScrollableHeight = pageHeight - window.innerHeight;
            const currentScrollTop = window.scrollY;

            if (totalScrollableHeight <= 0) {
                progressBarMask.style.width = '0%';
            if (percentageDisplay) {
                percentageDisplay.textContent = '0%';
                }
                return;
            }

            const progressPercentage = (currentScrollTop / totalScrollableHeight) * 100;
            const maskWidthPercentage = 100 - progressPercentage;

            // Ustawiamy szerokość kurtyny, upewniając się, że jest w zakresie 0-100.
            progressBarMask.style.width = `${Math.max(0, Math.min(100, maskWidthPercentage))}%`;
            if (percentageDisplay) {
                const displayPercentage = Math.round(Math.min(100, progressPercentage));
                percentageDisplay.textContent = `${displayPercentage}%`;
            }
        };

        window.addEventListener('scroll', updateProgressBar, { passive: true });
        window.addEventListener('resize', updateProgressBar, { passive: true });
        
        // Wywołujemy funkcję po krótkim opóźnieniu, aby upewnić się, że strona (w tym obrazy)
        // zdążyła się w pełni wyrenderować i jej wysokość jest ostateczna.
        setTimeout(updateProgressBar, 100);
    });
})();