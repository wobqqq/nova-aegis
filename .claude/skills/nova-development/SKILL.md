---
name: nova-development
description: >-
  Use whenever you create or edit the Nova side of Aegis: src/Nova (AegisTool,
  AegisCard), routes/api.php and routes/inertia.php, src/Http (controllers,
  Authorize, TransportSecurity), the Inertia page and components in
  resources/js, resources/css/tool.css, vite.config.js, dist/, the menu entry,
  the viewAegis gate, or the way the service provider wires Nova. Use it with
  aegis-security for anything about access, and nova-component-testing for the
  specs.
metadata:
  author: project
---

# Nova development (this package)

Aegis is a Nova 5 tool with an Inertia page, plus a dashboard card. `vendor/laravel/nova` here is the test double in `stubs/nova`, not Nova: check a version-specific API in a real Nova install, and add any Nova class or method you start using to `stubs/nova` with its real signature (see `package-testing`).

## The pieces

- `AegisTool` — `Laravel\Nova\Tool`. Its constructor sets `canSee()` from the `viewAegis` gate; `boot()` registers `dist/js/tool.js` and `dist/css/tool.css` with `Nova::script()` / `Nova::style()`; `menu()` is one `MenuSection` to `/aegis` with the `shield-check` icon.
- `AegisCard` — `Laravel\Nova\Card`, component `aegis-card`, width 1/2, `toolPath` from `Nova::url('/aegis')` in its meta.
- `routes/inertia.php` — registered with `Nova::router(['nova', 'nova.auth', Authorize::class], 'aegis')`, renders the page `Aegis`.
- `routes/api.php` — under `nova-vendor/aegis` with `['nova', 'nova.auth', Authorize::class]`; route names `nova.aegis.*`.
- The routes are registered in `$this->app->booted()`, so Nova's own routes and middleware exist first.

## Controllers are thin

- `OverviewController` asks `CheckRunner` and `AuditStore`; `SettingsController` asks `ModuleRegistry` and `SettingsRepository`; `ScanController` asks `Scanner`.
- No business logic in a controller or a Nova class: it belongs in a service the tests can call directly.
- A new endpoint goes into the same group in `routes/api.php`, answers JSON, validates its input and is covered for the allowed user, the refused user (403) and the unregistered tool (404) in `ToolApiTest`.

## The frontend

- Vue 3 SFCs built by Vite as a UMD library into `dist/` with `vue` and `laravel-nova` as externals (`vite.config.js`). `resources/js/tool.js` calls `Nova.inertia('Aegis', Tool)` and registers `AegisCard` (Nova resolves the card's `aegis-card`) in `Nova.booting`.
- HTTP only through `resources/js/api.js` (`Nova.request()`), feedback through `Nova.success()` / `Nova.error()`.
- Use Nova's global components (`Head`, `Heading`, `Card`) and its CSS variables; the package's own classes are prefixed `aegis-`.
- The settings form is generated from `Field::jsonSerialize()`: a new field type is a `FieldType` case, a `Field` factory and a branch in `FieldInput.vue`, together.
- After a change under `resources/`, run `make npm.build` and commit `dist/` with it. A UI change that does not show is almost always an unbuilt asset.

## Checklist

- [ ] Access: `canSee` and `Authorize` both use `viewAegis`; nothing is reachable without it.
- [ ] No business logic in Nova classes or controllers.
- [ ] New strings in `resources/lang/en/aegis.php`.
- [ ] `dist/` rebuilt; `make ready` passes.
