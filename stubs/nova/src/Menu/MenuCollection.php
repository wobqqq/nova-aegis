<?php

declare(strict_types=1);

namespace Laravel\Nova\Menu;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, mixed>
 */
class MenuCollection extends Collection
{
    /**
     * @return static
     */
    public function authorized(Request $request)
    {
        return $this->filter(static fn (mixed $item): bool => !is_object($item) || !method_exists($item, 'authorizedToSee') || $item->authorizedToSee($request) === true)->values();
    }

    /**
     * @return static
     */
    public function withoutEmptyItems()
    {
        return $this->reject(static fn (mixed $item): bool => $item instanceof MenuSection && $item->path === null && $item->items->isEmpty())->values();
    }
}
