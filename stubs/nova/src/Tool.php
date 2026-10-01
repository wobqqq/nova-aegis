<?php

declare(strict_types=1);

namespace Laravel\Nova;

use Illuminate\Http\Request;

abstract class Tool
{
    use AuthorizedToSee;
    use Makeable;

    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public function authorize(Request $request)
    {
        return $this->authorizedToSee($request);
    }

    /**
     * @return void
     */
    public function boot()
    {
    }

    /**
     * @return mixed
     */
    abstract public function menu(Request $request);
}
