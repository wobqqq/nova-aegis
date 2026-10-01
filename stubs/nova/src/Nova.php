<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Closure;
use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Events\ServingNova;
use Stringable;

class Nova
{
    /**
     * @var array<int, Tool>
     */
    public static array $tools = [];

    /**
     * @var array<int, Script>
     */
    public static array $scripts = [];

    /**
     * @var array<int, Style>
     */
    public static array $styles = [];

    /**
     * @var array<string, mixed>
     */
    public static array $jsonVariables = [];

    /**
     * @var (Closure(Request):bool)|null
     */
    public static $authUsing;

    public static function version(): string
    {
        return (InstalledVersions::getPrettyVersion('laravel/nova') ?? '5.x') . ' (test double)';
    }

    public static function name(): Stringable|string
    {
        $name = config('nova.name');

        return is_string($name) ? $name : 'Nova Site';
    }

    public static function path(): string
    {
        $path = config('nova.path', '/nova');

        return '/' . ltrim(is_string($path) ? $path : '/nova', '/');
    }

    public static function url(?string $url = null): string
    {
        return rtrim(static::path(), '/') . '/' . ltrim((string)$url, '/');
    }

    /**
     * @param array<int, class-string|string>|null $middleware
     */
    public static function router(?array $middleware = null, ?string $prefix = null): RouteRegistrar
    {
        return Route::domain(config('nova.domain'))
            ->prefix(static::url($prefix))
            ->middleware($middleware ?? config('nova.middleware', []));
    }

    /**
     * @param array<int, mixed>|(Closure(NovaServiceProviderRegistered):void)|string $callback
     *
     * @return void
     */
    public static function booted(Closure|string|array $callback)
    {
        Event::listen(NovaServiceProviderRegistered::class, $callback);
    }

    /**
     * @param array<int, mixed>|(Closure(ServingNova):void)|string $callback
     *
     * @return void
     */
    public static function serving(Closure|string|array $callback)
    {
        Event::listen(ServingNova::class, $callback);
    }

    /**
     * @return void
     */
    public static function flushState()
    {
        static::$authUsing = null;
        static::$jsonVariables = [];
        static::$scripts = [];
        static::$styles = [];
        static::$tools = [];
    }

    /**
     * @param array<int, Tool> $tools
     */
    public static function tools(array $tools): static
    {
        static::$tools = array_merge(static::$tools, $tools);

        return new static();
    }

    /**
     * @return array<int, Tool>
     */
    public static function registeredTools(): array
    {
        return static::$tools;
    }

    /**
     * @return array<int, Tool>
     */
    public static function availableTools(Request $request): array
    {
        if (static::user($request) === null) {
            return [];
        }

        return array_values(array_filter(static::$tools, static fn (Tool $tool): bool => $tool->authorize($request)));
    }

    public static function bootTools(Request $request): void
    {
        foreach (static::availableTools($request) as $tool) {
            $tool->boot();
        }
    }

    public static function script(Script|string $name, string $path): static
    {
        static::$scripts[] = new Script($name, $path);

        return new static();
    }

    public static function remoteScript(string $path): static
    {
        return static::script(Script::remote($path), $path);
    }

    public static function style(Style|string $name, string $path): static
    {
        static::$styles[] = new Style($name, $path);

        return new static();
    }

    public static function remoteStyle(string $path): static
    {
        return static::style(Style::remote($path), $path);
    }

    /**
     * @return array<int, Script>
     */
    public static function allScripts(): array
    {
        return static::$scripts;
    }

    /**
     * @return array<int, Style>
     */
    public static function allStyles(): array
    {
        return static::$styles;
    }

    /**
     * @param array<string, mixed> $variables
     */
    public static function provideToScript(array $variables): static
    {
        static::$jsonVariables = array_merge(static::$jsonVariables, $variables);

        return new static();
    }

    /**
     * @return array<string, mixed>
     */
    public static function jsonVariables(Request $request): array
    {
        return array_map(static fn (mixed $value): mixed => $value instanceof Closure ? $value($request) : $value, static::$jsonVariables);
    }

    /**
     * @return \Illuminate\Foundation\Auth\User|null
     */
    public static function user(?Request $request = null)
    {
        return ($request ?? request())->user(Util::userGuard());
    }

    /**
     * @param Closure(Request):bool $callback
     */
    public static function auth(Closure $callback): static
    {
        static::$authUsing = $callback;

        return new static();
    }

    /**
     * @param Request $request
     */
    public static function check($request): bool
    {
        return static::$authUsing === null ? app()->environment('local') : (bool)(static::$authUsing)($request);
    }
}
