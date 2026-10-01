<?php

declare(strict_types=1);

namespace Laravel\Nova\Menu;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Exceptions\NovaException;
use Laravel\Nova\Makeable;
use Laravel\Nova\URL;
use Laravel\Nova\WithComponent;
use Laravel\Nova\WithIcon;
use Stringable;

/**
 * @method static static make(Stringable|string $name, iterable<mixed> $items = [], string $icon = 'collection')
 */
class MenuSection implements JsonSerializable
{
    use AuthorizedToSee;
    use Conditionable;
    use Macroable;
    use Makeable;
    use WithComponent;
    use WithIcon;

    /**
     * @var string
     */
    public $component = 'menu-section';

    /**
     * @var string|null
     */
    public $icon;

    /**
     * @var string|URL|null
     */
    public $path = null;

    /**
     * @var bool
     */
    public $collapsable = false;

    /**
     * @var bool
     */
    public $collapsedByDefault = false;

    public MenuCollection $items;

    /**
     * @param iterable<mixed> $items
     */
    public function __construct(public Stringable|string $name, iterable $items = [], ?string $icon = 'collection')
    {
        $this->items = new MenuCollection($items);
        $this->withIcon($icon);
    }

    /**
     * @throws NovaException
     *
     * @return $this
     */
    public function path(URL|string|null $href)
    {
        if ($this->collapsable) {
            throw new NovaException('A menu section with a path cannot be collapsable.');
        }

        $this->path = $href;

        return $this;
    }

    /**
     * @throws NovaException
     *
     * @return $this
     */
    public function collapsable()
    {
        if ($this->path !== null) {
            throw new NovaException('A menu section with a path cannot be collapsable.');
        }

        $this->collapsable = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function collapsedByDefault()
    {
        $this->collapsable();
        $this->collapsedByDefault = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function icon(string $icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $url = $this->path === null || $this->path === '' ? null : URL::make($this->path);

        return [
            'active' => $url?->active() ?? false,
            'badge' => null,
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'component' => $this->component,
            'icon' => $this->icon,
            'items' => $this->items->authorized(request())->withoutEmptyItems()->all(),
            'key' => md5($this->name . '-' . $this->path),
            'name' => $this->name,
            'path' => (string)$url,
        ];
    }
}
