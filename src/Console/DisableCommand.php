<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Console;

use Illuminate\Console\Command;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Settings\SettingsRepository;

final class DisableCommand extends Command
{
    protected $signature = 'aegis:disable';

    protected $description = 'Turn the Aegis hardening off, for an administrator it locked out.';

    public function handle(SettingsRepository $settings): int
    {
        $settings->save(HardeningModule::KEY, array_replace($settings->section(HardeningModule::KEY), ['enabled' => false]));

        $this->components->info('Aegis hardening is off.');

        return self::SUCCESS;
    }
}
