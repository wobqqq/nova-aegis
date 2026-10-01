<?php

declare(strict_types=1);

namespace Laravel\Nova;

trait WithComponent
{
    /**
     * @return string
     */
    public function component()
    {
        return $this->component;
    }

    /**
     * @return $this
     */
    public function withComponent(string $component)
    {
        $this->component = $component;

        return $this;
    }
}
