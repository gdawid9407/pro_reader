=== Pro Reader ===
Contributors: (Twoja nazwa użytkownika na wp.org, np. dawid-golis)
Tags: reading progress, progress bar, popup, recommendations, related posts, read more, engagement, post, block
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zwiększ zaangażowanie czytelników za pomocą dynamicznego paska postępu i inteligentnego popupa z rekomendacjami artykułów.

== Description ==

**Pro Reader** to wszechstronne narzędzie zaprojektowane, aby zwiększyć zaangażowanie i czas spędzany przez użytkowników na Twojej stronie.
   Wtyczka oferuje dwa kluczowe, w pełni konfigurowalne moduły: dynamiczny pasek postępu czytania oraz inteligentne okno popup z rekomendacjami "Czytaj Więcej".

Dzięki zaawansowanym opcjom personalizacji, możesz precyzyjnie dostosować każdy element do wyglądu i potrzeb Twojej witryny, poprawiając doświadczenia użytkownika i zachęcając go do odkrywania nowych treści.

**Główne Funkcjonalności:**

*   **Dynamiczny Pasek Postępu Czytania**
    *   Wizualnie informuje użytkownika o postępie czytania artykułu.
    *   Pełna kontrola nad pozycją (góra/dół), wysokością, szerokością i przezroczystością.
    *   Możliwość ustawienia gradientu kolorów dla paska.
    *   Opcjonalne wyświetlanie procentowego postępu na pasku.
    *   Zaawansowana opcja śledzenia postępu tylko w obrębie kontenera z treścią (np. `.entry-content`) dla większej precyzji.
    *   Możliwość włączenia modułu tylko dla wybranych typów treści (np. wpisy, strony).

*   **Inteligentny Popup "Czytaj Więcej"**
    *   Zaawansowane wyzwalacze wyświetlania: po określonym czasie, po przewinięciu strony o zadany procent lub przy próbie opuszczenia strony (scroll w górę).
    *   **Unikalny system rekomendacji** oparty na logice:
        *   **Popularność:** Na podstawie liczby wewnętrznych linków prowadzących do danego artykułu (wtyczka sama buduje indeks).
        *   **Data:** Wyświetlanie najnowszych wpisów.
        *   **Hybrydowa:** Inteligentne łączenie obu powyższych metod.
    *   Pełna personalizacja wyglądu:
        *   Wizualny edytor (TinyMCE) dla niestandardowej treści w popupie.
        *   Wybór układu rekomendacji (lista lub siatka).
        *   **Konstruktor układu elementu:** Przeciągnij i upuść, aby zmienić kolejność i widoczność komponentów (miniaturka, tytuł, zajawka, metadane, przycisk).
        *   Rozbudowane opcje stylizacji przycisków, miniaturek i tekstu.
    *   **Podgląd na żywo** w panelu administracyjnym, który aktualizuje się w czasie rzeczywistym podczas zmiany ustawień.

*   **Wydajność**
    *   Rekomendacje w popupie są ładowane asynchronicznie (AJAX), dzięki czemu nie spowalniają początkowego renderowania strony.
    *   Zoptymalizowane zapytania i nowoczesna struktura kodu zapewniają płynne działanie.

== Installation ==

**Prosta Instalacja (z panelu WordPress)**

1.  Przejdź do sekcji `Wtyczki > Dodaj nową` w panelu administracyjnym WordPress.
2.  Kliknij przycisk `Wyślij wtyczkę na serwer`.
3.  Wybierz plik `.zip` z wtyczką i kliknij `Zainstaluj teraz`.
4.  Po zakończeniu instalacji kliknij `Włącz wtyczkę`.
5.  Przejdź do nowej pozycji w menu "Pro Reader", aby skonfigurować wtyczkę.

**Instalacja dla Deweloperów (wymaga Composera)**

Ta wtyczka korzysta z autoloadera PSR-4 do zarządzania klasami. Jeśli instalujesz wtyczkę z repozytorium Git, musisz wygenerować pliki autoloadera.

