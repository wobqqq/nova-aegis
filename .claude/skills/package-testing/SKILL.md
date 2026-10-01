---
name: package-testing
description: "How the PHP side of Aegis is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures, tests/Support), phpunit.xml.dist, when a test needs Nova, a user, the network or the console, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the package

## The harness

- Pest 4 on Orchestra Testbench 10 (Laravel 12) with the real `laravel/nova` from nova.laravel.com. SQLite in memory, array cache and session (`phpunit.xml.dist`, `APP_URL=https://aegis.test`).
- `tests/TestCase.php` loads `Inertia\ServiceProvider`, `NovaCoreServiceProvider` and `AegisServiceProvider`, creates the `users` table for `tests/Fixtures/User.php`, registers `AegisTool` with Nova and defines `viewAegis` as `is_admin`.
- `tests/Pest.php` gives two global helpers: `admin()` (allowed by the gate) and `editor()` (refused).
- `Feature/` exercises the package through the container, HTTP, the console and Nova; `Unit/ArchitectureTest.php` holds the `arch()` rules: strict types, no debugging calls, immutable value objects, enums for shared codes, network connections only in `Scanners`.

## Rules

- Test what an administrator or the application sees: the config Laravel ends up with, the JSON the API answers, the status a check reports, the exit code of a command. Not private methods.
- A security rule is a test: a refused user, an unlisted scan target, an invalid setting, a check that throws.
- No test reaches the network:
  - HTTP goes through a Guzzle `MockHandler` given to `HttpProbe`;
  - TCP through local sockets (`tests/Support/Sockets.php`);
  - TLS and the scanner endpoints through probes replaced in the container (`app()->instance(TlsProbe::class, ...)`);
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
3. `make ready` before the commit.
