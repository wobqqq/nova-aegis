# Aegis

[![CI](https://github.com/wobqqq/nova-aegis/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/nova-aegis/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/wobqqq/nova-aegis)](https://packagist.org/packages/wobqqq/nova-aegis)
[![Downloads](https://img.shields.io/packagist/dt/wobqqq/nova-aegis)](https://packagist.org/packages/wobqqq/nova-aegis)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://github.com/wobqqq/nova-aegis/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/nova-aegis/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/nova-aegis/blob/main/LICENSE.md)

**Aegis** is a security suite for Laravel Nova. It hardens the application's session and password policy, checks the configuration a production site gets wrong most often, audits the installed packages for advisories and scans the site from the outside for exposed files, open ports and expiring certificates, all from one Nova page and a dashboard card.

Add-on modules extend it with more protection layers through a small, stable API.

## 🚀 Features

### 📊 Dashboard card

`AegisCard` counts the failing checks, the warnings and the passing ones, and lists the problems with a link to the Aegis page.

### 🔍 Security checks

- **Debug mode** is off.
- **Environment** is `production`.
- **Application key** is set.
- **`APP_URL`** uses HTTPS.
- **Nova path** is not a guessable one (`/nova`, `/admin`, …).
- **Session cookie** is secure and HTTP-only.
- **Password policy**: the default rule asks for at least 12 characters.
- **Stale administrators**: accounts nobody signed in with for 90 days (when the user model records the last sign-in).
- **Dependency advisories** from `composer audit`, and a warning when the last audit is older than a week.

The same checks run from the console with `php artisan aegis:check`, whose exit code fails a deployment pipeline on a failing check (`--strict` fails on warnings too).

### ⚙️ Hardening

Everything is off until **Enable hardening** is switched on in **Aegis → Settings**.

- Session cookies: secure, HTTP-only, SameSite (`lax` / `strict`), lifetime, encryption.
- Password policy for `Password::defaults()`: minimum length, mixed case, letters, numbers, symbols, and the "not in a known breach" check.
- Force HTTPS: generated URLs use `https`, plain HTTP requests are redirected with a 301.
- HSTS: `Strict-Transport-Security` with its max-age and `includeSubDomains`.

### 🛡️ Scanners

- **Sensitive files**: requests `/.env`, `/.git/config`, `/composer.lock`, … on the listed URLs and reports those that answer 200.
- **TCP ports**: dials SSH, FTP, MySQL, PostgreSQL, Redis, … on the listed hosts.
- **TLS certificates**: reads the certificate of each listed host and port, and warns 14 days before it expires.

A scanner only reaches the targets listed in its settings, never follows a redirect and sends no credentials.

### 🧩 Modules

Add-on packages register their own settings section, dashboard line and checks:

- Admin IP Access
- IP Blocker
- Smart IP Blocker
- CSP
- Input Sanitizer

## 📦 Requirements

- PHP 8.2 or higher
- Laravel 12 or 13
- Laravel Nova 5

## 📥 Installation

### 1. Install the package

```bash
composer require wobqqq/nova-aegis
```

The service provider is discovered automatically.

### 2. Run the migrations

```bash
php artisan migrate
```

This creates the `aegis_settings` table every Aegis module keeps its settings in.

### 3. Register the tool

In `app/Providers/NovaServiceProvider.php`:

```php
use Wobqqq\Aegis\Nova\AegisTool;

public function tools(): array
{
    return [
        AegisTool::make(),
    ];
}
```

### 4. Add the dashboard card (optional)

In `app/Nova/Dashboards/Main.php`:

```php
use Wobqqq\Aegis\Nova\AegisCard;

public function cards(): array
{
    return [
        AegisCard::make(),
    ];
}
```

### 5. Say who may open Aegis

Nobody can until the application defines the `viewAegis` gate, for instance in `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewAegis', fn ($user) => $user->is_admin);
```

### 6. Keep the scheduler running

`composer audit` runs daily through Laravel's scheduler, so `php artisan schedule:run` must run every minute. Set `AEGIS_AUDIT_SCHEDULE=false` to run `php artisan aegis:audit` yourself instead.

### 7. Change the defaults (optional)

```bash
php artisan vendor:publish --tag=aegis-config
```

| Setting | Env | Default |
|---|---|---|
| Cache store | `AEGIS_CACHE_STORE` | the default store |
| User model | `AEGIS_USER_MODEL` | `App\Models\User` |
| Last sign-in column | `AEGIS_USER_LAST_LOGIN_COLUMN` | none (the check reports it is not configured) |
| Days before an account is stale | `AEGIS_USER_STALE_AFTER_DAYS` | `90` |
| Daily audit | `AEGIS_AUDIT_SCHEDULE` | `true` |
| Composer binary | `AEGIS_COMPOSER_BINARY` | `composer` |

## 💻 Usage

Open **Aegis** in the Nova menu. **Overview** shows the checks, the modules and the last dependency audit; **Settings** holds the hardening, the scanners' targets and every module's section; **Scanners** runs a scan of one target at a time.

Console commands:

```bash
php artisan aegis:check [--strict]   # run the checks
php artisan aegis:audit              # run composer audit now
php artisan aegis:disable            # turn the hardening off, for an administrator it locked out
```

## 🧱 Writing a module

A module is a Laravel package that implements `Wobqqq\Aegis\Contracts\Module` and registers it from its service provider:

```php
use Wobqqq\Aegis\Aegis;

public function boot(): void
{
    Aegis::module(new CspModule());
    Aegis::check(new CspHeaderCheck());
}
```

- `key()` names its settings section (`csp`), `defaults()` and `rules()` define and validate its values, `fields()` draws its form (`Field::toggle()`, `number()`, `text()`, `textarea()`, `select()`, `table()`), and `status()` adds its line to the dashboard.
- `Aegis::settings('csp')` reads the saved values merged over the defaults, cached.
- `Aegis::save('csp', $values)` validates the values with the module's rules, stores them and dispatches `SettingsSaved` (for a recovery command, for instance).
- `Wobqqq\Aegis\Events\SettingsSaved` is dispatched with the section and its values after each save.

## ⬆️ Upgrading

See [CHANGELOG.md](https://github.com/wobqqq/nova-aegis/blob/main/CHANGELOG.md). Run `php artisan migrate` after each update.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/nova-aegis/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. No Nova license is needed: development and CI run on a test double of Nova in `stubs/nova` (installed as `laravel/nova` from a path repository, never shipped). Applications still install the real Nova.

```bash
make install        # composer install, npm ci
make code.fix       # composer normalize, Rector, PHP CS Fixer, ESLint, Prettier
make code.check     # composer validate/audit, php -l, PHP CS Fixer, Rector, PHPStan (level max), ESLint, Prettier, npm audit
make test.coverage  # Pest and Vitest with coverage (90 % minimum)
make npm.build      # build dist/ (committed)
make ready          # everything above
make test.nova      # optional: the PHP suite on the real Nova
```

`make test.nova` copies the repository to a temporary directory, installs the real `laravel/nova` from nova.laravel.com there and runs Pest; it needs your own Nova license in `auth.json` (gitignored), and `NOVA_VERSION=5.9.3 make test.nova` picks a release your license may download. The working copy, its `vendor/` and `composer.lock` are left untouched.