1.  Sklonuj repozytorium wtyczki do katalogu `wp-content/plugins/`.
2.  Otwórz terminal i przejdź do głównego katalogu wtyczki (np. `cd wp-content/plugins/pro-reader`).
3.  Uruchom polecenie `composer install`, aby pobrać zależności i wygenerować plik `vendor/autoload.php`.
4.  Włącz wtyczkę w panelu administracyjnym WordPress.

== Frequently Asked Questions ==

= Pasek postępu / popup nie wyświetla się na mojej stronie. Co robić? =

Upewnij się, że wykonałeś następujące kroki:
1.  W panelu admina przejdź do `Pro Reader > Pasek Postępu` (lub `Popup "Czytaj Więcej"`).
2.  Zaznacz pole "Włącz Moduł..." na samej górze ustawień.
3.  W sekcji "Wyświetlaj na" zaznacz typy treści (np. "Wpisy"), na których moduł ma być aktywny.
4.  Zapisz zmiany.

= Rekomendacje w popupie są puste lub brakuje popularnych artykułów. Dlaczego? =

Wtyczka buduje własny indeks "popularności" na podstawie linków wewnętrznych między Twoimi artykułami.
1.  **Po pierwszej aktywacji:** Indeks jest pusty. Zostaną pokazane tylko najnowsze posty. Aby zbudować indeks, musisz zaktualizować swoje istniejące wpisy (wystarczy otworzyć i kliknąć "Zaktualizuj").
2.  **Ręczne indeksowanie:** Możesz też przejść do `Pro Reader > Popup "Czytaj Więcej"` i na dole strony kliknąć przycisk **"Uruchom pełne indeksowanie"**. 
Spowoduje to przeskanowanie wszystkich opublikowanych wpisów i zbudowanie bazy linków. Proces ten może zająć chwilę na stronach z dużą ilością treści.

= Czy mogę dostosować kolory i wygląd elementów? =

Tak. Prawie każdy element wizualny wtyczki można dostosować. Przejdź do panelu ustawień wtyczki, a znajdziesz tam szczegółowe opcje dla kolorów, czcionek, układu, rozmiarów i wielu innych.
Zakładka "Popup" oferuje dodatkowo podgląd na żywo, który ułatwia personalizację.

== Screenshots ==

1.  **Panel Ustawień Paska Postępu.** Konfiguracja wyglądu, pozycji i zachowania paska postępu.
2.  **Panel Ustawień Popupa.** Rozbudowane opcje wyzwalaczy, logiki rekomendacji oraz treści.
3.  **Konstruktor Układu i Podgląd na Żywo.** Potężne narzędzie do personalizacji wyglądu rekomendacji z natychmiastowym podglądem zmian.
4.  **Przykład działania Paska Postępu.** Pasek postępu widoczny na górze strony artykułu.
5.  **Przykład działania Popupa.** Popup wyświetlony na stronie z rekomendacjami w układzie listy.
6.  **Przykład działania Popupa w układzie siatki.** Alternatywny, horyzontalny układ rekomendacji.

== Changelog ==

= 1.1.0 (Data Twojej refaktoryzacji) =
*   **Refaktoryzacja:** Całkowita przebudowa struktury kodu wtyczki w celu zwiększenia czytelności, wydajności i łatwości dalszego rozwoju.
*   **Nowa struktura:** Wprowadzono nową, logiczną strukturę katalogów z podziałem na `Admin`, `Core`, `Database`, `Frontend` i `Templates`.
*   **Standard PSR-4:** Wtyczka jest teraz w pełni zgodna ze standardem autoloadingu PSR-4.
*   **Poprawka:** Naprawiono skrypt deinstalacyjny (`uninstall.php`), aby poprawnie usuwał opcje i tabele z bazy danych.
*   **Usprawnienie:** Wydzielono kod JavaScript z panelu admina do dedykowanego pliku `admin-settings.js`.
*   **Usprawnienie:** Wydzielono kod HTML komponentów (popup, pasek postępu) do dedykowanych plików szablonów, oddzielając logikę od warstwy prezentacji.

= 1.0.0 =
*   Pierwsze wydanie wtyczki.

== For Developers: Code Structure Overview ==

