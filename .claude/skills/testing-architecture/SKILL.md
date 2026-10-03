---
name: testing-architecture
description: "What kind of test to write for which code, and what to fake. Use when deciding between a unit test and a feature test, writing tests for a use case, an entity, a value object, a pure function, a listener or a job, when reaching for Mockery/createMock or facade fakes (Event::fake, Mail::fake, Http::fake, Queue::fake), when a test asserts the database directly, when a class is hard to test, or when a test breaks after a refactoring that did not change behaviour. Pest/PHPUnit syntax and naming live in the testing-best-practices / pest-testing skills."
license: MIT
---

# Testing architecture

Tests exist to let the code change safely. A test that breaks when behaviour did not change, or keeps passing when it did, costs more than it gives.

## 1. Pick the level by what the code is

| Code | Test | Base |
|---|---|---|
| Pure function, value object, entity/domain logic, calculators, parsers | **unit**: no container, no database, no facades | `PHPUnit\Framework\TestCase` / Pest without `uses(TestCase::class)` |
| Use case (application service) with Eloquent, transactions, events | **feature/integration** through the real container and a test database | the app `TestCase` |
| HTTP endpoint, Nova resource/action, console command | **functional**: drive it from outside, assert observable output | the app `TestCase` |

- Do not force unit tests onto application services: mocking `ConnectionInterface`, repositories and Eloquent to avoid a database produces long, brittle tests that mirror the implementation. Test them as integration tests.
- If you badly want to unit test business rules buried in a service, that is the signal to move those rules into an entity, value object or policy class that can be tested alone (see `domain-layer-cqrs`).

## 2. Unit tests: fast, isolated, black-box

- Unit tests extend the plain PHPUnit `TestCase`, so an accidental facade call fails loudly instead of silently hitting the real service.
- Test inputs and outputs, edge values (empty, zero, exactly the limit, limit + 1, negative, multibyte) and the exception for each precondition.
- Stateful objects: arrange the state through public behaviour (create, then call methods), act once, assert the result or the recorded events — never by poking private fields.

```php
it('refuses to book a seat that is already taken', function (): void {
    $show = Show::scheduled(capacity: 2);
    $show->book(SeatNumber::of(1), CustomerId::new());

    expect(fn () => $show->book(SeatNumber::of(1), CustomerId::new()))
        ->toThrow(SeatAlreadyBooked::class);
});
```

- When a bug is found, first write the failing test that reproduces it, then fix.

## 3. Test doubles: only at boundaries

- Fake what leaves the process: HTTP APIs, mail, queues, the clock, storage, randomness. Do not mock your own value objects, entities or pure helpers — use the real ones.
- Prefer the framework fakes and small hand-written fakes over interaction mocks:
  - `Http::fake()` / `Http::preventStrayRequests()`, `Mail::fake()`, `Queue::fake()`, `Event::fake([OnlyThis::class])`, `Storage::fake()`, `Date`/`$this->travelTo()`;
  - a `final` in-memory fake implementing your interface (`FakeTranslator`, `FakeExchangeRates`) when several tests need the same behaviour.
- Assert outcomes, not calls. `expects($this->once())->method('save')` breaks on every refactoring; "the translation is stored and `TranslationCompleted` was dispatched" survives it. Verify interactions only when the interaction *is* the behaviour (an outgoing request's payload, a mail's recipient).
- Enable `Http::preventStrayRequests()` (and equivalent guards) globally in the test base so a forgotten fake never reaches a real service.

## 4. Functional tests: assert behaviour, not storage

- Drive the feature like a client and verify through the application's own outputs:

```php
it('deletes a post', function (): void {
    $post = Post::factory()->create();

    $this->actingAs(admin())->deleteJson("/api/posts/{$post->id}")->assertNoContent();

    $this->actingAs(admin())->getJson("/api/posts/{$post->id}")->assertNotFound();
});
```

- `assertDatabaseHas/Missing` couples the test to the schema (soft deletes, renamed columns, JSON storage). Use it only when persistence itself is the requirement (a migration, an audit table) or there is no read path.
- Cover authorization: the same request as a user without permission must fail.
- Keep functional tests for the main scenarios and the risky edges; push combinatorial edge cases down to unit tests of the extracted logic.

## 5. Testability is a design check

Hard to test usually means:

- hidden dependencies (facades, `app()`, `new` of I/O classes inside methods) → inject them (see `dependency-injection`);
- several responsibilities in one class → split;
- rules mixed with I/O → extract the rules into pure code;
- time, randomness or ids generated inside → inject a clock / id generator or pass them in.

Do not create interfaces or repositories *only* to satisfy a mocking style; change the test level instead.

## 6. Architecture tests

Encode the conventions so they cannot drift:

```php
arch('strict types everywhere')->expect('App')->toUseStrictTypes();

arch('classes are final')->expect('App')->classes()->toBeFinal();

arch('no debugging helpers')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();

arch('use cases do not touch HTTP')
    ->expect('App\Actions')
    ->not->toUse(['Illuminate\Http\Request', 'Illuminate\Support\Facades\Request']);
```

## Checklist

- [ ] Pure logic → unit tests on plain PHPUnit, black-box, edge values covered.
- [ ] Use cases → integration tests against the test database, outcome assertions.
- [ ] Doubles only at process boundaries; fakes over interaction mocks; stray HTTP blocked.
- [ ] Functional tests verify through the app's outputs, plus authorization failures.
- [ ] Every bug fix starts with a failing test.
- [ ] Architecture tests guard strict types, finality, layering and debug helpers.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
