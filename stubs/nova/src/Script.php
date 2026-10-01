<?php

declare(strict_types=1);

namespace Laravel\Nova;

class Script extends Asset
{
    public function url(): string
    {
        return $this->remote ? (string)$this->path : '/nova-api/scripts/' . $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function toResponseHeaders(): array
    {
        return ['Content-Type' => 'application/javascript'];
    }
}
