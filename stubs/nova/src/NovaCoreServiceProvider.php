<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Http\Middleware\CheckResponseForModifications;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Middleware\Authenticate;
use Laravel\Nova\Http\Middleware\BootTools;
use Laravel\Nova\Http\Middleware\DispatchServingNovaEvent;
use Laravel\Nova\Http\Middleware\RedirectIfAuthenticated;
use Laravel\Nova\Http\Middleware\ServeNova;
use Laravel\Nova\Http\MiddlewareCollection;

class NovaCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!defined('NOVA_PATH')) {
            define('NOVA_PATH', dirname(__DIR__));
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->register(NovaServiceProvider::class);
        }

        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__ . '/../config/nova.php', 'nova');
        }

        Route::aliasMiddleware('nova.guest', RedirectIfAuthenticated::class);
        Route::aliasMiddleware('nova.auth', Authenticate::class);
        Route::middlewareGroup('nova:serving', [DispatchServingNovaEvent::class, BootTools::class]);

        $middleware = $this->middleware('nova.middleware', []);

        (new MiddlewareCollection($middleware))->asMiddlewareGroup('nova');
        (new MiddlewareCollection($this->middleware('nova.api_middleware', [])))->asMiddlewareGroup('nova:api');
        (new MiddlewareCollection($this->middleware('nova.asset_middleware', ['nova:api', CheckResponseForModifications::class])))->asMiddlewareGroup('nova:asset');
        (new MiddlewareCollection($middleware))->appendsRedirectIfAuthenticatedMiddleware()->asMiddlewareGroup('nova:auth');

        $kernel = $this->app->make(HttpKernel::class);

        if ($kernel instanceof Kernel) {
            $kernel->pushMiddleware(ServeNova::class);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nova');
        $this->registerRoutes();

        Nova::serving(static function (ServingNova $event): void {
            Nova::provideToScript([
                'appName' => Nova::name(),
                'base' => Nova::path(),
                'locale' => $event->app->getLocale(),
                'timezone' => config('app.timezone', 'UTC'),
                'version' => Nova::version(),
            ]);
        });
    }

    private function registerRoutes(): void
    {
        Route::group(['prefix' => 'nova-api'], function (Router $router): void {
            foreach (['asset', 'api'] as $group) {
                $router->group([
                    'domain' => config('nova.domain'),
                    'as' => "nova.{$group}.",
                    'middleware' => "nova:{$group}",
                    'excluded_middleware' => [SubstituteBindings::class],
                ], function () use ($group): void {
                    $this->loadRoutesFrom(__DIR__ . "/../routes/{$group}.php");
                });
            }
        });
    }

    /**
     * @param list<string> $default
     *
     * @return list<string>
     */
    private function middleware(string $key, array $default): array
    {
        $middleware = config($key, $default);

        return is_array($middleware) ? array_values(array_filter($middleware, is_string(...))) : $default;
    }
}
