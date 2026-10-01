---
name: nova-component-testing
description: >-
  Use whenever you write, run or change the Vitest specs of the Aegis page and
  card: resources/js/**/*.spec.js, resources/js/testing.js, vitest.config.js,
  or when a Vue component in resources/js needs tests or coverage. Captures the
  Vitest + happy-dom + @vue/test-utils setup, how the Nova global is faked, the
  stubs for Nova's components and the 90/85 coverage gate.
metadata:
  author: project
---

# Testing the Vue components

The page (`pages/Tool.vue`), the card (`components/AegisCard.vue`) and their components are tested with **Vitest 4, happy-dom and @vue/test-utils**, apart from the Nova runtime.

## Commands

- `make npm.test` — `vitest run`.
- `make npm.test.coverage` — v8 coverage; lines, functions and statements 90 %, branches 85 %. `tool.js` and `testing.js` are excluded.
- One file while iterating: `docker compose run --rm node npx vitest run resources/js/components/FieldInput.spec.js`.

## The seams (`resources/js/testing.js`)

- `fakeNova(responses)` sets `globalThis.Nova` with `request()`, `success` and `error`. `responses` maps `'METHOD url'` to the data to answer, a function returning it, or an `Error` to reject with. It returns the request mock for assertions (`request.put` toHaveBeenCalledWith …).
- `httpError(status, data)` builds the error shape `api.js` reads (`error.response.data.message`, `.errors`).
- `stubs` replaces Nova's `Head`, `Heading` and `Card` so their slots still render.

## Rules

- A spec sits next to its component (`Foo.vue` → `Foo.spec.js`).
- Drive the DOM (`setValue`, `trigger('click' | 'submit')`) and assert what the administrator sees (`text()`, classes) and what was sent (`request.*` calls). Read `wrapper.vm` only when the DOM cannot show it.
- `await flushPromises()` after anything that calls the API, including `mounted()`.
- Cover each answer a handler branches on: success, 422 with `errors` (the field and the row keys `targets.0.host`), another error with a `message`, and no response at all.
- Escaping is a spec: a value holding `<script>` must not come out as markup.
