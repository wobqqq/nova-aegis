<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Laravel\Nova\Events\ServingNova;

class DispatchServingNovaEvent
{
    /**
     * @param Request $request
     * @param Closure(Request):mixed $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        $app = Container::getInstance();

        if ($app instanceof Application) {
            ServingNova::dispatch($app, $request);
        }

        return $next($request);
    }
}
