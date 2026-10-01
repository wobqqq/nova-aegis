<?php

declare(strict_types=1);

namespace Laravel\Nova;

/**
 * @property string|null $icon
 */
trait WithIcon
{
    /**
     * @param string|null $icon
     *
     * @return $this
     */
    public function withIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }
}
