<?php

declare(strict_types=1);

namespace Laravel\Nova;

trait Makeable
{
    /**
     * @return static
     */
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }
}
