---
name: aegis-security
description: "Security checklist for the Aegis suite. Use for any change to what an outside value can do or who may reach something: an API route or controller in src/Http, the Authorize middleware or the viewAegis gate, a Vue component that prints a value, a setting and its validation rules, a probe or the Scanner (network connections), the hardening applied at boot (session, password policy, HTTPS, HSTS), the composer audit process, a console command, caching of settings, or a security review of this package."
license: MIT
---

# Aegis security checklist

Aegis runs on production applications and decides how they are protected. Treat every rule below as a test to write, not a guideline to remember.

## 1. Output: escape everything

- Vue templates print with `{{ }}`. `v-html` is an ESLint error; keep it that way.
- A stored value, a scan target, an exception or an advisory title is text, never markup: assert in a spec that `<script>` comes out escaped (`StatusList.spec.js`).
- A link built from data uses a fixed scheme and host (`Nova.url()` / `toolPath`), never a URL taken from a setting; `target="_blank"` carries `rel="noopener noreferrer"`.
- The API answers JSON only; a controller never returns a stored value as HTML.

## 2. Access: authorize every entry point

- Every route in `routes/api.php` sits in the group with `nova`, `nova.auth` and `Authorize`. `Authorize` answers 404 when the tool is not registered and 403 when `viewAegis` refuses.
- `viewAegis` refuses everyone unless the application defines it (`AegisTool::GATE`). Never ship a default that allows.
- A controller dispatches a fixed list (the section is looked up in `ModuleRegistry`, the scan kind is a route), never a class or method named by the request.
- Expensive actions are throttled (`throttle:6,1` for the audit, `20,1` for scans).
- Console commands are the recovery path: `aegis:disable` turns the hardening off. A new protection that can lock someone out ships its own way back.

## 3. Input: validate settings twice

- `Module::rules()` bounds every value: `boolean`, `integer|min|max`, `in:`, `ip`, `url:http,https`, `max:` lengths, `array|max:` counts, a strict `regex` for lists and paths (no `..`).
- `SettingsRepository::save()` validates against the rules, merges the defaults and drops keys the defaults do not name.
- Where a value is used it is read again through `Values` or a `fromArray()` with safe fallbacks: the stored row may predate the rules.
- A column or table name from config is checked against `^\w+$` before it reaches a query.

## 4. Scanners: no SSRF, no hangs

- `Scanner` only scans a target listed in the `scanners` section; anything else is `null` and the API answers 422.
- Every connection has a connect and a total timeout (`config/aegis.php`); TCP dials all ports at once and waits one timeout in total.
- HTTP requests set `allow_redirects => false` per request, send no cookies or credentials, and the response body is never stored or returned.
- IPv6 literals are bracketed (`tcp://[::1]:22`).
- Only the probes open sockets. A new network call goes into a probe bound in the container, so tests replace it.

## 5. Hardening

- It is applied only while `enabled` is on, once at boot, and failures are reported, never thrown (`AegisServiceProvider::applyHardening()`).
- Only write config keys Laravel reads (`session.secure`, `session.http_only`, `session.same_site`, `session.lifetime`, `session.encrypt`).
- HSTS and the HTTPS redirect apply to secure requests only, and the redirect is a 301 to the same URI.
- A new default must not lock the current administrator out: ship it off.

## 6. Caching

- Settings are cached as arrays, never objects, under a versioned key (`aegis.settings.v1`), flushed on every save.
- An unreadable cache or a missing table falls back to the database or the defaults; it never breaks a request.

## 7. Secrets and data

- Never print, log or send `.env`, `auth.json`, cookies or `Authorization` headers. `CheckRunner` logs a failing check's class, not its message.
- `composer audit` runs with fixed arguments through `Process`, never a shell string.

## Review procedure

1. `git diff --stat` and list every changed route, controller, component, setting, rule, probe and cache.
2. Walk each through sections 1 to 6 and name the test that pins it.
3. Run `make ready`.
4. Report each finding as: file:line, what an attacker sends, what happens, the fix.
