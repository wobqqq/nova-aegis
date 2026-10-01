<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

interface TlsProbe
{
    /**
     * Null when no valid certificate is presented.
     */
    public function certificate(string $host, int $port): ?Certificate;
}
