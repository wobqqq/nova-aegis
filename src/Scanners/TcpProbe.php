<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

interface TcpProbe
{
    public const string OPEN = 'open';

    public const string CLOSED = 'closed';

    /**
     * @param list<int> $ports
     *
     * @return array<int, string>
     */
    public function states(string $ip, array $ports): array;
}
