<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Nova;
use Laravel\Nova\Util;

class RedirectIfAuthenticated
{
    /**
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ?string $guard = null)
    {
        return Auth::guard(Util::userGuard())->check() ? redirect(Nova::path()) : $next($request);
    }
}
