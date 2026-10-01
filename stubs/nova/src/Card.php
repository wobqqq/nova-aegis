<?php

declare(strict_types=1);

namespace Laravel\Nova;

abstract class Card extends Element
{
    public const FULL_WIDTH = 'full';

    public const ONE_THIRD_WIDTH = '1/3';

    public const ONE_HALF_WIDTH = '1/2';

    public const ONE_QUARTER_WIDTH = '1/4';

    public const TWO_THIRDS_WIDTH = '2/3';

    public const THREE_QUARTERS_WIDTH = '3/4';

    public const FIXED_HEIGHT = 'fixed';

    public const DYNAMIC_HEIGHT = 'dynamic';

    /**
     * @var string
     */
    public $width = self::ONE_THIRD_WIDTH;

    /**
     * @var string
     */
    public $height = self::FIXED_HEIGHT;

    /**
     * @return $this
     */
    public function width(string $width)
    {
        $this->width = $width;

        if ($width === static::FULL_WIDTH) {
            $this->height = static::DYNAMIC_HEIGHT;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function height(string $height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return $this
     */
    public function dynamicHeight()
    {
        return $this->height(static::DYNAMIC_HEIGHT);
    }

    /**
     * @return $this
     */
    public function fixedHeight()
    {
        return $this->height(static::FIXED_HEIGHT);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(['width' => $this->width, 'height' => $this->height], parent::jsonSerialize());
    }
}
