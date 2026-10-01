<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Events\NovaServiceProviderRegistered;
use Laravel\Nova\Util;

class ServeNova
{
    /**
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (Util::isNovaRequest($request)) {
            NovaServiceProviderRegistered::dispatch();
        }

        return $next($request);
    }
}
