<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Middleware;
use Laravel\Nova\Nova;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'nova::layout';

    /**
     * @return string|null
     */
    public function version(Request $request)
    {
        return $this->rootView . ':' . parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'novaConfig' => static fn (): array => Nova::jsonVariables($request),
            'currentUser' => static function () use ($request): ?array {
                $user = Nova::user($request);

                return $user === null ? null : ['id' => $user->getAuthIdentifier()];
            },
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        Config::set('inertia.ssr.enabled', false);

        return parent::handle($request, $next);
    }
}
