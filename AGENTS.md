# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Junie, Cursor) working in this repository.

## What this is

**Aegis** (`wobqqq/nova-aegis`) is the core of a security suite for Laravel Nova (Laravel 12, PHP 8.2+). It:

- hardens the application at boot (`HardeningService::apply()`: session cookies, `Password::defaults()`, forced HTTPS; the `TransportSecurity` middleware for the HTTPS redirect and HSTS) from the **Aegis → Settings** tab;
- runs the security checks (`Checks/Core`), `composer audit` (`Audit/`) and three scanners (`Scanners/`: sensitive files over HTTP, open TCP ports, TLS certificates);
- draws them in a Nova tool (`AegisTool`, the Inertia page `Aegis`) and a dashboard card (`AegisCard`);
- is the extension point of five add-on modules, each its own package: Admin IP Access, IP Blocker, Smart IP Blocker, CSP, Input Sanitizer.

This is a **security product installed on production applications**. A bug here locks administrators out, leaks data or silently leaves an application unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP or Node.

```bash
make install        # composer install + npm ci
make code.fix       # composer normalize, Rector, PHP CS Fixer, ESLint, Prettier
make code.check     # validate, normalize --dry-run, composer audit, php -l, cs, Rector, PHPStan max, ESLint, Prettier, npm audit
make test           # Pest + Vitest
make test.coverage  # both with coverage, failing below 90 %
make npm.build      # dist/js/tool.js and dist/css/tool.css
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories from `composer audit` or `npm audit` are fixed by updating the package, never ignored. `dist/` is committed: rebuild it in the same commit as any change under `resources/js` or `resources/css`.

Installing Nova needs a license: `auth.json` (gitignored and export-ignored) holds the credentials. Never read, print or commit it.

## How the code is laid out

| Path | Holds |
|------|-------|
| `src/AegisServiceProvider.php` | Wiring only: config, singletons, the core modules and checks, the middleware, routes, commands, the schedule, the hardening at boot. |
| `src/Aegis.php` | The static entry point the modules use. |
| `src/Contracts/` | `Module` and `Check`, the public interfaces. |
| `src/Settings/` | `AegisSetting` (one row per section, JSON values), `SettingsRepository` (validate, merge defaults, cache), `Field` (the form schema the page draws). |
| `src/Modules/ModuleRegistry.php` | The registered sections, keyed by `Module::key()`. |
| `src/Checks/` | `CheckResult`, the registry, the runner (isolates a failing check), the core checks. |
| `src/Hardening/` | The `hardening` section, its typed settings and the service that applies them. |
| `src/Scanners/` | The `scanners` section, `Scanner` (only listed targets) and the probes, the only code that opens network connections. |
| `src/Audit/` | `composer audit` through `Process`, its result cached forever. |
| `src/Http/` | The tool's API controllers and middleware (`Authorize`, `TransportSecurity`). |
| `src/Nova/` | `AegisTool` and `AegisCard`. |
| `src/Support/Values.php` | Typed reads of untrusted stored values. |
| `resources/js/`, `resources/css/` | The Vue 3 page, card and components, built by Vite into `dist/`. |
| `resources/lang/en/aegis.php` | Every label and message, under `aegis::aegis.*`. |

### The contract with the modules (do not break it)

The modules are separate packages that applications update independently, so an application may run a new core with old modules or the other way round. These are **public API**; renaming or changing their shape breaks installed applications:

- `Wobqqq\Aegis\Aegis::module()`, `::check()`, `::settings()`, `::save()`;
- `Wobqqq\Aegis\Support\Values` (reading a typed value from a section with a safe fallback);
- `Wobqqq\Aegis\Contracts\Module` and `Check` (adding a method to an interface is a breaking change);
- `Wobqqq\Aegis\Checks\CheckResult` and its factories, `Wobqqq\Aegis\Enums\Status`;
- `Wobqqq\Aegis\Settings\Field` and its factories, `Wobqqq\Aegis\Enums\FieldType`;
- `Wobqqq\Aegis\Events\SettingsSaved` and its properties;
- the `aegis_settings` table and the section keys `hardening` and `scanners`;
- the `viewAegis` gate, the `nova-vendor/aegis` routes, the `aegis-card` component name.

Add to these; do not rename or remove. A module must keep working with every released core version of the same major.

## Upgrading installed applications safely

Read the `package-upgrades` skill before changing anything that reaches an application that already runs the package. In short:

- A change to the schema is a **new** migration; a released migration is never edited.
- A change to what is **stored** (a setting's key, type or meaning) keeps reading the old shape (`SettingsRepository` merges defaults and drops unknown keys; `Values` falls back on a bad type) or converts the rows in a migration.
- A change to what is **cached** bumps the key's version (`SettingsRepository::CACHE_VERSION`, `aegis.audit.v1`).
- Defaults stay safe: a new protection ships disabled or with a value that cannot lock an administrator out.
- Every change is a line under *Unreleased* in `CHANGELOG.md`.

## Security rules (always)

Read the `aegis-security` skill for the full checklist. The non-negotiables:

- **Escape every output.** Vue templates use `{{ }}`, never `v-html` (ESLint fails on it); exception messages and stored values are text.
- **Authorize every entry point.** Each API route passes `Authorize` (the tool registered and `viewAegis` allowed); a new route goes in `routes/api.php` under the same group.
- **Validate every setting** in the module's `rules()` and again where it is used (`Values`, `HardeningSettings::fromArray()`).
- **The scanners only reach what the administrator listed**, with timeouts, without following redirects, and never with credentials.
- **The application keeps working when Aegis breaks**: a failing check, a missing table or an unreadable cache never turns a request into a 500.
- Never log or print secrets, `.env`, `auth.json` or a request's cookies.

## Tests

Pest 4 on Orchestra Testbench 10 with the real `laravel/nova` (SQLite in memory), Vitest 4 with happy-dom for the Vue components. No test reaches the network. Read the `package-testing` skill (PHP) and `nova-component-testing` (JS).

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for applications that upgrade);
  4. merge once `make ready` passed, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (`git tag -a v1.0.0 -m "..." && git push origin v1.0.0`); Packagist reads the tag.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via PHP CS Fixer.
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- Value objects are `final readonly`; services are `final` unless a test double has to extend them (the probes).
- Laravel and Nova patterns: container bindings, `Process`, `Http`/Guzzle, `Cache`, Nova's `Tool`/`Card`, `Nova.request()` on the page.
- Commits: imperative subject saying what the change does for the application ("Redirect plain HTTP when HTTPS is forced"), a body with the why.
