<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks;

use Wobqqq\Aegis\Contracts\Check;

final class CheckRegistry
{
    /** @var list<Check> */
    private array $checks = [];

    public function register(Check $check): void
    {
        $this->checks[] = $check;
    }

    /**
     * @return list<Check>
     */
    public function all(): array
    {
        return $this->checks;
    }
}
