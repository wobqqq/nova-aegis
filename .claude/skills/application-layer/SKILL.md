---
name: application-layer
description: "Where business logic lives and how entry points hand work to it. Use when adding or changing a use case (create, publish, suspend, import, send…), when a controller, console command, job, Nova action, Livewire/Inertia handler or listener grows beyond input → call → response, when two entry points need the same behaviour, when writing a service/action class or its input DTO, when a FormRequest should produce data for a service, when deciding where a DB transaction belongs, or when a method takes Request, an array of input or a boolean flag that switches behaviour."
license: MIT
---

# The application layer

Controllers, console commands, jobs, Nova actions and listeners are **entry points**. They translate their own input into a call and the result into their own output. Everything in between — the use case — lives in application classes that do not know which entry point called them.

Introduce this layer when either is true (otherwise a plain controller is fine):

1. the same action is reachable from more than one place (web + API, Nova + console, a job);
2. a handler mixes request/response work with business rules, so a change to one forces reading the other.

Plain CRUD with no rules beyond validation does not need a service; do not add one "for consistency".

## 1. Entry points stay thin

An entry point does three things: build the input, call one use case, shape the output.

```php
// ✅
final class PublishPostController
{
    public function __invoke(PublishPostRequest $request, PublishPost $publishPost): RedirectResponse
    {
        $publishPost->handle($request->toDto());

        return to_route('posts.index');
    }
}

// ❌ rules, queries and side effects inside the controller
public function publish(Request $request, int $id): RedirectResponse
{
    $post = Post::findOrFail($id);
    if ($post->body === '') { return back()->withErrors(...); }
    $post->update(['published_at' => now()]);
    Mail::to($post->author)->send(new PostPublishedMail($post));
    // ...
}
```

- The same holds for `handle()` of a command or job and for Nova `Action::handle()`: build a DTO, call the use case, report.
- Helpers and facades for the response side (`view()`, `to_route()`, `Response::json()`) are fine in entry points; they never move into the use case.

## 2. One use case, one class (or one method of a small service)

- Name it after the action, a verb: `PublishPost`, `SuspendAccount`, `ImportContacts`. Avoid `UserService` that grows to twenty methods with twenty dependencies.
- A small cohesive service (`PostWriter::create/update/delete`) is acceptable while its methods share dependencies; split it when one method needs a dependency the others do not.
- Never merge different actions behind a flag or a mode: `createOrUpdate()`, `toggleBlock()`, `save(bool $isNew)`. Different actions drift apart; keep `create()` and `update()`, `block()` and `unblock()` separate and extract only what is truly identical.
- A boolean parameter that changes behaviour (`export($rows, $skipHeader = false)`) means two responsibilities are hidden in one method. Replace it with two classes, a strategy injected through the constructor, or configuration.

## 3. Input: typed DTOs, never Request or raw arrays

A use case receives `final readonly` DTOs (or a few scalars/value objects). It never receives `Request`, `$request->all()`, `validated()` arrays, or Nova `ActionFields`.

```php
final readonly class PublishPostDto
{
    public function __construct(
        public int $postId,
        public int $publishedBy,
        public ?CarbonImmutable $publishAt,
    ) {}
}

final class PublishPostRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['publish_at' => ['nullable', 'date', 'after:now']];
    }

    public function toDto(): PublishPostDto
    {
        return new PublishPostDto(
            postId: (int)$this->route('post'),
            publishedBy: (int)$this->user()?->getAuthIdentifier(),
            publishAt: $this->date('publish_at')?->toImmutable(),
        );
    }
}
```

- The DTO speaks the domain, not HTML forms: a checkbox becomes `bool`, a date string becomes `CarbonImmutable`, "field present" never encodes meaning.
- Each entry point has its own mapper to the same DTO: `FormRequest::toDto()`, a private `dto()` in a console command, a static `fromFields()` for a Nova action.
- Map field by field with typed accessors (`string()`, `integer()`, `boolean()`, `date()`, `enum()`). Never `Model::create($request->validated())` or `fill($request->all())`: it hides which fields are written (mass-assignment risk, impossible to find usages).
- Use `validated()` only as the source of a DTO, never as the thing passed on.

## 4. The use case owns data access

- Pass ids (or value objects), not models the caller had to load: `PublishPost::handle(PublishPostDto)` loads the post itself. Entry points then never query the database.
- Load for writing inside the use case (`Post::query()->lockForUpdate()->findOrFail($id)` when concurrent writers matter); never trust a model instance that came from a cache or a read replica.
- Assign attributes explicitly (`$post->published_at = $dto->publishAt ?? now();`) so "find usages" of a column finds every write.
- Route-model binding is fine for read-only pages; for writes prefer an explicit id passed to the use case.

## 5. Transactions at the use-case boundary

- One use case = one transaction when it writes more than one row that must stay consistent. Open it in the use case, not in the controller and not inside a model method.
- Keep it short: do slow work (HTTP calls, image processing, exchange-rate lookups, file storage) **before** opening the transaction, and side effects (mail, queue, cache clear, external API) **after** commit — see the `events` skill.
- Inject `Illuminate\Database\ConnectionInterface` (or use `DB::transaction()` in code that is never unit tested) and return the result from the closure.

```php
public function handle(PlaceOrderDto $dto): int
{
    $rate = $this->rates->rateFor($dto->currency);            // slow, outside

    $orderId = $this->db->transaction(function () use ($dto, $rate): int {
        $order = new Order();
        $order->customer_id = $dto->customerId;
        $order->currency = $dto->currency->value;
        $order->exchange_rate = $rate;
        $order->save();
        foreach ($dto->lines as $line) {
            $order->lines()->create(['sku' => $line->sku, 'quantity' => $line->quantity]);
        }

        return $order->id;
    });

    $this->events->dispatch(new OrderPlaced($orderId));       // after commit

    return $orderId;
}
```

## 6. Output

- Return what the caller needs and nothing it must interpret: `void`, the new id, a result DTO. Never `bool $ok` or `null` for failure — failures are exceptions (see `error-handling`).
- Do not return a half-loaded model for the caller to keep working with; return an id or a read DTO and let reads go through query classes (`domain-layer-cqrs`).

## 7. What stays out of models, observers and controllers

- Models: relations, casts, scopes, small invariant-keeping methods (`$post->publish()` that checks its own state). Not mail, HTTP, other aggregates or authorization.
- Observers / model events: infrastructure only (cache keys, search index sync, audit stamps). Business reactions are explicit events from the use case. An observer that compares `getOriginal()` to guess what happened (`$order->wasChanged('status') && $order->status === 'cancelled'`) means a `CancelOrder` use case is missing.
- Controllers: no queries beyond what a read page needs, no business conditions.

## Checklist

- [ ] The entry point builds a DTO, calls one use case, returns a response — nothing else.
- [ ] The use case is named after an action and takes typed DTOs / ids, never `Request` or arrays.
- [ ] No behaviour-switching booleans, no `createOrUpdate`/`toggle` methods.
- [ ] Columns are assigned explicitly; no `create($request->...)`.
- [ ] Multi-row writes are in one short transaction; slow work before, side effects after commit.
- [ ] Failures are exceptions, success returns a value or `void`.
- [ ] Laravel-specific API details: the `laravel-best-practices` skill.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
