<?php

declare(strict_types=1);

namespace Wobqqq\Aegis;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;
use Throwable;
use Wobqqq\Aegis\Audit\AuditStore;
use Wobqqq\Aegis\Checks\CheckRegistry;
use Wobqqq\Aegis\Checks\Core;
use Wobqqq\Aegis\Console\AuditCommand;
use Wobqqq\Aegis\Console\CheckCommand;
use Wobqqq\Aegis\Console\DisableCommand;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Hardening\HardeningService;
use Wobqqq\Aegis\Http\Middleware\Authorize;
use Wobqqq\Aegis\Http\Middleware\TransportSecurity;
use Wobqqq\Aegis\Modules\ModuleRegistry;
use Wobqqq\Aegis\Scanners\HttpProbe;
use Wobqqq\Aegis\Scanners\ScannersModule;
use Wobqqq\Aegis\Scanners\TcpProbe;
use Wobqqq\Aegis\Scanners\TlsProbe;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\Aegis\Settings\SettingsRepository;

final class AegisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aegis.php', 'aegis');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(CheckRegistry::class);
        $this->app->singleton(HardeningService::class);
        $this->app->singleton(SettingsRepository::class, static fn (Application $app): SettingsRepository => new SettingsRepository(
            $app->make(ModuleRegistry::class),
            self::cache($app),
            $app->make('validator'),
            $app->make('events'),
        ));
        $this->app->singleton(AuditStore::class, static fn (Application $app): AuditStore => new AuditStore(self::cache($app)));
        $this->app->bind(HttpProbe::class, static fn (Application $app): HttpProbe => new HttpProbe(
            null,
            self::integer($app, 'aegis.scanners.http_timeout', 10),
            self::integer($app, 'aegis.scanners.http_concurrency', 7),
        ));
        $this->app->bind(TcpProbe::class, static fn (Application $app): TcpProbe => new TcpProbe(self::integer($app, 'aegis.scanners.tcp_timeout', 2)));
        $this->app->bind(TlsProbe::class, static fn (Application $app): TlsProbe => new TlsProbe(self::integer($app, 'aegis.scanners.tls_timeout', 10)));
    }

    public function boot(ModuleRegistry $modules, CheckRegistry $checks, Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis');

        $modules->register(new HardeningModule());
        $modules->register($this->app->make(ScannersModule::class));

        foreach ([
            Core\DebugModeCheck::class,
            Core\EnvironmentCheck::class,
            Core\AppKeyCheck::class,
            Core\HttpsUrlCheck::class,
            Core\NovaPathCheck::class,
            Core\SessionCookieCheck::class,
            Core\PasswordPolicyCheck::class,
            Core\StaleAdminsCheck::class,
            Core\DependencyAdvisoriesCheck::class,
        ] as $check) {
            $checks->register($this->app->make($check));
        }

        AegisSetting::saved(fn () => $this->app->make(SettingsRepository::class)->flush());
        $this->app->make('events')->listen(SettingsSaved::class, fn () => $this->app->make(HardeningService::class)->forget());

        $router->pushMiddlewareToGroup('web', TransportSecurity::class);
        $this->applyHardening();

        $this->app->booted(function (): void {
            $this->routes();
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/aegis.php' => config_path('aegis.php')], 'aegis-config');
            $this->commands([AuditCommand::class, CheckCommand::class, DisableCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            if ($this->app->make(Config::class)->get('aegis.audit.schedule') === true) {
                $schedule->command('aegis:audit')->daily()->withoutOverlapping()->runInBackground();
            }
        });
    }

    private function applyHardening(): void
    {
        try {
            $this->app->make(HardeningService::class)->apply();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Nova::router(['nova', 'nova.auth', Authorize::class], 'aegis')->group(__DIR__ . '/../routes/inertia.php');

        Route::middleware(['nova', 'nova.auth', Authorize::class])
            ->prefix('nova-vendor/aegis')
            ->group(__DIR__ . '/../routes/api.php');
    }

    private static function cache(Application $app): \Illuminate\Contracts\Cache\Repository
    {
        $store = $app->make(Config::class)->get('aegis.cache_store');

        return $app->make(CacheFactory::class)->store(is_string($store) && $store !== '' ? $store : null);
    }

    private static function integer(Application $app, string $key, int $default): int
    {
        $value = $app->make(Config::class)->get($key);

        return is_numeric($value) && (int)$value > 0 ? (int)$value : $default;
    }
}
