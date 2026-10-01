<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Wobqqq\Aegis\Audit\AuditStore;
use Wobqqq\Aegis\Audit\ComposerAudit;
use Wobqqq\Aegis\Checks\CheckRunner;

final readonly class OverviewController
{
    public function __construct(private CheckRunner $runner, private AuditStore $audits)
    {
    }

    public function show(): JsonResponse
    {
        return new JsonResponse([
            'checks' => $this->runner->checks(),
            'modules' => $this->runner->modules(),
            'audit' => $this->audits->last(),
        ]);
    }

    public function audit(ComposerAudit $audit): JsonResponse
    {
        return new JsonResponse(['audit' => $audit->run()]);
    }
}
