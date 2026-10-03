---
name: error-handling
description: "How failures travel from the code that detects them to the user, the log and the caller. Use when a method could fail (not found, not allowed in this state, limit reached, external service down), when tempted to return false/null/-1 or a status array for failure, when creating an exception class, writing try/catch, mapping exceptions to HTTP status codes, Nova action responses or console exit codes, editing the exception handling in bootstrap/app.php, deciding what is logged or shown to the user, or reviewing code that swallows exceptions."
license: MIT
---

# Error handling

A use case has one successful path. Every deviation from it is an exception that carries enough meaning for the right layer to react. Return values describe success only.

## 1. Do not signal failure with return values

```php
// ❌ the caller must remember to check, and cannot tell why it failed
public function cancel(CancelOrderDto $dto): bool

// ✅ success returns nothing; every failure has a type
public function cancel(CancelOrderDto $dto): void
{
    $order = Order::query()->findOrFail($dto->orderId);   // ModelNotFoundException → 404
    if ($order->isShipped()) {
        throw new OrderAlreadyShipped($order->id);
    }
    $order->cancelled_at = $this->clock->now();
    $order->saveOrFail();
}
```

- No `bool $ok`, `null`-means-error, `['success' => false, 'error' => ...]` results from use cases.
- Use the `*OrFail` methods (`findOrFail`, `firstOrFail`, `saveOrFail`, `updateOrFail`) so a missing row or a failed write cannot pass silently.
- `?Type` is a legitimate return when absence is a normal answer to a **query** (`findByEmail(): ?User`), not when a command failed.
- A result object is justified only where the caller genuinely needs several outcomes at once (validating a batch and reporting every problem). Then make it a typed `final readonly` class with named constructors, not an array.

## 2. Two kinds of failure

| Kind | Examples | User sees | Logged |
|---|---|---|---|
| **Client / business** — the request cannot be fulfilled as asked | order already shipped, campaign already sent, limit of 10 reached, not found | a specific, translated message; 4xx | no (or at info/debug level) |
| **Server / unexpected** — something broke | DB down, API timeout, TypeError, bug | a generic message; 5xx | yes, with context |

Keep them apart in the type hierarchy so the handler can tell them apart without string matching.

## 3. A base business exception

```php
abstract class BusinessException extends \RuntimeException
{
    public function __construct(string $userMessage, ?\Throwable $previous = null)
    {
        parent::__construct($userMessage, 0, $previous);
    }

    public function status(): int
    {
        return 422;
    }
}

final class CampaignAlreadySent extends BusinessException
{
    public function __construct(public readonly int $campaignId)
    {
        parent::__construct(__('campaigns.already_sent'));
    }

    #[\Override]
    public function status(): int
    {
        return 409;
    }
}
```

- One small class per business reason, named as a fact (`CampaignAlreadySent`, `QuotaExceeded`, `OrderAlreadyShipped`). Callers and tests can catch exactly that case.
- The message is meant for the user and is translated; technical detail goes into typed properties or `context()` for logs.
- Extend `\RuntimeException` (unchecked): the global handler deals with it, so `@throws` chains through every layer are not needed. Document `@throws` only where a caller is expected to catch.
- Do not reuse PHP's `\DomainException`/`\InvalidArgumentException` for business cases: libraries throw them too, and `InvalidArgumentException` means a programmer error (see `validation`).

## 4. Translate in one place

Entry points do not catch to convert; the exception handler does, per output channel.

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->dontReport([BusinessException::class]);

    $exceptions->render(function (BusinessException $e, Request $request) {
        return $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], $e->status())
            : back()->withInput()->withErrors(['error' => $e->getMessage()]);
    });
})
```

- Server errors keep Laravel's default rendering (generic 500 page/JSON, details only with `APP_DEBUG`). Never echo `$e->getMessage()` of an unexpected exception to the user.
- Console commands: catch `BusinessException`, print the message, `return self::FAILURE`; let anything else crash with a stack trace.
- Nova actions: catch `BusinessException` and `return Action::danger($e->getMessage())`; unexpected errors bubble up.
- Queued jobs: a business exception that retrying cannot fix calls `$this->fail($e)`; transient errors are rethrown so the queue retries with backoff.

## 5. Catching

- Catch only what you can handle at this level, as narrowly as possible. `catch (\Throwable)` belongs in the handler, a job runner or a loop that must process the remaining items — and there it logs.
- Never swallow: an empty `catch`, `catch { return null; }` or `rescue()` without a report turns an incident into silent data loss.
- Wrap a low-level exception in a meaningful one when crossing a boundary, keeping `$previous`: `throw new TranslationFailed($resourceId, previous: $e);`.
- Do not use exceptions for normal control flow inside a loop over expected data (e.g. "skip invalid CSV rows"): collect problems into a typed result instead.

## 6. Logging

- Log server errors once, at the top (the handler does it). Do not log and rethrow at every layer.
- Add context through the exception (`context(): array` method or properties) or `Log::withContext()`, never by concatenating into the message.
- Never log secrets, tokens, passwords, full request bodies or personal data you do not need.
- High-volume expected failures (third-party flakiness) are throttled or reported to monitoring, not written to the log thousands of times (see `laravel-best-practices` → error handling).

## Checklist

- [ ] Use cases return values only on success; failure is a typed exception.
- [ ] Business failures extend one `BusinessException` base with a user-facing, translated message and an HTTP status.
- [ ] Conversion to HTTP / Nova / console output happens once, in the handler or the entry point's thin catch.
- [ ] No swallowed exceptions; narrow catches; `$previous` kept when wrapping.
- [ ] Unexpected errors are logged once with context and never shown verbatim.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
