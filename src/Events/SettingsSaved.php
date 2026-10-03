<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class SettingsSaved implements ShouldDispatchAfterCommit
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(public string $section, public array $values)
    {
    }
}
