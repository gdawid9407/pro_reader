/wp-content/plugins/pro-reader/
│
├── assets/                           # Folder na pliki frontendowe
│   ├── css/                          # Folder na pliki CSS
│   │   └── style.css                 # Główny plik CSS
│   ├── js/                           # Folder na pliki JavaScript
│   │   ├── script.js                 # Główny plik JS (wejściowy dla Webpack)
│   │   ├── progress-bar.js           # Skrypt odpowiedzialny za pasek postępu
│   │   ├── popup.js                  # Skrypt odpowiedzialny za popup "Czytaj więcej"
│   │   └── recommendations.js        # Skrypt odpowiedzialny za rekomendacje artykułów
│   └── images/                       # Folder na obrazki
│       └── placeholder.jpg           # Obrazki (np. placeholdery)
│
├── includes/                         # Folder na pliki PHP
│   ├── class-progress-bar.php        # Klasa do obsługi paska postępu
│   ├── class-popup.php               # Klasa do obsługi popupów
│   ├── class-recommendations.php     # Klasa do obsługi rekomendacji
│   ├── class-integration.php         # Klasa do integracji z page builderami
│   └── class-settings.php            # Klasa do konfiguracji w panelu administracyjnym
│
├── templates/                        # Folder na szablony HTML
│   ├── progress-bar-template.php     # Szablon HTML dla paska postępu
│   ├── popup-template.php            # Szablon HTML dla popupu
│   └── recommendations-template.php  # Szablon HTML dla rekomendacji
│
├── languages/                        # Folder na pliki tłumaczeń
│   └── reader-engagement-pro.pot     # Plik do tłumaczeń
│
├── reader-engagement-pro.php         # Główny plik wtyczki
├── uninstall.php                     # Plik odpowiedzialny za deinstalację wtyczki
└── readme.txt                        # Dokumentacja wtyczki



1. **Przygotowanie środowiska i struktury wtyczki**

   * Utworzenie folderu wtyczki w katalogu `wp-content/plugins`.
   * Zdefiniowanie pliku głównego wtyczki (np. `reader-engagement-pro.php`).
   * Rejestracja wtyczki w systemie WordPress (dodanie nagłówka w pliku głównym).

2. **Moduł Śledzenia Postępu**

   * Implementacja śledzenia pozycji scrolla za pomocą API Intersection Observer.
   * Obliczanie progresu czytania w czasie rzeczywistym.
   * Tworzenie opcji konfiguracji w panelu administracyjnym (pozycja paska, kolorystyka, przezroczystość).
   * Dodanie funkcji wykluczania nagłówków/stopek i elementów sticky.

3. **Moduł Wyzwalania Popupów**

   * Implementacja popupu "Czytaj Więcej".
   * Tworzenie wyzwalaczy popupu (procent postępu, czas spędzony na stronie, kierunek scrolla).
   * Dodanie edytora treści popupu (WYSIWYG) w panelu administracyjnym.
   * Możliwość konfiguracji czasu opóźnienia i wyglądu popupu.

4. **Moduł Rekomendacji Artykułów**

   * Implementacja silnika rekomendacji na podstawie wybranych kryteriów (popularność, tagi, data).
   * Tworzenie algorytmu doboru artykułów (automatyczny lub ręczny).
   * Konfiguracja stylu wyświetlania i liczby rekomendacji w panelu administracyjnym.
   * Opcjonalna integracja z zewnętrznymi API (np. do analizy treści lub popularności).

5. **Moduł Integracji z Page Builderami**

   * Utworzenie dedykowanego widgetu dla Elementora.
   * Implementacja shortcode dla WP Bakery.
   * Stworzenie bloku dla Gutenberga.
   * Umożliwienie integracji z natywnym edytorem WordPressa (shortcode/Hook).

6. **Panel Administracyjny**

   * Dodanie sekcji do konfiguracji wtyczki w panelu administracyjnym WordPressa.
   * Możliwość ustawiania opcji dla każdego z modułów (pasek postępu, popup, rekomendacje).
   * Obsługa szablonów i typów postów.

7. **Optymalizacja Wydajności**

   * Implementacja lazy loadingu dla skryptów i obrazów.
   * Cache’owanie wyników rekomendacji (np. Redis/Memcached).
   * Walidacja danych wejściowych przez WP API.
   * Optymalizacja pod kątem Core Web Vitals (CLS, LCP).

8. **Instalacja i Wdrożenie**

   * Przygotowanie skryptów instalacyjnych.
   * Dodanie instrukcji konfiguracji w dokumentacji.
   * Testowanie wtyczki na różnych wersjach WordPressa (5.6+).

9. **Testowanie i Debugowanie**

   * Testowanie poprawności działania każdego modułu.
   * Testowanie integracji z popularnymi page builderami (Elementor, WP Bakery, Gutenberg).
   * Wykonywanie testów wydajnościowych (Core Web Vitals, Lazy Loading, cache).

10. **Publikacja i Dokumentacja**

    * Przygotowanie dokumentacji użytkownika i dewelopera.
    * Publikacja wtyczki w repozytorium WordPressa.
    * Zbieranie opinii użytkowników i ewentualne poprawki.
