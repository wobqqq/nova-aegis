<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;

/**
 * @method static static make(string|null $component = null)
 */
abstract class Element implements JsonSerializable
{
    use AuthorizedToSee;
    use Macroable;
    use Makeable;
    use Metable;
    use WithComponent;

    /**
     * @var string
     */
    public $component;

    /**
     * @var bool
     */
    public $onlyOnDetail = false;

    public function __construct(?string $component = null)
    {
        $this->component = $component ?? $this->component;
    }

    /**
     * @return bool
     */
    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    /**
     * @return $this
     */
    public function onlyOnDetail()
    {
        $this->onlyOnDetail = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'component' => $this->component(),
            'prefixComponent' => false,
            'onlyOnDetail' => $this->onlyOnDetail,
        ], $this->meta());
    }
}
