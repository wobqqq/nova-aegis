<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Contracts;

use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Settings\Field;

/**
 * A settings section of the Aegis page: the core's own sections and every add-on module
 * implement it, so the page draws and validates them all the same way.
 */
interface Module
{
    /**
     * The settings section, unique among modules: lowercase letters, digits and dashes.
     */
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * Validation rules for the section's values, keyed like Laravel rules (`ips.*.ip`).
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * @return list<Field>
     */
    public function fields(): array;

    /**
     * The module's line on the dashboard, or null when it adds none.
     *
     * @param array<string, mixed> $values
     */
    public function status(array $values): ?CheckResult;
}
