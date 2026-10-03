---
name: domain-layer-cqrs
description: "When and how to give business rules their own layer, and how to separate reads from writes. Use when business rules start repeating across services, when an entity needs invariants (an aggregate with children that must stay consistent), when considering entities with behaviour, value objects, domain events recorded by entities, UUID identities, Doctrine/data mapper, read models, query classes, reporting queries, caching of reads, read replicas, or event sourcing — and before introducing any of these, to check that the project needs them."
license: MIT
---

# Domain layer, CQRS and event sourcing — only when they pay

Most Laravel applications are content/integration heavy with modest business rules: an application layer over Eloquent (see `application-layer`) is the right size. The patterns below are tools for specific pain; each has a cost in code, onboarding and synchronisation.

## Do not introduce unless

| Pattern | Introduce only when | Do not, when |
|---|---|---|
| Rich entities (behaviour on models) | rules about one object's state repeat in several use cases | the model is a form-to-table mapping |
| Separate domain layer (plain PHP entities, data mapper) | the rules are the product (games, pricing, billing, scheduling, workflows) and must be unit tested exhaustively | CRUD, CMS, admin panels, integrations |
| Query classes / read DTOs | read logic is non-trivial (filters, joins, aggregates, reports) or must be cached | a list is `Model::query()->latest()->paginate()` |
| Full CQRS (separate read model/storage) | reads and writes have different shapes, technologies or scale | one database serves both comfortably |
| Event sourcing | history *is* the business data (ledgers, audit-regulated domains, rules computed from past moves) | history is only "nice to have" — use an audit log table |

When the answer is "not yet", say so in the change and keep the simpler design.

## 1. Behaviour belongs with the data it guards

Even without a separate layer, put state rules on the model or entity instead of in every service:

```php
final class Campaign extends Model
{
    public function markQueued(int $recipients): void
    {
        if ($this->status !== CampaignStatus::Draft) {
            throw new CampaignAlreadySent($this->id);
        }
        if ($recipients === 0) {
            throw new CampaignHasNoRecipients($this->id);
        }
        $this->status = CampaignStatus::Queued;
        $this->recipients_count = $recipients;
    }
}
```

- Methods named after domain actions (`publish`, `markQueued`, `close`), not setters. Prefer "tell, don't ask": the caller asks the object to do something; it does not read three fields and decide for it.
- An **aggregate** is a parent with children that share invariants (order + lines, playlist + tracks, show + bookings). Children are changed only through the parent (`$show->book()`), which checks the rule (`capacity`, `no double booking`) in one place.
- Expose only what callers need. Getters added "just in case" leak structure and make every change ripple.

## 2. A real domain layer (when the table above says yes)

- Entities are plain `final` PHP classes with private state, named constructors (`Show::schedule()`, `Customer::register()`), invariants checked in every state-changing method, value objects for every concept (`Money`, `Email`, `ShowId`).
- Identity is assigned by the application (UUID/ULID value objects), not by the database, so entities are complete before persistence and tests need no database.
- Entities **record** domain events (`$this->recorded[] = new SeatBooked(...)`) and the application layer releases and dispatches them after persisting — the entity never sees the dispatcher.
- Persistence through a data mapper (Doctrine via `laravel-doctrine/orm`) or hand-written repositories mapping to Eloquent/SQL. Keep mapping out of the entity where possible.
- Domain errors: specific exceptions per rule; if a caller needs all violations at once, return a typed validation result instead of throwing the first.
- Unit-test the domain without the framework (see `testing-architecture`).

## 3. Separate reads from writes (light CQRS)

Writes and reads change for different reasons: writes need fresh, locked, consistent data and invariants; reads need joins, aggregates, pagination and caching.

- Write side: use cases (`application-layer`) — they load what they change from the primary connection, never from a cache or replica.
- Read side: query classes returning read DTOs or arrays shaped for the screen/API:

```php
final readonly class CampaignReportQuery
{
    public function __construct(private ConnectionInterface $db) {}

    /** @return list<CampaignReportRow> */
    public function forPeriod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->db->table('campaigns as c')
            ->join('campaign_stats as s', 's.campaign_id', '=', 'c.id')
            ->whereBetween('c.sent_at', [$from, $to])
            ->groupBy('c.id', 'c.name')
            ->get(['c.id', 'c.name', $this->db->raw('SUM(s.opens) as opens')])
            ->map(fn (object $row): CampaignReportRow => CampaignReportRow::fromRow($row))
            ->all();
    }
}
```

- Reports and dashboards use the query builder or SQL, not Eloquent models with dozens of accessors.
- Cache reads with a decorator around the query class (same interface, `CachedCampaignQueries`), invalidated by events — never inside the write path.
- A read-only Eloquent model for a view or projection may throw on `save()`/`delete()` to make writes impossible.
- With replicas or caches, reads may be slightly stale (eventual consistency). Never feed a stale read into a write decision.

## 4. Event sourcing (rarely)

- State is derived by replaying an append-only stream of events; current-state tables are projections rebuilt from it.
- Gains: full history, temporal queries, new rules that apply retroactively, natural audit.
- Costs: every read needs a projection, uniqueness constraints need separate guard tables, schema changes become event versioning, the team must think in events. Use an established library; never hand-roll a store for a production system.
- A plain audit/history table next to normal state is the right answer for most "we want history" requests.

## Checklist

- [ ] The pattern solves a pain the project has today, written down in the change.
- [ ] State rules live on the entity/model in action-named methods; aggregates guard their children.
- [ ] Domain entities (if any) are framework-free, identified by app-generated ids, record events, unit tested.
- [ ] Non-trivial reads go through query classes returning read DTOs; writes never read from caches/replicas.
- [ ] Event sourcing only for history-as-data domains, with a library.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
