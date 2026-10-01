<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks;

use JsonSerializable;
use Override;
use Wobqqq\Aegis\Enums\Status;

final readonly class CheckResult implements JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public Status $status,
        public string $message,
    ) {
    }

    public static function pass(string $key, string $label, string $message): self
    {
        return new self($key, $label, Status::PASS, $message);
    }

    public static function warn(string $key, string $label, string $message): self
    {
        return new self($key, $label, Status::WARN, $message);
    }

    public static function fail(string $key, string $label, string $message): self
    {
        return new self($key, $label, Status::FAIL, $message);
    }

    public static function info(string $key, string $label, string $message): self
    {
        return new self($key, $label, Status::INFO, $message);
    }

    /**
     * @return array{key: string, label: string, status: string, message: string}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
