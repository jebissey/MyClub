## Quick context

This repository is a lightweight PHP CMS (MyClub) built with FlightPHP and Latte. The web app lives under `WebSite/` (PSR-4 autoload `app\` => `app/`). The runtime database is SQLite at `WebSite/data/MyClub.sqlite`.

Keep these facts in mind when making changes:
- Entry point: `WebSite/index.php` — initializes `app\helpers\Application`, Flight engine, Latte and session handling.
- Routes are assembled in `WebSite/app/config/Routes.php` by merging many route files from `WebSite/app/config/routes/*.php`.
- Controllers are created via `ControllerFactory` (`WebSite/app/config/ControllerFactory.php`) — prefer using factory wiring when adding or invoking controllers.

## Architecture highlights (why/how)
- Modular controllers under `WebSite/app/modules/*`. Templates (Latte) live next to modules and use filters defined in `Application::setupLatteFilters()`.
- Data access is via DataHelper classes in `WebSite/app/models/*` and services under `WebSite/app/services/*`.
- Routes describe `methodAndPath` strings using `@param` tokens (e.g. `@id:[0-9]+`). `Routes::mapRoute()` converts `[0-9]+` params to integers. Respect this pattern when adding routes or parsing params.
- Error handling: `ErrorManager` is used widely and Flight's `error` hook is wired in `index.php`.

## Developer workflows & commands
- Install PHP deps for the web app: run composer inside `WebSite/` (not repo root):
  - `cd WebSite && composer install`
- Run locally with PHP built-in server (serves `WebSite/index.php`):
  - `php -S localhost:8000 -t WebSite WebSite/index.php`
- Route integration tests (custom runner):
  - The test runner is `test/FlightRouteTester.php`. It requires a test DB (default `test/Database/tests.sqlite`) and will backup/restore the live website DB before/after runs.
  - Example: `php test/FlightRouteTester.php --base-url=http://localhost:8000 --db-path=test/Database/tests.sqlite --export-json --stop`
  - Important: the runner calls `CurrentWebSite::backup/remove/restore` on `WebSite/data/MyClub.sqlite`. Ensure you have a clean copy and backups before running.
- Packaging full install: see `dev/CreateFullInstall.sh` — used to build `dev/FullInstall.zip` containing the `WebSite` root, `app/` and `vendor/`.

## Testing database expectations
- The route tester expects the tests DB rows to include these columns when a route has parameters or POST payloads: `JsonGetParameters`, `JsonPostParameters`, `JsonConnectedUser`, `ExpectedResponseCode`, `Query`, `QueryExpectedResponse`.
- For simulations (multi-step flows) the tests DB drives requests and can simulate authentication via `JsonConnectedUser`.

## Conventions & patterns to follow
- Use `ControllerFactory` to construct controllers inside route definitions and tests — this keeps dependency wiring consistent.
- Add new routes under `WebSite/app/config/routes/` as files returning an array of route objects (see existing files for the pattern). `Routes.php` merges them.
- Templates: place visual templates under `WebSite/app/modules/<Module>/` and rely on Latte filters already registered in `Application`.
- DB usage: SQLite only — use `app/models/Database.php` helper (existing code uses singletons). Tests assume DB file paths rather than in-memory DBs.

## Integration points & external deps
- Runtime: FlightPHP, Latte, Guzzle, Tracy — see `WebSite/composer.json`.
- Email and background behaviors are handled by `app/services/*` (e.g. EmailService). If you modify these, search for usages in `app/config/Routes.php` and controllers.
- Media/images served by `Routes::serveFile()` (e.g. `/favicon.ico`, `/webCard`). Use `app/images/` as source.

## Files worth inspecting for context (examples)
- `WebSite/index.php` — app bootstrap, session, maintenance check.
- `WebSite/app/config/Routes.php` — route assembly and param handling.
- `WebSite/app/config/ControllerFactory.php` — dependency wiring for controllers.
- `WebSite/app/helpers/Application.php` — Latte setup, PDO acquisition and global singletons.
- `test/FlightRouteTester.php` and `test/Core/TestExecutor.php` — how automated route tests run and validate responses.

If anything above is ambiguous or you want the instructions tuned for a particular agent behavior (e.g., more detail on Latte templates, or how to add routes), tell me which area to expand and I will iterate.
