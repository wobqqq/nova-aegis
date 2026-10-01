<?php

declare(strict_types=1);

namespace Laravel\Nova;

use JsonSerializable;
use Stringable;

class URL implements JsonSerializable, Stringable
{
    use Makeable;

    /**
     * @var string|null
     */
    public $url;

    /**
     * @var bool
     */
    public $remote;

    public function __construct(self|string|null $url, bool $remote = false)
    {
        [$this->url, $this->remote] = $url instanceof self ? [$url->url, $url->remote] : [$url, $remote];
    }

    public static function remote(string $url): static
    {
        return new static($url, true);
    }

    public function get(): ?string
    {
        return $this->remote ? $this->url : Nova::url($this->url);
    }

    public function active(): bool
    {
        $path = ltrim((string)$this->get(), '/');

        return request()->is($path, rtrim($path, '/') . '/*');
    }

    public function __toString(): string
    {
        return (string)$this->get();
    }

    /**
     * @return array{url: string|null, remote: bool}
     */
    public function jsonSerialize(): array
    {
        return ['url' => $this->get(), 'remote' => $this->remote];
    }
}
