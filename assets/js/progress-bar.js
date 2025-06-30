
(function() {
    // Czekamy, aż cały dokument (DOM) zostanie wczytany
    document.addEventListener('DOMContentLoaded', function() {

        // Logowanie startowe - sprawdź to w konsoli deweloperskiej (F12)
        console.log('[Pro Reader] Script initialized.');

        // 1. Znajdź element paska postępu na stronie
        const progressBar = document.getElementById('progress-bar');
        
        // Jeśli z jakiegoś powodu paska nie ma na stronie, zakończ działanie skryptu
        if (!progressBar) {
            console.error('[Pro Reader] Progress bar element (#progress-bar) not found.');
            return;
        }

        // Logowanie, że element został znaleziony
        console.log('[Pro Reader] Progress bar element found:', progressBar);

        // 2. Funkcja aktualizująca szerokość paska
        const updateProgressBar = () => {
            // Całkowita wysokość dokumentu, którą można przewinąć.
            // document.documentElement.scrollHeight jest najbardziej niezawodną wartością.
            const totalScrollableHeight = document.documentElement.scrollHeight - window.innerHeight;

            // Obecna pozycja przewinięcia (jak daleko od góry)
            const currentScrollTop = window.scrollY;

            // Zabezpieczenie przed dzieleniem przez zero, jeśli strona nie jest przewijalna
            if (totalScrollableHeight <= 0) {
                progressBar.style.width = '100%';
                return;
            }

            // Oblicz postęp jako procent
            const progressPercentage = (currentScrollTop / totalScrollableHeight) * 100;
            
            // Ustaw szerokość paska, upewniając się, że nie przekracza 100%
            progressBar.style.width = `${Math.min(progressPercentage, 100)}%`;

            // Logowanie wartości w trakcie przewijania (możesz to usunąć po testach)
            // console.log(`Scroll: ${currentScrollTop}, Total: ${totalScrollableHeight}, Progress: ${progressPercentage.toFixed(2)}%`);
        };

        // 3. Nasłuchuj na zdarzenie przewijania
        // Opcja { passive: true } to ważna optymalizacja wydajności
        window.addEventListener('scroll', updateProgressBar, { passive: true });

        // 4. Nasłuchuj na zmianę rozmiaru okna (np. obrót telefonu)
        window.addEventListener('resize', updateProgressBar, { passive: true });

        // 5. Wywołaj funkcję raz na starcie, aby ustawić początkową pozycję
        // Używamy setTimeout, aby dać przeglądarce chwilę na obliczenie wymiarów
        setTimeout(updateProgressBar, 100);
    });
})();