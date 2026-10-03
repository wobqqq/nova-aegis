<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use Carbon\CarbonImmutable;
use Illuminate\Process\Factory as Process;
use Psr\Clock\ClockInterface;
use Throwable;

final readonly class ComposerAudit
{
    public function __construct(
        private Process $process,
        private AuditStore $store,
        private ClockInterface $clock,
        private string $binary,
        private int $timeout,
        private string $workingDirectory,
    ) {
    }

    public function run(): AuditResult
    {
        $ranAt = CarbonImmutable::instance($this->clock->now());

        try {
            $process = $this->process->newPendingProcess()
                ->path($this->workingDirectory)
                ->timeout($this->timeout)
                ->run([$this->binary, 'audit', '--format=json', '--locked', '--no-interaction', '--abandoned=report']);

            $report = json_decode($process->output(), true);
            $result = is_array($report)
                ? AuditResult::fromReport($ranAt, $report)
                : AuditResult::failed($ranAt, trim($process->errorOutput()) !== '' ? 'composer audit did not answer with JSON.' : 'composer audit answered nothing.');
        } catch (Throwable $throwable) {
            $result = AuditResult::failed($ranAt, sprintf('composer audit could not run: %s', class_basename($throwable)));
        }

        $this->store->put($result);

        return $result;
    }
}
