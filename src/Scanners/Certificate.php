<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use Carbon\CarbonInterface;

final readonly class Certificate
{
    public function __construct(
        public CarbonInterface $issuedOn,
        public CarbonInterface $expiresOn,
    ) {
    }
}
