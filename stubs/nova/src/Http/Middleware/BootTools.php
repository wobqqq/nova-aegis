<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;

class BootTools
{
    /**
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        Nova::bootTools($request);

        return $next($request);
    }
}
