<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;

class Authorize
{
    /**
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        abort_unless(Nova::check($request), 403);

        return $next($request);
    }
}
