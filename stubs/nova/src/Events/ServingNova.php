<?php

declare(strict_types=1);

namespace Laravel\Nova\Events;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class ServingNova
{
    use Dispatchable;

    public function __construct(public Application $app, public Request $request)
    {
    }
}
