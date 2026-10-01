# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

Breaking for code that extends the core: every class is now final, and an application that called `new HttpProbe()` (or the TCP / TLS probe) resolves the interface from the container instead. Modules and the settings stored in `aegis_settings` are unaffected.

### Added

- Laravel 13 support; CI runs the suite on Laravel 12 and 13, and on PHP 8.5.

### Changed

- PHP 8.4 or later is required.
- Stricter typing throughout: typed constants, `#[\Override]`, final classes, value objects for audit advisories and TLS certificates, and PHPStan on bleeding edge with the strict and shipmonk rules. No behaviour changes.
- The scanner probes are interfaces (`HttpProbe`, `TcpProbe`, `TlsProbe`) with final implementations under `Scanners\Probes`; bind your own implementation of the interface to replace one.

## [1.1.1] - 2026-10-01

### Changed

- Development and CI run on a test double of Nova (`stubs/nova`, not shipped) and need no Nova license; `make test.nova` runs the PHP suite on the real Nova. Nothing changes for applications.
- The README splits the installation into numbered steps.
- The Dependabot config no longer reads the Nova registry.

## [1.1.0] - 2026-10-01

### Added

- `Aegis::save()` for modules that change their settings outside the Aegis tool, such as a recovery command; it validates the values with the module's rules and dispatches `SettingsSaved`.
- `Wobqqq\Aegis\Support\Values` is part of the public API.

## [1.0.0] - 2026-10-01

### Added

- The Aegis tool: an overview of the security checks, the dependency audit, the settings and the scanners.
- A dashboard card with the failing checks and warnings.
- Hardening: session cookies, the password policy, forced HTTPS and HSTS, all off until enabled.
- Checks: debug mode, environment, application key, HTTPS URL, Nova path, session cookie, password policy, stale administrators, dependency advisories.
- `composer audit` from the dashboard, the console (`aegis:audit`) and a daily schedule.
- Scanners: sensitive files over HTTP, open TCP ports and TLS certificates, limited to the listed targets.
- The module API (`Aegis::module()`, `Aegis::check()`, `Aegis::settings()`, `SettingsSaved`) for add-on packages.
- `aegis:check` and `aegis:disable` console commands.

[Unreleased]: https://github.com/wobqqq/nova-aegis/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/wobqqq/nova-aegis/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/wobqqq/nova-aegis/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/wobqqq/nova-aegis/releases/tag/v1.0.0
