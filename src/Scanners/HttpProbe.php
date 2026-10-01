<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

interface HttpProbe
{
    /**
     * The status code of each URL, or "error". Redirects are not followed: a sensitive path
     * redirected to the home page would answer 200 there and read as exposed.
     *
     * @param list<string> $urls
     *
     * @return array<string, int|string>
     */
    public function statuses(array $urls): array;
}
