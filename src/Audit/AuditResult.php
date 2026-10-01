<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use Illuminate\Support\Carbon;
use JsonSerializable;

final readonly class AuditResult implements JsonSerializable
{
    /**
     * @param list<array{package: string, title: string, cve: string|null, link: string|null}> $advisories
     * @param list<string> $abandoned
     */
    public function __construct(
        public Carbon $ranAt,
        public array $advisories,
        public array $abandoned,
        public ?string $error = null,
    ) {
    }

    public static function failed(string $error): self
    {
        return new self(\Illuminate\Support\Facades\Date::now(), [], [], $error);
    }

    /**
     * @param array<mixed> $report composer audit --format=json
     */
    public static function fromReport(array $report): self
    {
        $advisories = [];

        foreach (is_array($report['advisories'] ?? null) ? $report['advisories'] : [] as $package => $list) {
            foreach (is_array($list) ? $list : [] as $advisory) {
                if (!is_array($advisory)) {
                    continue;
                }

                $advisories[] = [
                    'package' => is_string($advisory['packageName'] ?? null) ? $advisory['packageName'] : (string)$package,
                    'title' => is_string($advisory['title'] ?? null) ? $advisory['title'] : '',
                    'cve' => is_string($advisory['cve'] ?? null) ? $advisory['cve'] : null,
                    'link' => is_string($advisory['link'] ?? null) ? $advisory['link'] : null,
                ];
            }
        }

        $abandoned = is_array($report['abandoned'] ?? null) ? array_map(strval(...), array_keys($report['abandoned'])) : [];

        return new self(\Illuminate\Support\Facades\Date::now(), $advisories, array_values($abandoned));
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
                $advisories[] = [
                    'package' => $advisory['package'],
                    'title' => is_string($advisory['title'] ?? null) ? $advisory['title'] : '',
                    'cve' => is_string($advisory['cve'] ?? null) ? $advisory['cve'] : null,
                    'link' => is_string($advisory['link'] ?? null) ? $advisory['link'] : null,
                ];
            }
        }

        $abandoned = array_values(array_filter(is_array($data['abandoned'] ?? null) ? $data['abandoned'] : [], is_string(...)));

        return new self(
            \Illuminate\Support\Facades\Date::parse($data['ran_at']),
            $advisories,
            $abandoned,
            is_string($data['error'] ?? null) ? $data['error'] : null,
        );
    }

    /**
     * @return array{ran_at: string, advisories: list<array{package: string, title: string, cve: string|null, link: string|null}>, abandoned: list<string>, error: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'ran_at' => $this->ranAt->toIso8601String(),
            'advisories' => $this->advisories,
            'abandoned' => $this->abandoned,
            'error' => $this->error,
        ];
    }
}
