<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Aegis\Hardening\HardeningService;

final readonly class TransportSecurity
{
    public function __construct(private HardeningService $hardening)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = $this->hardening->settings();

        if (!$settings->enabled) {
            return $next($request);
        }

        if ($settings->forceHttps && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        if ($settings->hsts && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                sprintf('max-age=%d%s', $settings->hstsMaxAge, $settings->hstsIncludeSubdomains ? '; includeSubDomains' : ''),
            );
        }

        return $response;
    }
}
