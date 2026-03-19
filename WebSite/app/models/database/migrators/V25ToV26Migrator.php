<?php

declare(strict_types=1);

namespace app\models\database\migrators;

use PDO;

use app\interfaces\DatabaseMigratorInterface;

class V25ToV26Migrator implements DatabaseMigratorInterface
{
    public function upgrade(PDO $pdo, int $currentVersion): int
    {
        $simpleLabels = [
            1  => 'Wybierz język',
            2  => 'Język',
            3  => 'Moje dane',
            4  => 'Strefa administratora',
            5  => 'Wyloguj',
            6  => 'Pomoc kontekstualna',
            7  => 'Głosuj',
            8  => '(Musisz być zalogowany)',
            9  => 'Strona główna',
            10 => 'Utworzony przez',
            11 => 'zmodyfikowano dnia',
            12 => 'dnia',
            13 => 'Twoje wydarzenia',
            14 => 'Wszystkie wydarzenia',
            15 => 'Typ',
            16 => 'Podsumowanie',
            17 => 'Miejsce',
            18 => 'Data i godzina',
            19 => 'Czas trwania',
            20 => 'Atrybuty',
            21 => 'Opis',
            22 => 'Uczestnicy',
            23 => 'Odbiorcy',
            24 => 'Członkowie',
            25 => 'Publiczne',
            26 => 'Zapisz się',
            27 => 'Wypisz się',
            28 => 'Komplet',
            29 => 'Brak atrybutów',
            30 => 'Brak uczestników',
            31 => 'Logowanie',
            32 => 'Edytuj',
            33 => 'Wiadomości',
            34 => 'Usuń',
            35 => 'Duplikuj',
            36 => 'Wyślij e-mail',
            37 => 'Aktualności',
            38 => 'Katalog',
            39 => 'Statystyki',
            40 => 'Preferencje',
            41 => 'Grupy',
            42 => 'Dostępności',
            43 => 'Konto',
            44 => 'Gość',
            45 => 'Rano',
            46 => 'Południe',
            47 => 'Wieczór',
            48 => 'Nieznany użytkownik (e-mail)',
            49 => '--- Strona główna ---',
            50 => '--- Wiadomości ---',
            51 => '--- Strony błędów ---',
            52 => '--- Pomoce ---',
            54 => 'Pomoc projektanta',
            55 => 'Pomoc animatora',
            57 => 'Pomoc zarządcy osób',
            58 => 'Pomoc redaktora',
            59 => 'Pomoc użytkownika',
            60 => 'Pomoc statystyk odwiedzających',
            61 => 'Pomoc webmastera',
            62 => 'Nagłówek strony głównej',
            63 => 'Stopka strony głównej',
            72 => 'Połączenia',
            76 => 'Zapisz',
            77 => 'Anuluj',
            79 => 'Notatnik',
            87  => 'Artykuł {id} nie istnieje',
            88  => 'Nieznany autor artykułu {id}',
            89  => 'Musisz być zalogowany, aby wyświetlić ten artykuł',
            90  => 'Wystąpił błąd podczas aktualizacji artykułu',
            91  => 'Tytuł i treść są wymagane',
            92  => 'Artykuł został pomyślnie zaktualizowany',
            93  => 'E-mail wysłany do subskrybentów',
            94  => 'Nowy artykuł jest dostępny na {root}',
            95  => 'Zgodnie z Twoimi preferencjami, ta wiadomość informuje Cię o nowym artykule',
            96  => 'Aby przestać otrzymywać te e-maile, zaktualizuj swoje preferencje',
            97  => 'Redaktorzy vs odbiorcy',
            98  => 'Utworzony przez',
            99  => 'Tytuł',
            100 => 'Ostatnia modyfikacja',
            101 => 'Grupa',
            102 => 'Opublikowany',
            103 => 'Ankieta',
            104 => 'Treść',
        ];
        foreach ($simpleLabels as $id => $translation) {
            $escaped = str_replace("'", "''", $translation);
            $pdo->exec("UPDATE Languages SET pl_PL = '{$escaped}' WHERE Id = {$id}");
        }

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">🚫 Błąd 403 – Brak dostępu</h1>

    <p class="mt-3">
      Wygląda na to, że próbujesz uzyskać dostęp do chronionej strony.<br>
      Spokojnie! Jeśli ta strona pojawia się zaraz po otwarciu przeglądarki, prawdopodobnie dlatego że:
    </p>
    <ul class="text-start mx-auto d-inline-block">
      <li>Twoja przeglądarka automatycznie otworzyła <strong>ostatnio odwiedzone strony</strong>.</li>
      <li>Podczas ostatniej wizyty na naszej stronie nie <strong>wylogowałeś/aś się</strong>.</li>
    </ul>
    <p class="mt-3">
      👉 W takim przypadku jest to całkowicie normalne.
    </p>
    <p class="fw-bold">
      💡 Wskazówka: Jeśli zaznaczysz opcję <em>„Zapamiętaj mnie"</em> podczas logowania, następnym razem zostaniesz automatycznie zalogowany/a i ta strona się nie pojawi.
    </p>

    <hr class="my-4">

    <h5>ℹ️ Inne możliwe sytuacje:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Jeśli to aplikacja wywołała ten błąd: <strong>prosimy poinformować webmastera</strong>.</li>
      <li>➡️ Jeśli próbowałeś/aś wpisać adres strony bezpośrednio w przeglądarce: niezły pomysł 😉 ale ta strona wymaga określonych uprawnień.</li>
      <li>➡️ Jeśli udało Ci się wyświetlić chronione informacje <strong>bez pojawienia się tej strony</strong>: <strong>natychmiast poinformuj webmastera</strong>, aby to naprawił.</li>
    </ul>

    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>'
WHERE Id = 64
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">🔍 Błąd 404 – Strona nie znaleziona</h1>
    <p class="mt-3">
      Strona, której szukasz, nie istnieje lub nie jest już dostępna.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Możliwe przyczyny:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Aplikacja omyłkowo Cię tu przekierowała: <strong>prosimy poinformować webmastera</strong>.</li>
      <li>➡️ Próbowałeś/aś zgadnąć adres w pasku przeglądarki: <em>niezły pomysł 😉 ale ta strona nie istnieje</em>.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>'
WHERE Id = 65
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">💥 Błąd 500 – Wewnętrzny błąd serwera</h1>
    <p class="mt-3">
      Ups… coś poszło nie tak po naszej stronie.<br>
      Ten błąd wynika z wewnętrznego problemu aplikacji.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Co zrobić?</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ <strong>Prosimy poinformować webmastera</strong>, aby mógł naprawić problem.</li>
      <li>➡️ Możesz też spróbować ponownie za chwilę — czasem serwer po prostu potrzebuje kawy ☕.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót do strony głównej</a>
  </div>
</div>'
WHERE Id = 66
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="alert alert-warning" role="alert">
  <p>🔒 <strong>Ups… ten zasób jest zarezerwowany dla zalogowanych członków!</strong></p>
  <p>Musisz się zalogować, aby uzyskać do niego dostęp.</p>
  <p>💡 Wybierając opcję „Zapamiętaj mnie", Twoja przeglądarka następnym razem rozłoży przed Tobą 🟥czerwony dywan🟥 — bez podawania hasła.</p>
</div>'
WHERE Id = 67
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="text-center full-screen d-flex flex-column justify-content-center align-items-center">
    <div class="emoji">🚧</div>
    <h1 class="mt-4">Strona w trakcie konserwacji</h1>
    <p class="text-muted">Za 30 sekund zostaniesz przekierowany/a na stronę główną...</p>
    <a href="/" class="btn btn-primary mt-3">Wróć teraz na stronę główną</a>
</div>
<style>
    .full-screen {
      height: 100vh;
    }
    .emoji {
      font-size: 10rem;
    }
</style>'
WHERE Id = 68
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-success">📧 E-mail wysłany!</h1>
    <p class="mt-3">
      E-mail z linkiem do <strong>utworzenia nowego hasła</strong> został wysłany na podany adres.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Uwaga:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Sprawdź folder <strong>spam</strong> lub <strong>niechciana poczta</strong>, jeśli nie widzisz wiadomości w skrzynce głównej.</li>
      <li>➡️ Kliknij po prostu link w e-mailu, aby ustawić nowe hasło.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>'
WHERE Id = 69
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-danger">⚠️ Nie udało się wysłać e-maila</h1>
    <p class="mt-3">
      E-mail z resetowaniem hasła <strong>nie mógł zostać wysłany</strong> na podany adres.
    </p>
    <hr class="my-4">
    <h5>ℹ️ Sprawdź następujące kwestie:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Upewnij się, że podany adres e-mail jest poprawny i zarejestrowany w naszym systemie.</li>
      <li>➡️ Jeśli problem się powtarza, skontaktuj się z <strong>webmasterem</strong> lub administratorem strony.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>'
WHERE Id = 70
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container text-center mt-5">
  <div class="card shadow-lg rounded-3 p-4">
    <h1 class="text-warning">📧 Nieznany adres e-mail</h1>
    <p class="mt-3">
      Podany adres e-mail <strong>nie istnieje</strong> w naszym systemie.
    </p>
    <hr class="my-4">
    <h5>🔍 Sprawdź następujące kwestie:</h5>
    <ul class="text-start mx-auto d-inline-block">
      <li>➡️ Upewnij się, że adres e-mail został wpisany poprawnie, bez literówek.</li>
      <li>➡️ Jeśli nigdy nie tworzyłeś/aś konta, skontaktuj się z administratorem strony w celu jego założenia.</li>
      <li>➡️ W razie wątpliwości, skontaktuj się z <strong>webmasterem</strong> lub administratorem klubu.</li>
    </ul>
    <a href="/" class="btn btn-primary mt-3">🏠 Powrót na stronę główną</a>
  </div>
</div>'
WHERE Id = 71
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages
SET en_US = REPLACE(en_US, '''''', ''''),
    fr_FR = REPLACE(fr_FR, '''''', '''')
WHERE Id IN (73, 74, 75)
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>📁 Plik tekstu nie znaleziony</h1>
                        <p class=''mt-3''>
                        Nie można odnaleźć pliku z tekstem piosenki.<br>
                        Upewnij się, że piosenka istnieje i że jej plik z tekstem (<code>.lrc</code>) ma właściwą nazwę.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Nazwa pliku może nie odpowiadać nazwie piosenki.</li>
                        <li>➡️ Plik mógł zostać przeniesiony lub usunięty.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>'
WHERE Id = 73
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>🔒 Plik tekstu niedostępny</h1>
                        <p class=''mt-3''>
                        Plik z tekstem piosenki istnieje, ale nie można go odczytać.<br>
                        Sprawdź uprawnienia pliku lub skontaktuj się z administratorem.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Plik może nie mieć odpowiednich uprawnień do odczytu.</li>
                        <li>➡️ Plik może być zablokowany lub uszkodzony.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>'
WHERE Id = 74
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class=''container text-center mt-5''>
                    <div class=''card shadow-lg rounded-3 p-4''>
                        <h1 class=''text-danger''>⚠️ Błąd odczytu pliku tekstu</h1>
                        <p class=''mt-3''>
                        Wystąpił nieoczekiwany błąd podczas odczytu pliku z tekstem piosenki.<br>
                        Sprawdź zawartość pliku lub spróbuj ponownie później.
                        </p>
                        <ul class=''text-start mx-auto d-inline-block mt-3''>
                        <li>➡️ Plik może być uszkodzony.</li>
                        <li>➡️ Serwer napotkał tymczasowy błąd operacji wejścia/wyjścia.</li>
                        </ul>
                        <a href=''/'' class=''btn btn-primary mt-4''>🏠 Powrót na stronę główną</a>
                    </div>
                </div>'
WHERE Id = 75
SQL);

        $pdo->exec(<<<'SQL'
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
    <h5 class="alert-heading">Witaj w swoim obszarze osobistym</h5>
    <p>
        Tutaj możesz przeglądać i aktualizować swoje informacje.
    </p>
    <p class="mb-0">
        👉 Jeśli przycisk ☰ jest widoczny w prawym górnym rogu, kliknij go, aby uzyskać dostęp do menu.
    </p>
    <p class="mb-0">
        💡 Możesz też kliknąć bezpośrednio na poniższe linki, aby przejść do różnych opcji.
    </p>
</div>
<div class="user-links mt-3 mb-3">
    <div class="d-flex flex-wrap gap-3">
        <a href="/user/account" class="{if $page == ''account''}active{/if} text-decoration-none">👤 Konto</a>
        <a href="/user/availabilities" class="{if $page == ''account''}active{/if} text-decoration-none">🕒 Dostępności</a>
        <a href="/user/groups" class="{if $page == ''account''}active{/if} text-decoration-none">🔐 Grupy</a>
        <a href="/user/preferences" class="{if $page == ''account''}active{/if} text-decoration-none">⭐ Preferencje</a>
        <a href="/user/notifications" class="{if $page == ''account''}active{/if} text-decoration-none">🔔 Powiadomienia</a>
        <a href="/user/statistics" class="{if $page == ''account''}active{/if} text-decoration-none">📊 Statystyki</a>
        <a href="/user/directory" class="{if $page == ''account''}active{/if} text-decoration-none">🎭 Katalog członków</a>
        <a href="/user/news" class="{if $page == ''account''}active{/if} text-decoration-none">📰 Aktualności</a>
        <a href="/user/messages" class="{if $page == ''account''}active{/if} text-decoration-none">💬 Wiadomości</a>
        <a href="/user/notepad" class="{if $page == ''account''}active{/if} text-decoration-none">🗒️ Notatnik</a>
        <a href="/user/connections" class="{if $page == ''account''}active{/if} text-decoration-none">🕸️ Połączenia</a>
    </div>
</div>'
WHERE Id = 78
SQL);

        $pdo->exec(<<<'SQL'
UPDATE Languages SET pl_PL =
'<div class="alert alert-warning mt-2">
        <h5 class="alert-heading">Dostęp do administracji</h5>
        <p>
            Stąd możesz uzyskać dostęp do obszarów administracyjnych zgodnie z Twoimi uprawnieniami.
        </p>
        <p class="mb-0">
            🔐 Wyświetlane są tylko sekcje, do których masz dostęp.
        </p>
    </div>
    <div class="admin-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventManager}
            <li class="nav-item">
                <a class="nav-link" href="/eventManager">🗓️ Zarządzanie wydarzeniami</a>
            </li>
            {/if}

            {if $isDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/designer">🎨 Design</a>
            </li>
            {/if}

            {if $isRedactor}
            <li class="nav-item">
                <a class="nav-link" href="/redactor">✍️ Redakcja treści</a>
            </li>
            {/if}

            {if $isPersonManager}
            <li class="nav-item">
                <a class="nav-link" href="/personManager">📇 Zarządzanie członkami</a>
            </li>
            {/if}

            {if $isVisitorInsights}
            <li class="nav-item">
                <a class="nav-link" href="/visitorInsights">🔍 Analiza odwiedzających</a>
            </li>
            {/if}

            {if $isWebmaster}
            <li class="nav-item">
                <a class="nav-link" href="/webmaster">🛠️ Administracja stroną</a>
            </li>
            {/if}
        </ul>
    </div>'
WHERE Id = 80
SQL);

        $pdo->exec(<<<'SQL'
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Administracja projektem</h5>
        <p>
            Ten obszar umożliwia konfigurację elementów wizualnych i strukturalnych aplikacji.
        </p>
        <p class="mb-0">
            🎨 Poniżej wyświetlane są tylko narzędzia projektowe, do których masz dostęp.
        </p>
    </div>

    <div class="designer-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            {if $isEventDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/eventTypes">🗓️ Typy wydarzeń i atrybuty</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/needs">📋 Potrzeby związane z wydarzeniami</a>
            </li>
            {/if}

            {if $isHomeDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/settings">🔧 Personalizacja</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/designs">🧠 Projekty</a>
            </li>
            {/if}

            {if $isKanbanDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/kanban">🟨 Kanban</a>
            </li>
            {/if}

            {if $isNavbarDesigner}
            <li class="nav-item">
                <a class="nav-link" href="/navbar">📑 Pasy nawigacyjne</a>
            </li>
            {/if}

        </ul>
    </div>'
WHERE Id = 81
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Zarządzanie wydarzeniami</h5>
        <p>
            Ten obszar umożliwia zarządzanie wydarzeniami, harmonogramami i komunikacją z uczestnikami.
        </p>
        <p class="mb-0">
            🗓️ Skorzystaj z poniższych narzędzi, aby planować, monitorować i analizować swoje wydarzenia.
        </p>
    </div>

    <div class="event-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">

            <li class="nav-item">
                <a class="nav-link" href="/weekEvents">🗓️ Tygodniowy kalendarz</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/nextEvents">📅 Nadchodzące wydarzenia</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/guest">📩 Wyślij zaproszenie</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/emails">📧 Pobierz e-maile</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/events/crossTab">🧮 Tabela przestawna</a>
            </li>

        </ul>
    </div>'
WHERE Id = 82
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Przestrzeń redakcyjna</h5>
        <p>
            Ten obszar jest przeznaczony do pisania, zarządzania i analizowania opublikowanych treści.
        </p>
        <p class="mb-0">
            ✍️ Skorzystaj z poniższych narzędzi, aby tworzyć artykuły, zarządzać mediami i śledzić wyniki.
        </p>
    </div>

    <div class="redactor-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/articles">📰 Artykuły</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/media/list">📂 Media</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/topArticles">📈 Top 50</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/articles/crossTab">🧮 Tabela przestawna</a>
            </li>
        </ul>
    </div>'
WHERE Id = 83
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Zarządzanie członkami</h5>
        <p>
            Ten obszar umożliwia zarządzanie członkami klubu, grupami i rejestracjami.
        </p>
        <p class="mb-0">
            👥 Skorzystaj z poniższych narzędzi, aby organizować, importować i zarządzać danymi członków.
        </p>
    </div>
    <div class="person-manager-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/persons">🎭 Członkowie</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/groups">👫 Grupy</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/registration">🎟️ Rejestracje</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/import">📥 Import</a>
            </li>
        </ul>
    </div>'
WHERE Id = 84
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Analiza odwiedzających</h5>
        <p>
            Ten obszar umożliwia monitorowanie aktywności odwiedzających, analizowanie źródeł ruchu i trendów.
        </p>
        <p class="mb-0">
            👀 Skorzystaj z poniższych narzędzi, aby uzyskać dostęp do logów, najpopularniejszych stron i alertów żądanych przez członków.
        </p>
    </div>
    <div class="visitor-insights-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item">
                <a class="nav-link" href="/referents">☁️ Strony odsyłające</a>
            </li>
            <li class="nav-item" title="Najpopularniejsze strony według okresu">
                <a class="nav-link" href="/topPages">📈 Popularne strony</a>
            </li>
            <li class="nav-item" title="Tabela przestawna">
                <a class="nav-link" href="/crossTab">🧮 Tabela przestawna</a>
            </li>
            <li class="nav-item" title="Odwiedzający">
                <a class="nav-link" href="/logs">📊 Odwiedzający</a>
            </li>
            <li class="nav-item" title="Ostatnie wizyty">
                <a class="nav-link" href="/lastVisits">👁️ Ostatnie wizyty</a>
            </li>
            <li class="nav-item" title="Alerty żądane przez członków">
                <a class="nav-link" href="/membersAlerts">📩 Alerty członków</a>
            </li>
        </ul>
    </div>'
WHERE Id = 85
SQL);

        $pdo->exec(<<<'SQL'
UPDATE Languages SET pl_PL =
'<div class="alert alert-info mt-2">
        <h5 class="alert-heading">Obszar webmastera</h5>
        <p>
            Ten obszar umożliwia zarządzanie stroną internetową, bazami danych, powiadomieniami i konserwacją.
        </p>
        <p class="mb-0">
            🛠️ Skorzystaj z poniższych narzędzi, aby uzyskać dostęp do baz danych, zarządzać rejestracjami, wysyłać e-maile i przeprowadzać konserwację.
        </p>
    </div>
    <div class="webmaster-links mt-3 mb-3">
        <ul class="nav nav-pills gap-3">
            <li class="nav-item"><a class="nav-link" href="/dbbrowser">🗂️ Przeglądarka bazy danych</a></li>
            <li class="nav-item"><a class="nav-link" href="/groups">🧑‍🤝‍🧑 Grupy</a></li>
            <li class="nav-item"><a class="nav-link" href="/registration">🎟️ Rejestracje</a></li>
            <li class="nav-item"><a class="nav-link" href="/notifications">🔔 Powiadomienia</a></li>
            <li class="nav-item"><a class="nav-link" href="/sendEmails">📥 E-maile</a></li>
            <li class="nav-item"><a class="nav-link" href="/maintenance">🚧 Konserwacja</a></li>
            {if $isMyclubWebSite}
            <li class="nav-item"><a class="nav-link" href="/installations">🌐 Instalacje</a></li>
            {/if}
        </ul>
    </div>'
WHERE Id = 86
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container my-3">
    <section class="mb-3">
        <div class="card shadow-sm">
            <div class="card-body p-3 p-md-5">
                <h2 class="fw-bold text-primary">Narzędzia obszarów administracyjnych</h2>
                <p class="lead text-muted mb-4">
                    Ta strona daje dostęp do narzędzi administracyjnych zgodnie z Twoimi uprawnieniami.
                </p>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Dostęp i uprawnienia</h3>
                                <ul class="list-unstyled text-muted small">
                                    <li class="mb-2">
                                        <strong>Widoczność:</strong> Żółty klucz pojawia się na górnym pasku tylko dla członków z odpowiednimi uprawnieniami.
                                    </li>
                                    <li>
                                        <strong>Inteligentna nawigacja:</strong>
                                        <ul class="mt-2 list-unstyled ms-3">
                                            <li class="mb-1">→ Wiele obszarów → menu wyboru</li>
                                            <li>→ Jeden obszar → automatyczne przekierowanie (oszczędność czasu 😊)</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3 flex-shrink-0">
                                <i class="bi bi-phone fs-4"></i>
                            </div>
                            <div>
                                <h3 class="h5 fw-bold mb-2">Optymalizacja na urządzenia mobilne</h3>
                                <p class="text-muted small">
                                    Skróty są wyświetlane bezpośrednio tutaj — nie trzeba otwierać menu ☰ na małych ekranach.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section>
        <h2 class="h4 mb-3 fw-bold">Co warto zapamiętać</h2>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-key-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Żółty klucz</strong> jest widoczny tylko dla członków z uprawnieniami.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-arrow-right-circle fs-4 text-primary me-3"></i>
                <span>
                    Jeden obszar administracyjny → automatyczne przekierowanie.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-phone fs-4 text-success me-3"></i>
                <span>
                    Na urządzeniach mobilnych: skróty bezpośrednio na tej stronie.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-4 text-warning me-3"></i>
                <span>
                    <strong>Pomoc kontekstualna:</strong> dostępna w każdym module poprzez ikonę pomocy.
                </span>
            </div>
        </div>
    </section>
</div>'
WHERE Id = 53
SQL);

        $pdo->exec(<<<SQL
UPDATE Languages SET pl_PL =
'<div class="container my-5">
    <header class="mb-5 border-bottom pb-3">
        <h1 class="display-5 fw-bold text-primary">Pomoc kontekstualna: MyClub</h1>
        <p class="lead">Uprość zarządzanie swoim stowarzyszeniem w kilku kliknięciach.</p>
    </header>
    <section class="mb-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title h4 mb-4">Prezentacja aplikacji</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <strong>Bezpieczne logowanie</strong>
                                <p class="text-muted small">
                                    Logowanie przez e-mail.
                                    <span class="d-block mt-1 text-dark">
                                        👉 <em>Pierwszy raz? Użyj opcji „Utwórz / zresetuj hasło", aby zainicjować swoje konto.</em>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-newspaper"></i>
                            </div>
                            <div>
                                <strong>Przeglądanie artykułów</strong>
                                <p class="text-muted small">
                                    Czytaj i udostępniaj aktualności pisane przez społeczność.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <strong>Zarządzanie aktywnościami</strong>
                                <p class="text-muted small">
                                    Zapisuj się na aktywności i synchronizuj je z osobistym kalendarzem.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-sliders"></i>
                            </div>
                            <div>
                                <strong>Preferencje i filtry</strong>
                                <p class="text-muted small">
                                    Skonfiguruj ulubione typy wydarzeń i dostępność, aby uzyskać spersonalizowany widok.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <strong>Grupy i zasoby</strong>
                                <p class="text-muted small">
                                    Dołącz do konkretnych grup, aby uzyskać dostęp do ich dedykowanych zasobów.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-start">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <strong>Katalog członków</strong>
                                <p class="text-muted small">
                                    Przedstaw się innym członkom, uzupełniając swój profil.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr class="my-5">
    <section>
        <h2 class="h4 mb-4">Co warto zapamiętać</h2>
        <p class="text-muted">
            Nawigacja odbywa się głównie przez górny pasek nawigacyjny.
        </p>
        <div class="list-group">
            <div class="list-group-item d-flex align-items-center">
                <span class="badge bg-secondary me-3">[Logo]</span>
                <span>
                    W lewym górnym rogu — natychmiast wraca na <strong>stronę główną</strong>.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-list fs-3 me-3"></i>
                <span>
                    <strong>Menu hamburger:</strong> Na urządzeniach mobilnych, w prawym górnym rogu, odsłania ukryte opcje nawigacji.
                </span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">👻</span>
                <span><strong>Tryb publiczny:</strong> Nie jesteś zalogowany/a. Dostęp ograniczony wyłącznie do publicznych zasobów.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <span class="fs-3 me-3">😊</span>
                <span><strong>Tryb członka:</strong> Jesteś zalogowany/a. Pełny dostęp do zasobów swoich grup.</span>
            </div>
            <div class="list-group-item d-flex align-items-center">
                <i class="bi bi-box-arrow-right fs-3 text-warning me-3"></i>
                <span><strong>Wyloguj:</strong> Kliknij tutaj, aby bezpiecznie zakończyć sesję.</span>
            </div>
            <div class="list-group-item d-flex align-items-center bg-light">
                <i class="bi bi-question-circle-fill fs-3 text-warning me-3"></i>
                <span>
                    <strong>Pomoc:</strong> Tutaj znajdziesz wszystkie informacje potrzebne do korzystania z MyClub.
                </span>
            </div>
        </div>
    </section>
</div>
<div class="mt-4 text-center text-muted">
W stopce,
  <strong><a href="https://myclub.ovh/menu/show/article/28">Samouczki</a></strong> przekierują Cię na stronę
  <strong><i><u>MyClub</u></i></strong>.
  Znajdziesz tam <strong>filmy</strong>, <strong>artykuły</strong>,
  <strong>słownik</strong> i inne zasoby pomocnicze.
</div>'
WHERE Id = 56
SQL);

        $pdo->exec("INSERT OR IGNORE INTO Languages (Name, en_US, fr_FR, pl_PL) VALUES ('LegalNotices', '', '', '')");
        $pdo->exec("UPDATE Languages SET en_US = 'Your legal notices here'   WHERE Name = 'LegalNotices' AND TRIM(en_US) = ''");
        $pdo->exec("UPDATE Languages SET fr_FR = 'Vos mentions légales ici'  WHERE Name = 'LegalNotices' AND TRIM(fr_FR) = ''");
        $pdo->exec("UPDATE Languages SET pl_PL = 'Twoje informacje prawne tutaj' WHERE Name = 'LegalNotices' AND TRIM(pl_PL) = ''");

        return 26;
    }
}
