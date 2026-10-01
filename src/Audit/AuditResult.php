<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use JsonSerializable;
use Override;

final readonly class AuditResult implements JsonSerializable
{
    /**
     * @param list<Advisory> $advisories
     * @param list<string> $abandoned
     */
    public function __construct(
        public CarbonInterface $ranAt,
        public array $advisories,
        public array $abandoned,
        public ?string $error = null,
    ) {
    }

    public static function failed(string $error): self
    {
        return new self(Date::now(), [], [], $error);
    }

    /**
     * @param array<mixed> $report composer audit --format=json
     */
    public static function fromReport(array $report): self
    {
        $advisories = [];

        foreach (is_array($report['advisories'] ?? null) ? $report['advisories'] : [] as $package => $list) {
            foreach (is_array($list) ? $list : [] as $advisory) {
                if (is_array($advisory)) {
                    $advisories[] = Advisory::fromArray($advisory, (string)$package);
                }
            }
        }

        $abandoned = is_array($report['abandoned'] ?? null) ? array_map(strval(...), array_keys($report['abandoned'])) : [];

        return new self(Date::now(), $advisories, array_values($abandoned));
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        if (!is_string($data['ran_at'] ?? null)) {
            return null;
        }

        $advisories = [];

        foreach (is_array($data['advisories'] ?? null) ? $data['advisories'] : [] as $advisory) {
            if (is_array($advisory) && is_string($advisory['package'] ?? null)) {
                $advisories[] = Advisory::fromArray($advisory);
            }
        }

        $abandoned = array_values(array_filter(is_array($data['abandoned'] ?? null) ? $data['abandoned'] : [], is_string(...)));

        return new self(
            Date::parse($data['ran_at']),
            $advisories,
            $abandoned,
            is_string($data['error'] ?? null) ? $data['error'] : null,
        );
    }

    /**
     * @return list<string>
     */
    public function packages(): array
    {
        return array_values(array_unique(array_map(static fn (Advisory $advisory): string => $advisory->package, $this->advisories)));
    }

    /**
     * @return array{ran_at: string, advisories: list<array{package: string, title: string, cve: string|null, link: string|null}>, abandoned: list<string>, error: string|null}
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'ran_at' => $this->ranAt->toIso8601String(),
            'advisories' => array_map(static fn (Advisory $advisory): array => $advisory->jsonSerialize(), $this->advisories),
            'abandoned' => $this->abandoned,
            'error' => $this->error,
        ];
    }
}