The plugin follows modern PHP development practices, utilizing PSR-4 autoloading and a clear separation of concerns. The code is organized into a `src/` directory, which contains all PHP classes.

**Key Directories within `src/`:**

*   **/src/Core/**: Contains the main plugin bootstrapping logic.
    *   `Plugin.php`: The main plugin controller. It initializes all other components and hooks into WordPress.
    *   `Installer.php`: Handles activation logic, such as creating database tables.
    *   `AjaxHandler.php`: A centralized handler for all AJAX requests within the plugin.

*   **/src/Frontend/**: Contains classes responsible for frontend logic and rendering.
    *   `ProgressBar.php`: The controller for the reading progress bar module.
    *   `Popup.php`: The controller for the recommendations popup module.

*   **/src/Database/**: Contains classes that interact directly with the database.
    *   `LinkIndexer.php`: Scans post content for internal links and populates the custom index table.
    *   `RecommendationQuery.php`: Executes complex queries to fetch popular and latest posts for the popup.

*   **/src/Admin/**: Contains classes for the WordPress admin area.
    *   `Settings_Page.php`: Builds the main settings page with its tabs.
    *   `Settings_Popup.php` & `Settings_Progress_Bar.php`: Register and sanitize settings fields for each module.

*   **/src/Templates/**: Contains all HTML view files, separated from the PHP logic. This allows for easy customization of the frontend components.
    *   `/popup/main-popup.php`: The main HTML structure of the popup container.
    *   `/popup/recommendation-item.php`: The template for a single recommended post item.
    *   `/popup/preview.php`: The template for the live preview in the admin panel.
    *   `/progress-bar/bar.php`: The HTML structure for the progress bar.

**Asset Management:**

*   Frontend and admin JavaScript files are located in `/assets/js/`.
*   CSS files are located in `/assets/css/`.
*   The project may use `webpack.config.js` for bundling and processing assets.

This structure makes the codebase clean, maintainable, and easy to extend with new features.

== Upgrade Notice ==

= 1.1.0 =
This is a major code refactoring release. While all functionality remains the same and settings are preserved, the file structure has been completely reorganized for better performance and maintainability. It is recommended to backup your site before upgrading, as is standard practice.

== Additional Information ==

**How the "Popularity" Logic Works**

Unlike other plugins that rely on view counts (which can be inaccurate and heavy on the database), Pro Reader uses a unique and lightweight method to determine post popularity.

1.  **Indexing:** When a post is published or updated, the plugin scans its content for any internal links (`<a href="...">`) pointing to other posts on your site.
2.  **Database Table:** Each found link is stored as a relationship in a custom, optimized database table (`wp_rep_link_index`). For example, if "Post A" links to "Post B", a record is created.
3.  **Calculating Popularity:** When the popup needs to show popular posts, it simply queries this table to find which posts are **linked to the most often**. This is a powerful indicator of which content you, as the author, consider important and foundational.

This method is fast, efficient, and provides a more editorially-driven measure of popularity than simple page views.

**Customizing Templates**

For advanced users, the HTML output of the progress bar and popup can be customized by overriding the template files. Since the plugin's templates are loaded via a direct `include`, the recommended way to do this without losing changes on update is to use a filter.

*While the plugin does not currently include filters for template paths, this is a planned feature for future versions. In the meantime, any direct modifications to the template files in `src/Templates/` will be overwritten during a plugin update.*

== Future Development ==

Pro Reader is under active development. Here are some of the features planned for future releases:

*   **Template Override System:** Introducing filters to allow developers to safely override the HTML templates from within their theme.
*   **More Recommendation Sources:** Adding new sources for recommendations, such as posts from the same category/tag or hand-picked recommendations per post.
*   **A/B Testing for Popups:** The ability to create multiple versions of a popup to test which content or triggers are most effective.
*   **Advanced Analytics:** Built-in statistics to track popup views, clicks, and conversion rates.
*   **Gutenberg Block:** Further development of the "Pro Reader Popup" as a dynamic Gutenberg block.

If you have a feature request or have found a bug, please create an issue on the plugin's GitHub repository (if available).