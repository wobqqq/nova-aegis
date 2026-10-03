---
name: dependency-injection
description: "How classes get their collaborators and configuration. Use when writing a constructor, adding a dependency to an application/domain class, calling a facade or helper (DB::, Cache::, Http::, Mail::, Event::, now(), config(), auth(), app()) outside a controller, introducing or naming an interface, binding something in a service provider (bind, singleton, when()->needs()->give(), contextual attributes), reaching for inheritance, a Base* class or a trait to share behaviour, writing a static method, or when a class becomes hard to unit test or its constructor grows past four or five parameters."
license: MIT
---

# Dependency injection

The constructor is the class's contract: its public methods say what it does, its constructor says what it needs. Anything a class uses but does not declare is a hidden dependency.

## 1. Ask, don't fetch

- Application and domain classes receive collaborators through the constructor (promoted `private readonly` properties). They do not call facades, `app()`, `resolve()` or global helpers that reach the outside world.
- Entry points (controllers, Nova resources, Blade) may use facades and helpers for the response side; nobody unit tests a controller.

```php
// ✅
final readonly class SendInvoice
{
    public function __construct(
        private Mailer $mailer,
        private InvoiceRenderer $renderer,
        private ClockInterface $clock,
    ) {}
}

// ❌ dependencies discovered only by reading the body
final class SendInvoice
{
    public function handle(int $id): void
    {
        $pdf = app(InvoiceRenderer::class)->render($id);
        Mail::to(...)->send(...);
        $sentAt = now();
    }
}
```

Why: a hidden facade survives in tests as the real implementation — real mails, real HTTP, real payments — and the class cannot be reused or reasoned about without reading every line.

| Facade / helper | Inject instead |
|---|---|
| `DB::` | `Illuminate\Database\ConnectionInterface` |
| `Event::`, `event()` | `Illuminate\Contracts\Events\Dispatcher` |
| `Bus::`, `dispatch()` | `Illuminate\Contracts\Bus\Dispatcher` |
| `Cache::` | `Illuminate\Contracts\Cache\Repository` |
| `Mail::` | `Illuminate\Contracts\Mail\Mailer` |
| `Http::` | `Illuminate\Http\Client\Factory` |
| `Storage::` | `Illuminate\Contracts\Filesystem\Factory` (or `#[Storage('disk')]`) |
| `now()`, `Date::` in logic | `Psr\Clock\ClockInterface` |
| `auth()->user()` | pass the acting user/id in the DTO, or `#[CurrentUser]` at the entry point |
| `config()` | typed constructor values (see §4) |

## 2. Classes first, interfaces where there is a real seam

- Depend on a concrete `final` class when there is one sensible implementation of pure logic. The container injects it; converting it to an interface later changes only the binding.
- Introduce an interface at an **I/O boundary or a variation point**: an external API client, storage, a mailer, the clock, a strategy with several implementations (`SpamFilter` → `StrictSpamFilter`, `LenientSpamFilter`).
- Name the interface after the concept (`Storage`, `Translator`, `ExchangeRates`) and implementations after what makes them different (`S3Storage`, `OpenAiTranslator`, `FakeExchangeRates`). No `StorageInterface`, `IStorage`, `StorageImpl`, `Contracts\Storage` next to a class `Storage`.
- Public packages (Aegis modules, Fortify plugins) expose interfaces for every extension point; applications do not need an interface per class.

## 3. Composition over inheritance

- Every non-abstract class is `final`. A parent class has two audiences (its callers and its children); a change for one silently breaks the other.
- Need extra behaviour around an existing implementation? Write a decorator that implements the same interface and wraps the original, then bind it:

```php
final readonly class LoggingTranslator implements Translator
{
    public function __construct(private Translator $inner, private LoggerInterface $log) {}

    #[\Override]
    public function translate(string $text, Locale $to): string
    {
        $this->log->info('translate', ['to' => $to->value, 'chars' => mb_strlen($text)]);

        return $this->inner->translate($text, $to);
    }
}

// AppServiceProvider::register()
$this->app->bind(Translator::class, LoggingTranslator::class);
$this->app->when(LoggingTranslator::class)->needs(Translator::class)->give(OpenAiTranslator::class);
```

- No `BaseService`/`BaseRepository` to share a helper: extract the helper into its own class and inject it. The framework's base classes (`Model`, `FormRequest`, `Command`, Nova `Resource`) are the exception.
- Traits are not a way to share logic in application code: they read private state, assume fields exist and hide dependencies. Acceptable in tests and when the framework requires them (`HasFactory`, `SoftDeletes`, `Queueable`).

## 4. Configuration is a typed value, read once

- Read config at the edge (service provider, contextual attribute) and pass typed scalars or a small config DTO into the class. The class does not know config keys.

```php
final readonly class ExchangeRatesClient
{
    public function __construct(
        private Factory $http,
        #[Config('services.rates.url')] private string $baseUrl,
        #[Config('services.rates.timeout')] private int $timeoutSeconds,
    ) {}
}
```

- If an option is never set differently, make it a typed class constant instead of a parameter (`private const int MAX_EXPORT_ROWS = 10000;`). Add flexibility when someone needs it, not before.
- Behaviour that differs per use (strict vs lenient spam filtering) is either a different injected strategy or an explicit config entry — never a boolean parameter on every call.

## 5. Statics and state

- Static methods are fine for pure, private helpers of one module that never touch I/O: key builders (`CacheKeys::post(int $id)`), formatters, named constructors (`Money::fromCents()`). Anything that queries, writes, calls a service or reads the clock is an instance method on an injected object.
- No mutable static state and no singletons holding request data; per-request values travel in DTOs.
- Register stateless services as `scoped`/`singleton` only when they are truly stateless; Octane and queue workers reuse them across requests.

## 6. Smells

- Constructor over ~5 dependencies → the class does several jobs; split by use case.
- A dependency used by one method only → that method probably belongs to another class.
- `__call`, `__callStatic`, string-built method names, reflection in application code → replace with explicit methods; magic defeats static analysis and "find usages".
- Hard to construct in a test → hidden dependencies or too many responsibilities.

## Checklist

- [ ] Every collaborator and config value of an application/domain class is in its constructor.
- [ ] No facades or I/O helpers outside entry points.
- [ ] Interfaces only at I/O boundaries or real variation points, named after the concept.
- [ ] Classes are `final`; shared behaviour is a decorator or an injected helper, not a base class or trait.
- [ ] Config arrives typed; never-changing options are typed constants.
- [ ] Static methods are pure and module-private.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
