---
name: package-upgrades
description: "How a change reaches the Laravel applications that already run Aegis. Use before changing a migration, a stored setting (its key, type or meaning), a default value, a cache key or what is cached, the module contract (Aegis, Contracts, CheckResult, Field, SettingsSaved, section keys, routes, the gate), composer.json constraints, dist/, or when preparing a release or a tag."
license: MIT
---

# Upgrading installed applications

Applications update the core and each module independently with Composer. Every change is written for an application that has been running the previous version for months.

## Versions and releases

- Semantic versions: a fix is a patch, a new option or check a minor, a removed option or a changed contract a major.
- Every change adds a line under *Unreleased* in `CHANGELOG.md` saying what changes for the developer. A release moves them under the version and date.
- Release: merge the pull request, then `git tag -a v1.0.1 -m "..." && git push origin v1.0.1`. Packagist reads the tag.
- `dist/` is what applications run: it is rebuilt and committed with every frontend change, and checked before tagging.

## Constraints

- `laravel/nova` stays `^5.0` and `laravel/framework` `^12.0`: whole majors. Supporting a new major is a minor release with both ranges (`^5.0 || ^6.0`) and tests against both.
- The lock file is for development only (export-ignored); the ranges are what applications resolve.

## Migrations

- A released migration is never edited. A schema change is a new migration with a working `down()`.
- `loadMigrationsFrom()` runs them with the application's `php artisan migrate`; the upgrade note says to run it.

## Stored settings

Each section is one `aegis_settings` row (`section`, JSON `values`).

- `SettingsRepository::section()` merges the stored values over `defaults()` and drops keys the defaults no longer name, so adding a setting needs no migration.
- Changing a setting's type or meaning: prefer a **new key** to converting an ambiguous value. Convert in a migration only what can be converted without guessing, through `DB`, skipping rows with invalid JSON.
- The reading side still accepts the old shape (`Values`, `fromArray()`): an application can run the new code before it migrates.
- Never rename a section key a module or the core reads (`hardening`, `scanners`, each module's own).

## Cached values

- The settings cache stores arrays under `aegis.settings.v{CACHE_VERSION}`; the audit under `aegis.audit.v1`. A change to the shape of a cached value bumps its version.
- The readers already treat an unreadable entry as a miss; keep reading through them.

## The modules' contract

An application may run a new core with old modules or new modules with an old core.

- Only add to the contract listed in AGENTS.md. Adding a method to `Module` or `Check` breaks every module: add a new, optional interface instead, and check for it with `instanceof`.
- A module that needs a newer core API checks for it (`method_exists`, `class_exists`) and falls back.
- Run the modules' test suites against the changed core before releasing it.

## Defaults

- A new protection ships disabled, or with a default that cannot block the current administrator.
- Changing a default changes the behaviour of every application that never saved the section: say so in the changelog, or keep the old default.
