<?php

declare(strict_types=1);

namespace Laravel\Nova;

trait Metable
{
    /**
     * @var array<string, mixed>
     */
    public $meta = [];

    /**
     * @return array<string, mixed>
     */
    public function meta()
    {
        $request = request();

        return array_map(static fn (mixed $value): mixed => value($value, $request), $this->meta);
    }

    /**
     * @param array<string, mixed> $meta
     *
     * @return $this
     */
    public function withMeta(array $meta)
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }
}
