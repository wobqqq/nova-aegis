<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Events;

final readonly class SettingsSaved
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(public string $section, public array $values)
    {
    }
}
