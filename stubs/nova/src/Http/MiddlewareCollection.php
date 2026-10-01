<?php

declare(strict_types=1);

namespace Laravel\Nova\Http;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * @template TKey of int
 * @template TValue of string|class-string
 *
 * @extends Collection<TKey, TValue>
 */
class MiddlewareCollection extends Collection
{
    /**
     * @return $this
     */
    public function appendsRedirectIfAuthenticatedMiddleware()
    {
        $items = [];

        foreach ($this->items as $middleware) {
            $items[] = $middleware;

            if ($middleware === 'web') {
                $items[] = 'nova.guest';
            }
        }

        if (!in_array('web', $this->items, true)) {
            $items[] = 'nova.guest';
        }

        $this->items = $items;

        return $this;
    }

    public function asMiddlewareGroup(string $name): void
    {
        Route::middlewareGroup($name, $this->all());
    }
}
