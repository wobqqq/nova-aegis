<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Aegis\Nova\AegisTool;

final class Authorize
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tool = collect(Nova::registeredTools())->first(static fn (Tool $tool): bool => $tool instanceof AegisTool);

        abort_if($tool === null, 404);
        abort_unless($tool->authorize($request), 403);

        return $next($request);
    }
}
