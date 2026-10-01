<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;

final readonly class AuditStore
{
    private const string KEY = 'aegis.audit.v1';

    public function __construct(private Cache $cache)
    {
    }

    public function last(): ?AuditResult
    {
        try {
            $data = $this->cache->get(self::KEY);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? AuditResult::fromArray($data) : null;
    }

    public function put(AuditResult $result): void
    {
        $this->cache->forever(self::KEY, $result->jsonSerialize());
    }
}
