<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use JsonSerializable;
use Override;

final readonly class ScanResult implements JsonSerializable
{
    public function __construct(
        public string $target,
        public string $status,
        public bool $exposed,
        public ?string $detail = null,
    ) {
    }

    /**
     * @return array{target: string, status: string, exposed: bool, detail: string|null}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'target' => $this->target,
            'status' => $this->status,
            'exposed' => $this->exposed,
            'detail' => $this->detail,
        ];
    }
}
