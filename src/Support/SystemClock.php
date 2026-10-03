<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Support;

use DateTimeImmutable;
use Illuminate\Support\Facades\Date;
use Override;
use Psr\Clock\ClockInterface;

/**
 * @internal the application's clock, so time travel in tests applies
 */
final readonly class SystemClock implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return Date::now()->toImmutable();
    }
}
