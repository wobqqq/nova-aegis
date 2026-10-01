<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Support;

/**
 * Typed reads of stored settings, which may predate the rules or be written by hand.
 */
final class Values
{
    /**
     * @param array<string, mixed> $values
     */
    public static function bool(array $values, string $key, bool $default = false): bool
    {
        $value = $values[$key] ?? $default;

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function int(array $values, string $key, int $default, int $min, int $max): int
    {
        $value = $values[$key] ?? null;

        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int)$value));
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function string(array $values, string $key, string $default = ''): string
    {
        $value = $values[$key] ?? null;

        return is_scalar($value) ? trim((string)$value) : $default;
    }

    /**
     * The non-empty values of one column of a table setting, trimmed and without duplicates.
     *
     * @param array<string, mixed> $values
     *
     * @return list<string>
     */
    public static function column(array $values, string $key, string $column): array
    {
        $rows = $values[$key] ?? [];
        $result = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $value = is_array($row) && is_scalar($row[$column] ?? null) ? trim((string)$row[$column]) : '';

            if ($value !== '') {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Rows of a table setting as [target, ports], skipping rows without a target or a valid port.
     *
     * @param array<string, mixed> $values
     *
     * @return list<array{0: string, 1: list<int>}>
     */
    public static function targets(array $values, string $key, string $targetColumn): array
    {
        $rows = $values[$key] ?? [];
        $result = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $target = is_scalar($row[$targetColumn] ?? null) ? trim((string)$row[$targetColumn]) : '';
            $parts = is_scalar($row['ports'] ?? null) ? explode(',', (string)$row['ports']) : [];
            $ports = array_values(array_unique(array_filter(
                array_map(static fn (string $port): int => (int)trim($port), $parts),
                static fn (int $port): bool => $port > 0 && $port <= 65535,
            )));

            if ($target !== '' && $ports !== []) {
                $result[] = [$target, $ports];
            }
        }

        return $result;
    }
}
