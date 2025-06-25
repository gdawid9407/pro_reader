// Skrypt do śledzenia scrolla

document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1,
    };

    const progressBar = document.getElementById('progress-bar');
    if (!progressBar) return;

    const sections = document.querySelectorAll('.post-content');

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            // Oblicz procent widocznej części sekcji
            const progress = Math.round(entry.intersectionRatio * 100);
            // Aktualizacja paska tylko przy zmianie wartości
            if (parseInt(progressBar.style.width) !== progress) {
                progressBar.style.width = `${progress}%`;
            }
        });
    }, observerOptions);

    sections.forEach(section => observer.observe(section));
});