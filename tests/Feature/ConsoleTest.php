<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Hardening\HardeningModule;

use Wobqqq\Aegis\Settings\SettingsRepository;

it('turns the hardening off from the console and keeps the other settings', function (): void {
    resolve(SettingsRepository::class)->save(HardeningModule::KEY, ['enabled' => true, 'password_min_length' => 20] + new HardeningModule()->defaults());

    expect(Artisan::call('aegis:disable'))->toBe(0)->and(Artisan::output())->toContain('Aegis hardening is off.');

    expect(Aegis::settings(HardeningModule::KEY))->toMatchArray(['enabled' => false, 'password_min_length' => 20]);
});

it('fails the check command on a failing check only, or on a warning when strict', function (): void {
    config(['app.debug' => false, 'app.env' => 'production', 'app.key' => 'base64:key']);

    expect(Artisan::call('aegis:check'))->toBe(0)
        ->and(Artisan::call('aegis:check', ['--strict' => true]))->toBe(1);

    Aegis::check(new class () implements Check {
        #[Override]
        public function run(): CheckResult
        {
            return CheckResult::fail('custom', 'Custom', 'Broken');
        }
    });

    expect(Artisan::call('aegis:check'))->toBe(1)->and(Artisan::output())->toContain('Broken');
});

it('schedules the daily audit when the configuration asks for it', function (): void {
    config(['aegis.audit.schedule' => true]);
    app()->forgetInstance(Schedule::class);

    $events = collect(resolve(Schedule::class)->events())->map(static fn (Event $event): string => (string)$event->command);

    expect($events->contains(static fn (string $command): bool => str_contains($command, 'aegis:audit')))->toBeTrue();
});
