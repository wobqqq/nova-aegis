---
name: package-boundaries
description: "Extra rules for reusable Laravel/Nova packages (Composer libraries other applications install), on top of the architecture skills. Use when changing a package's public classes, contracts, events, config keys, routes, migrations, service provider bindings or extension points, when a package needs something from the host application (user model, gate, cache store, queue), or when deciding what is public API versus internal."
license: MIT
---

# Reusable packages

A package runs inside applications you do not control, upgraded independently of them and of its sibling packages. Every architecture rule still applies; these are the additional ones.

## 1. Public API is a promise

- Decide what is public: contracts (interfaces), value objects and DTOs exposed to callers, events and their payloads, facade/entry classes, config keys, route names, view names, translation keys, database tables and stored formats. Everything else is marked `@internal` and may change.
- Keep public signatures stable within a major version. Add; do not rename or remove. A breaking change is a major release with an upgrade note.
- Prefer interfaces for extension points the host may replace (probes, clients, strategies) and bind defaults in the service provider with `bind`/`singleton` so the host can rebind them.
- Classes are `final`; extension happens through interfaces, events and configuration, never through inheritance of package internals.

## 2. No assumptions about the host

- Never hard-code `App\Models\User`, a guard name, a cache store, a queue, a disk or a route prefix. Read them from the package config with sensible defaults, or from the framework (`config('auth.providers.users.model')`).
- Authorization through a gate or policy the host defines (deny by default), not through a role column the package invents.
- Do not depend on the host's helpers, middleware groups or base classes beyond what Laravel/Nova guarantee.
- Translations, views, config and migrations are publishable and namespaced (`aegis::`, `aegis.*`).

## 3. Configuration over hard-coding

- Every environment-specific value is a config key with an env override and a documented default; values are read in the service provider and passed typed into classes (see `dependency-injection`).
- New protections or behaviours ship **off** (or in a safe mode) so an upgrade never changes a site's behaviour unasked.

## 4. Data that already exists

- Released migrations are never edited; schema changes are new migrations with a working `down()`.
- Stored formats (settings JSON, cache payloads) are read tolerantly: unknown keys ignored, missing keys defaulted. Changing a cached shape bumps its cache-key version.

## 5. Dependencies

- Require whole majors (`^12.0 || ^13.0`) and test the lowest and highest supported versions in CI.
- Keep runtime dependencies minimal; anything only needed for development stays in `require-dev`.

## Checklist

- [ ] Public vs `@internal` is explicit; no breaking change without a major.
- [ ] Host specifics come from config or the framework, never hard-coded.
- [ ] Defaults are safe; new behaviour is opt-in.
- [ ] Migrations are append-only; stored data is read tolerantly.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
