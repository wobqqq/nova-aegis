<?php

declare(strict_types=1);

namespace Laravel\Nova\Exceptions;

use Illuminate\Auth\AuthenticationException as BaseAuthenticationException;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends BaseAuthenticationException
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage(), 'redirect' => $this->location()], 401);
        }

        if ($request->is('nova-api/*', 'nova-vendor/*')) {
            return response(null, 401);
        }

        return redirect()->guest($this->location());
    }

    protected function location(): string
    {
        return Nova::url('login');
    }
}
