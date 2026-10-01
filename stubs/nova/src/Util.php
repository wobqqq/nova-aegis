<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Illuminate\Http\Request;

class Util
{
    public static function isNovaRequest(Request $request): bool
    {
        $domain = config('nova.domain');
        $path = trim(Nova::path(), '/');

        if (is_string($domain) && $domain !== '' && $domain !== config('app.url') && $path === '') {
            $host = parse_url(str_contains($domain, '://') ? $domain : $request->getScheme() . '://' . $domain);
            $port = $host['port'] ?? (in_array($request->getPort(), [80, 443], true) ? null : $request->getPort());
            $expected = ($host['host'] ?? '') . ($port === null ? '' : ':' . $port);

            return rtrim($request->getHttpHost(), '/') === $expected;
        }

        $path = $path === '' ? '/' : $path;

        return $request->is($path, trim($path . '/*', '/'), 'nova-api/*', 'nova-vendor/*');
    }

    public static function userGuard(): string
    {
        $guard = config('nova.guard') ?? config('auth.defaults.guard');

        return is_string($guard) ? $guard : 'web';
    }
}
