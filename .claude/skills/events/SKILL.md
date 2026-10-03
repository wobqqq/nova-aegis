---
name: events
description: "Side effects and reactions to what the application did. Use when a use case must also send mail, notify, clear or warm a cache, regenerate a sitemap, sync a search index, call an external API or start a follow-up job; when creating an event, listener, subscriber or queued job; when touching model observers, $dispatchesEvents or the booted() hooks; when something is dispatched inside DB::transaction; when choosing between a direct call, a job and an event; or when a listener receives an Eloquent model in its payload."
license: MIT
---

# Events and side effects

A use case has a **core** (the state change that must happen together) and **side effects** (mail, cache, search index, sitemap, webhooks, statistics). The core runs in the use case, inside one transaction. Side effects run after the commit, preferably in the background, and the use case should not need to know each of them.

## 1. Choose the mechanism

| Situation | Use |
|---|---|
| The side effect is part of the use case's meaning and there is exactly one | call it directly after commit, or dispatch one job |
| Several independent reactions, likely to grow, owned by different features | dispatch a business event; each reaction is a listener |
| A reaction must happen in the same transaction (consistency) | it is not a side effect — put it in the core |
| Slow or unreliable work (HTTP, PDFs, mass mail) | queued job or queued listener |

## 2. Business events, named in the past tense

- An event states a fact of the domain: `CampaignSent`, `AccountSuspended`, `OrderPlaced`, `TranslationFailed`. Not `UserSaved`, `RowUpdated`, `BeforeSomething`.
- Dispatch it from the use case (or release it from the entity — see `domain-layer-cqrs`), at the point where the fact is true and committed.
- Payload: identifiers and the few immutable values listeners need (`campaignId`, `sentAt`, `recipientCount`). A `final readonly` class.

```php
final readonly class CampaignSent implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $campaignId,
        public int $recipientCount,
    ) {}
}
```

- Pass ids, not Eloquent models. A model in the payload carries whatever relations happened to be loaded; a listener reading `$event->campaign->recipients` may see a stale collection, and a queued listener re-fetches it anyway. Let each listener load fresh data by id.

## 3. Never react before the commit

- Events, jobs, mail and notifications dispatched inside a transaction may fire for data that is then rolled back, or run before the rows are visible to the worker.
- Implement `ShouldDispatchAfterCommit` on events, `ShouldQueueAfterCommit` / `$afterCommit = true` on queued listeners and jobs, `->afterCommit()` on notifications and mailables sent from transactional code, or dispatch after `transaction()` returns. With `QUEUE_CONNECTION=sync` in development the difference is invisible — write code that is correct for both.
- Do not hold a transaction open while dispatching to a slow broker or calling an API.

## 4. Model events are infrastructure, not business

- Eloquent `saved`/`deleted` events fire per row, also for half-built aggregates (an order saved before its lines), inside the transaction, and not at all for mass updates (`Model::query()->update()`) or database cascades.
- Use observers/model events only for row-level technical concerns: invalidating the cache of that row, stamping audit columns, syncing a search index entry.
- Business reactions never live in observers. An observer that diffs `getOriginal()` to infer "the account was just suspended" is a missing explicit `SuspendAccount` use case dispatching `AccountSuspended`.

## 5. Listeners

- One reaction per listener class, named after the reaction: `SendCampaignReport`, `ForgetCampaignCache`, `NotifyCrm`.
- A listener calls an application service or job; it does not reimplement business rules.
- Queued listeners and jobs are **idempotent**: they can run twice (retries, at-least-once delivery). Guard with state checks ("already notified?"), unique keys or `ShouldBeUnique`.
- Set `$tries`, `backoff()`, `$timeout` and a `failed()` handler on anything that calls the outside world; log failures with context.
- Listeners must not throw business exceptions back into the dispatching use case. If a reaction can fail, it runs queued and fails on its own.

## 6. Keep the core lean

```php
public function handle(SendCampaignDto $dto): void
{
    $recipients = $this->recipients->forCampaign($dto->campaignId);   // reads before
    $this->db->transaction(function () use ($dto, $recipients): void {
        $campaign = Campaign::query()->lockForUpdate()->findOrFail($dto->campaignId);
        $campaign->markQueued(count($recipients));                      // core state change
        $campaign->save();
    });

    $this->events->dispatch(new CampaignQueued($dto->campaignId));     // reactions after commit
}
```

## 7. Orchestration vs choreography

- **Choreography**: services react to each other's events. Fine for independent reactions; hard to follow when a business process spans many steps.
- **Orchestration**: one process class (or a job chain/batch, `Bus::chain`, `Bus::batch`) drives the steps explicitly and handles failures and compensation. Prefer it when the order matters, a later step can fail and earlier steps must be undone or retried, or someone has to answer "where is this process now?".
- Store the state of a long-running process in the database (status column, timestamps), not only in queued payloads.

## Checklist

- [ ] The core state change is one transaction in the use case.
- [ ] Side effects run after commit (`ShouldDispatchAfterCommit`, `afterCommit`, or dispatch after the transaction).
- [ ] Events are past-tense business facts with id/value payloads, not models.
- [ ] No business reactions in observers or `$dispatchesEvents`.
- [ ] Queued listeners/jobs are idempotent and have retries, timeouts and failure handling.
- [ ] Multi-step processes with failure paths are orchestrated explicitly.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
