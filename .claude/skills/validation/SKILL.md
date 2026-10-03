---
name: validation
description: "Which check goes where. Use when writing FormRequest rules or a Validator call, adding a rule that queries the database (exists, unique, Rule::exists/unique), checking a state or limit before an action (category archived, campaign already sent, max 10 items, email taken), validating input of a console command, job payload, Nova action or API, designing a DTO or a value object (Email, Money, Locale, Url, DateRange), or deciding whether a service should trust the data it receives."
license: MIT
---

# Validation: two levels

Input is checked twice, for two different reasons, in two different places.

| Level | Question | Where | Failure |
|---|---|---|---|
| **Input** | Did the user/caller send well-formed data? Types, required, formats, lengths, confirmations | `FormRequest`, the console command, the Nova action's `fields()` rules, API request classes | validation error for that field (422 / redirect with errors) |
| **Business** | Can this action happen to these objects right now? Existence, state, limits, uniqueness, permissions on the specific record | the use case or the entity method | a `BusinessException` (see `error-handling`) |

## 1. Input validation stays about the input

- Required, type, format, length, ranges, `confirmed`, `in:` lists of an enum: yes.
- Fields that exist only in the form (`password_confirmation`, a "terms" checkbox, UI-only toggles) are validated here and never reach the DTO.
- Do not encode business state in input rules:

```php
// ❌ the HTTP layer now knows about soft deletes, archiving and publishing rules
'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')->where('archived', false)],

// ✅ shape only
'category_id' => ['required', 'integer'],
```

The use case loads the category and throws `CategoryNotFound` / `CategoryArchived`. When the meaning of "available category" changes, one class changes, and every entry point (web, API, Nova, console) gets the same rule.

- Database rules are acceptable for pure uniqueness-of-input UX on a single simple form (e.g. `unique:users,email` to show the message next to the field) **only if** the use case enforces the same invariant (unique index + exception). The rule is a convenience; the use case is the guarantee.

## 2. The use case does not trust its caller

A DTO can be built anywhere — a test, a seeder, a job, another use case. The use case re-checks what the business depends on:

```php
public function handle(AddPlaylistTrackDto $dto): void
{
    $playlist = Playlist::query()->lockForUpdate()->findOrFail($dto->playlistId);

    if ($playlist->isLocked()) {
        throw new PlaylistIsLocked($playlist->id);
    }
    if ($playlist->tracks()->count() >= Playlist::MAX_TRACKS) {
        throw new PlaylistIsFull(Playlist::MAX_TRACKS);
    }

    $playlist->tracks()->attach($dto->trackId);
}
```

- Back every uniqueness/consistency invariant with the database (unique index, foreign key, NOT NULL) and translate the constraint violation into a business exception where it matters. Checks in PHP alone race under concurrency.
- Limits and allowed state transitions are constants or methods on the model/entity (`Playlist::MAX_TRACKS`, `$campaign->canBeSent()`), not scattered literals.

## 3. Value objects make invalid values unrepresentable

Wrap a primitive when it has rules or behaviour that would otherwise be repeated: email, money, locale, URL, IP/CIDR, percentage, date range, geo point.

```php
final readonly class Email
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = mb_strtolower(trim($value));
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid email address.', $value));
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

- Construction validates; afterwards every holder can trust it. Prefer behaviour on the value (`Money::add()`, `DateRange::overlaps()`, `Cidr::contains()`) over exposing internals for others to compute.
- The exception thrown by a value object is `\InvalidArgumentException` (a `LogicException`): it means **the code** passed an unchecked value. User input reaches a value object only after input validation; if the exception fires, it is a bug and a 500, not a form error.
- Composite values group fields that are meaningless apart: `Address`, `GeoPoint(lat, lng)`, `Money(amount, currency)`.
- Store them through Eloquent custom casts (`CastsAttributes`) when the model should hold the object, or map explicitly in the use case. Weigh the cost: for a plain CRUD field a validated `string` is enough.

## 4. DTOs and validation

- DTO constructors carry types, not rules: `public Email $email`, `public CarbonImmutable $birthDate`, `public int $quantity`. Use value objects for fields with rules.
- Do not move all input validation into the DTO/use case and delete the FormRequest: the user would get messages about fields they never typed (values filled from the session, defaults), and multi-field UI rules do not belong to the business.
- Do not duplicate one rule as both a regex in the FormRequest and a different regex in the value object; let the input rule be the user-friendly version and the value object the guarantee, and test both against the same examples.

## 5. Other entry points

- Console: validate options/arguments with `Validator::make()` (or explicit checks) before building the DTO; print errors and return `self::INVALID`.
- Nova actions: `rules()` on each field for input; business conditions in the use case, reported via `Action::danger()`.
- Jobs and listeners: their payload was produced by code, so do not re-run input validation — but the use case they call still checks business state, which may have changed since dispatch.
- Imports (CSV, API sync): validate row by row, collect errors into a typed report instead of failing the whole batch on the first bad row.

## Checklist

- [ ] Input rules check shape only; no state, soft-delete or permission logic in `exists`/`unique` rules.
- [ ] The use case re-checks existence, state and limits and throws named business exceptions.
- [ ] Invariants that must hold under concurrency are also database constraints.
- [ ] Primitives with rules are value objects that cannot be constructed invalid; their failure is a programmer error.
- [ ] UI-only fields never reach DTOs.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
