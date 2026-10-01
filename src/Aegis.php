<?php

declare(strict_types=1);

namespace Wobqqq\Aegis;

use Wobqqq\Aegis\Checks\CheckRegistry;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Settings\SettingsRepository;

/**
 * The entry point add-on modules use.
 */
final class Aegis
{
    public static function module(Module $module): void
    {
        resolve(ModuleRegistry::class)->register($module);
    }

    public static function check(Check $check): void
    {
        resolve(CheckRegistry::class)->register($check);
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(string $section): array
    {
        return resolve(SettingsRepository::class)->section($section);
    }
}
