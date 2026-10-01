---
name: package-testing
description: "How the PHP side of Aegis is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures, tests/Support), phpunit.xml.dist or stubs/nova (the Nova test double), when code or a test uses a Nova class or method not used before, when a test needs Nova, a user, the network or the console, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the package

## The harness

- Pest 5 on Orchestra Testbench 11 (Laravel 13; CI also on Laravel 12 and PHP 8.5) with `laravel/nova` resolved to the test double in `stubs/nova` (see below). SQLite in memory, array cache and session (`phpunit.xml.dist`, `APP_URL=https://aegis.test`).
- `tests/TestCase.php` loads `Inertia\ServiceProvider`, `NovaCoreServiceProvider` and `AegisServiceProvider`, creates the `users` table for `tests/Fixtures/User.php`, registers `AegisTool` with Nova and defines `viewAegis` as `is_admin`.
- `tests/Pest.php` gives two global helpers: `admin()` (allowed by the gate) and `editor()` (refused).
- `Feature/` exercises the package through the container, HTTP, the console and Nova; `Unit/ArchitectureTest.php` holds the `arch()` rules: strict types, no debugging calls, immutable value objects, enums for shared codes, network connections only in `Scanners`.

## The Nova test double (`stubs/nova`)

- `composer.json` declares `stubs/nova` as the `nova` path repository (`"versions": {"laravel/nova": "5.99.0"}`, `"symlink": true`), so `vendor/laravel/nova` links to it. No license, no `auth.json`, the same in CI. It is export-ignored; applications install the real Nova.
- It is our own minimal code with Nova's class names, public signatures and the behaviour the Aegis packages rely on. Never copy Nova's code or comments into it.
- It provides: `NovaCoreServiceProvider` (auto-discovered; merges `config/nova.php`, aliases `nova.auth` and `nova.guest`, the groups `nova`, `nova:api`, `nova:auth`, `nova:serving`, `nova:asset` built from `nova.middleware`, `nova.api_middleware`, `nova.asset_middleware`, `ServeNova` on the kernel, the `nova` views, the `nova-api` asset routes and a catch-all `nova-api/{path}` behind `nova:api` that answers 404 once authenticated and authorized), `NovaServiceProvider`, `Nova` (`path`, `url`, `router`, `serving`, `booted`, `tools`, `registeredTools`, `availableTools`, `bootTools`, `script`, `style`, `allScripts`, `allStyles`, `provideToScript`, `jsonVariables`, `user`, `auth`, `check`, `flushState`, `version`, `name`), `Util` (`isNovaRequest`, `userGuard`), `Tool`, `Element`, `Card`, `Menu\MenuSection`, `URL`, `Script`, `Style`, the events `ServingNova` and `NovaServiceProviderRegistered`, `Exceptions\AuthenticationException` (401 for JSON and `nova-api`/`nova-vendor`, a redirect to `Nova::url('login')` otherwise) and the middleware `Authenticate`, `Authorize` (`Nova::check()`, false outside `local` until `Nova::auth()` is set), `BootTools`, `DispatchServingNovaEvent`, `HandleInertiaRequests`, `RedirectIfAuthenticated`, `ServeNova`.
- It leaves out resources, fields, actions, lenses, dashboards, Fortify and the login pages, and does not prepend Nova's reverse-proxy guard to the groups.
- **A package uses a Nova API the double lacks:** read the real signature in a Nova install, add the class or method to `stubs/nova` with the same name, parameters, native types and PHPDoc (never narrower, or code that runs on Nova fails PHPStan or breaks at runtime), implement only the behaviour the package relies on, then run `make ready` and, with a license, `make test.nova`. Copy the change to the modules' `stubs/nova`.
- A test is about the package, not Nova: when a test only passes on one of them, rewrite it against the behaviour documented here instead of deleting the coverage.
- `make test.nova` runs the suite on the real Nova in a throwaway copy (`docker/test-nova.sh`): it needs `auth.json` with your license; `NOVA_VERSION=5.9.3 make test.nova` pins a release.

## Rules

- Test what an administrator or the application sees: the config Laravel ends up with, the JSON the API answers, the status a check reports, the exit code of a command. Not private methods.
- A security rule is a test: a refused user, an unlisted scan target, an invalid setting, a check that throws.
- No test reaches the network:
  - HTTP goes through a Guzzle `MockHandler` given to `GuzzleHttpProbe`;
  - TCP through local sockets (`tests/Support/Sockets.php`);
  - TLS and the scanner endpoints through an anonymous implementation of the probe interface bound in the container (`app()->instance(TlsProbe::class, new class () implements TlsProbe { ... })`);
  - `composer audit` through `Process::fake()`.
- A setting is saved through `SettingsRepository::save()` (or the API), never written to the table by hand, unless the test is about a stored row the rules would refuse.
- Coverage stays at 90 % or more (`make test.coverage`).

## PHPStan max on tests, without ignores

- Use the global `Pest\Laravel\*` functions (`getJson`, `putJson`, `actingAs`), never `$this->` in a closure.
- Console: `expect(Artisan::call('aegis:check'))->toBe(1)` and `Artisan::output()`, not `artisan()->assertExitCode()`.
- Annotate mocks (`/** @var CacheRepository&MockInterface $cache */`) and type closure parameters (`fn (PendingProcess $process)`).
- Read `mixed` JSON with `data_get()` or narrow it with `is_array()` before indexing.
- `Event::fake()` does not reach a dispatcher already injected into a singleton: capture with `Event::listen()` instead.

## Workflow

1. Write the change and its tests; iterate with `docker compose run --rm php vendor/bin/pest --filter='...'`.
2. `make composer.test.coverage` for gaps; cover the uncovered decisions, not getters.
3. `make ready` before the commit; `make test.nova` too when the change touches Nova and you have a license.
