<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Support;

/**
 * @internal
 */
final class Lang
{
    /**
     * @param array<string, int|string> $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $line = trans($key, $replace);

        return is_string($line) ? $line : $key;
    }
}
