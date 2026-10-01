<?php

declare(strict_types=1);

namespace Laravel\Nova\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Laravel\Nova\Exceptions\AuthenticationException as NovaAuthenticationException;

class Authenticate extends Middleware
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param string ...$guards
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guard = config('nova.guard');

        if (is_string($guard) && $guard !== '') {
            $guards[] = $guard;
        }

        try {
            return parent::handle($request, $next, ...$guards);
        } catch (AuthenticationException $e) {
            throw new NovaAuthenticationException('Unauthenticated.', $e->guards());
        }
    }
}
