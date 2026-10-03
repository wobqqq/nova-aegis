<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\Aegis\Settings\Field;
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

    $saved = settings()->save(HardeningModule::KEY, ['enabled' => true, 'password_min_length' => 16, 'injected' => 'value'] + new HardeningModule()->defaults());

    expect($saved)->toHaveKey('password_min_length', 16)
        ->and(array_key_exists('injected', $saved))->toBeFalse()
        ->and(settings()->section(HardeningModule::KEY))->toHaveKey('enabled', true)
        ->and(AegisSetting::query()->where('section', HardeningModule::KEY)->exists())->toBeTrue();

    expect($dispatched)->toBe([HardeningModule::KEY]);
});

it('refuses invalid values', function (array $values): void {
    /** @var array<string, mixed> $values */
    settings()->save(HardeningModule::KEY, $values + new HardeningModule()->defaults());
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
    /** @var MockInterface&Module $module */
    $module = Mockery::mock(Module::class);
    $module->allows('key')->andReturn('Bad Key');

    resolve(ModuleRegistry::class)->register($module);
})->throws(InvalidArgumentException::class);

it('reads a saved value written by another process once the cache is cleared', function (): void {
    expect(settings()->section(HardeningModule::KEY))->toHaveKey('enabled', false);

    AegisSetting::query()->create(['section' => HardeningModule::KEY, 'values' => ['enabled' => true]]);

    expect(settings()->section(HardeningModule::KEY))->toHaveKey('enabled', true);
});

it('falls back to the database when the cache cannot be read', function (): void {
    AegisSetting::query()->create(['section' => HardeningModule::KEY, 'values' => ['enabled' => true]]);
    settings()->flush();

    /** @var CacheRepository&MockInterface $cache */
    $cache = Mockery::mock(CacheRepository::class);
    $cache->allows('remember')->andThrow(new RuntimeException('cache down'));
    $repository = new SettingsRepository(resolve(ModuleRegistry::class), $cache, resolve('validator'), resolve('events'));

    expect($repository->section(HardeningModule::KEY))->toHaveKey('enabled', true);
});

it('answers the defaults before the table is migrated', function (): void {
    Schema::drop('aegis_settings');
    settings()->flush();

    expect(settings()->section(HardeningModule::KEY))->toHaveKey('enabled', false);
});

it('lets a module add its own section', function (): void {
    Aegis::module(new class () implements Module {
        #[Override]
        public function key(): string
        {
            return 'acme-module';
        }

        #[Override]
        public function label(): string
        {
            return 'Acme';
        }

        #[Override]
        public function description(): string
        {
            return '';
        }

        #[Override]
        public function defaults(): array
        {
            return ['enabled' => false];
        }

        #[Override]
        public function rules(): array
        {
            return ['enabled' => ['required', 'boolean']];
        }

        #[Override]
        public function fields(): array
        {
            return [Field::toggle('enabled', 'Enabled')];
        }

        #[Override]
        public function status(array $values): ?CheckResult
        {
            return null;
        }
    });

    settings()->save('acme-module', ['enabled' => true]);

    expect(Aegis::settings('acme-module'))->toBe(['enabled' => true]);
});

it('saves a section for a module through the public API', function (): void {
    $dispatched = [];
    Event::listen(SettingsSaved::class, static function (SettingsSaved $event) use (&$dispatched): void {
        $dispatched[] = $event->section;
    });

    $saved = Aegis::save(HardeningModule::KEY, array_replace(Aegis::settings(HardeningModule::KEY), ['enabled' => true, 'password_min_length' => 16]));

    expect($saved)->toMatchArray(['enabled' => true, 'password_min_length' => 16])
        ->and(Aegis::settings(HardeningModule::KEY))->toMatchArray(['enabled' => true, 'password_min_length' => 16])
        ->and($dispatched)->toBe([HardeningModule::KEY]);
});

it('refuses through the public API what the module rules refuse', function (): void {
    Aegis::save(HardeningModule::KEY, array_replace(Aegis::settings(HardeningModule::KEY), ['password_min_length' => 2]));
})->throws(ValidationException::class);

it('tells the listeners only once the application commits its transaction', function (): void {
    $dispatched = [];
    Event::listen(SettingsSaved::class, static function (SettingsSaved $event) use (&$dispatched): void {
        $dispatched[] = $event->section;
    });

    DB::transaction(static function () use (&$dispatched): void {
        settings()->save(HardeningModule::KEY, new HardeningModule()->defaults());

        expect($dispatched)->toBe([]);
    });

    expect($dispatched)->toBe([HardeningModule::KEY]);

    try {
        DB::transaction(static function (): never {
            settings()->save(HardeningModule::KEY, new HardeningModule()->defaults());

            throw new RuntimeException('rolled back');
        });
    } catch (RuntimeException) {
    }

    expect($dispatched)->toBe([HardeningModule::KEY]);
});
