<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\Aegis\Settings\SettingsRepository;

function settings(): SettingsRepository
{
    return resolve(SettingsRepository::class);
}

it('starts from the module defaults', function (): void {
    expect(settings()->section(HardeningModule::KEY))->toMatchArray(['enabled' => false, 'password_min_length' => 12])
        ->and(Aegis::settings('missing-module'))->toBe([]);
});

it('saves the validated values over the defaults and drops unknown keys', function (): void {
    $dispatched = [];
    Event::listen(SettingsSaved::class, static function (SettingsSaved $event) use (&$dispatched): void {
        $dispatched[] = $event->section;
    });

    $saved = settings()->save(HardeningModule::KEY, ['enabled' => true, 'password_min_length' => 16, 'injected' => 'value'] + (new HardeningModule())->defaults());

    expect($saved)->toHaveKey('password_min_length', 16)
        ->and(array_key_exists('injected', $saved))->toBeFalse()
        ->and(settings()->section(HardeningModule::KEY)['enabled'])->toBeTrue()
        ->and(AegisSetting::query()->where('section', HardeningModule::KEY)->exists())->toBeTrue();

    expect($dispatched)->toBe([HardeningModule::KEY]);
});

it('refuses invalid values', function (array $values): void {
    /** @var array<string, mixed> $values */
    settings()->save(HardeningModule::KEY, $values + (new HardeningModule())->defaults());
})->throws(ValidationException::class)->with([
    [['password_min_length' => 4]],
    [['session_same_site' => 'none']],
    [['enabled' => 'sometimes']],
    [['hsts_max_age' => 10]],
]);

it('refuses a section no module declares', function (): void {
    settings()->save('nope', []);
})->throws(InvalidArgumentException::class);

it('refuses a module key that cannot be a URL segment', function (): void {
    /** @var Mockery\MockInterface&Wobqqq\Aegis\Contracts\Module $module */
    $module = Mockery::mock(Wobqqq\Aegis\Contracts\Module::class);
    $module->allows('key')->andReturn('Bad Key');

    resolve(ModuleRegistry::class)->register($module);
})->throws(InvalidArgumentException::class);

it('reads a saved value written by another process once the cache is cleared', function (): void {
    expect(settings()->section(HardeningModule::KEY)['enabled'])->toBeFalse();

    AegisSetting::query()->create(['section' => HardeningModule::KEY, 'values' => ['enabled' => true]]);

    expect(settings()->section(HardeningModule::KEY)['enabled'])->toBeTrue();
});

it('falls back to the database when the cache cannot be read', function (): void {
    AegisSetting::query()->create(['section' => HardeningModule::KEY, 'values' => ['enabled' => true]]);
    settings()->flush();

    /** @var CacheRepository&Mockery\MockInterface $cache */
    $cache = Mockery::mock(CacheRepository::class);
    $cache->allows('remember')->andThrow(new RuntimeException('cache down'));
    $repository = new SettingsRepository(resolve(ModuleRegistry::class), $cache, resolve('validator'), resolve('events'));

    expect($repository->section(HardeningModule::KEY)['enabled'])->toBeTrue();
});

it('answers the defaults before the table is migrated', function (): void {
    Schema::drop('aegis_settings');
    settings()->flush();

    expect(settings()->section(HardeningModule::KEY)['enabled'])->toBeFalse();
});

it('lets a module add its own section', function (): void {
    Aegis::module(new class () implements Wobqqq\Aegis\Contracts\Module {
        public function key(): string
        {
            return 'acme-module';
        }

        public function label(): string
        {
            return 'Acme';
        }

        public function description(): string
        {
            return '';
        }

        public function defaults(): array
        {
            return ['enabled' => false];
        }

        public function rules(): array
        {
            return ['enabled' => ['required', 'boolean']];
        }

        public function fields(): array
        {
            return [Wobqqq\Aegis\Settings\Field::toggle('enabled', 'Enabled')];
        }

        public function status(array $values): ?Wobqqq\Aegis\Checks\CheckResult
        {
            return null;
        }
    });

    settings()->save('acme-module', ['enabled' => true]);

    expect(Aegis::settings('acme-module'))->toBe(['enabled' => true]);
});
