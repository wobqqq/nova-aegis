<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wobqqq\Aegis\Scanners\Scanner;
use Wobqqq\Aegis\Scanners\ScanResult;
use Wobqqq\Aegis\Support\Lang;

final readonly class ScanController
{
    public function __construct(private Scanner $scanner)
    {
    }

    public function sensitiveFiles(Request $request): JsonResponse
    {
        $target = $this->target($request, 'url', ['required', 'string', 'max:255', 'url:http,https']);

        return $this->answer($this->scanner->sensitiveFiles($target));
    }

    public function tcpPorts(Request $request): JsonResponse
    {
        $target = $this->target($request, 'host', ['required', 'string', 'max:255', 'ip']);

        return $this->answer($this->scanner->tcpPorts($target));
    }

    public function tlsCertificates(Request $request): JsonResponse
    {
        $target = $this->target($request, 'host', ['required', 'string', 'max:255']);

        return $this->answer($this->scanner->tlsCertificates($target));
    }

    /**
     * @param list<string> $rules
     */
    private function target(Request $request, string $field, array $rules): string
    {
        $request->validate([$field => $rules]);

        return trim($request->string($field)->toString());
    }

    /**
     * @param list<ScanResult>|null $results
     */
    private function answer(?array $results): JsonResponse
    {
        abort_if($results === null, 422, Lang::get('aegis::aegis.scanners.unlisted'));

        return new JsonResponse([
            'results' => $results,
            'exposed' => count(array_filter($results, static fn (ScanResult $result): bool => $result->exposed)),
        ]);
    }
}
