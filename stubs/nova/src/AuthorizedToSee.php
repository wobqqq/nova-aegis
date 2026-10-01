<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Closure;
use Illuminate\Http\Request;

trait AuthorizedToSee
{
    /**
     * @var (Closure(Request):bool)|null
     */
    public $seeCallback = null;

    /**
     * @return bool
     */
    public function authorizedToSee(Request $request)
    {
        return $this->seeCallback === null || (bool)($this->seeCallback)($request);
    }

    /**
     * @param Closure(Request):bool $callback
     *
     * @return $this
     */
    public function canSee(Closure $callback)
    {
        $this->seeCallback = $callback;

        return $this;
    }
}
