<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Wobqqq\Aegis\Http\Requests\SensitiveFilesScanRequest;
use Wobqqq\Aegis\Http\Requests\TcpPortsScanRequest;
use Wobqqq\Aegis\Http\Requests\TlsCertificatesScanRequest;
use Wobqqq\Aegis\Scanners\Scanner;
use Wobqqq\Aegis\Scanners\ScanResult;

final readonly class ScanController
{
    public function __construct(private Scanner $scanner)
    {
    }

    public function sensitiveFiles(SensitiveFilesScanRequest $request): JsonResponse
    {
        return $this->answer($this->scanner->sensitiveFiles($request->target()));
    }

    public function tcpPorts(TcpPortsScanRequest $request): JsonResponse
    {
        return $this->answer($this->scanner->tcpPorts($request->target()));
    }

    public function tlsCertificates(TlsCertificatesScanRequest $request): JsonResponse
    {
        return $this->answer($this->scanner->tlsCertificates($request->target()));
    }

    /**
     * @param list<ScanResult> $results
     */
    private function answer(array $results): JsonResponse
    {
        return new JsonResponse([
            'results' => $results,
            'exposed' => count(array_filter($results, static fn (ScanResult $result): bool => $result->exposed)),
        ]);
    }
}
