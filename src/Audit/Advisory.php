<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use JsonSerializable;
use Override;

final readonly class Advisory implements JsonSerializable
{
    public function __construct(
        public string $package,
        public string $title,
        public ?string $cve = null,
        public ?string $link = null,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data, string $package = ''): self
    {
        return new self(
            self::text($data, 'package') ?? self::text($data, 'packageName') ?? $package,
            self::text($data, 'title') ?? '',
            self::text($data, 'cve'),
            self::text($data, 'link'),
        );
    }

    /**
     * @return array{package: string, title: string, cve: string|null, link: string|null}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'package' => $this->package,
            'title' => $this->title,
            'cve' => $this->cve,
            'link' => $this->link,
        ];
    }

    /**
     * @param array<mixed> $data
     */
    private static function text(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
