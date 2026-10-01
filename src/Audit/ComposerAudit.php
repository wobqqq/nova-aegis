<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Audit;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Process;
use Throwable;

final readonly class ComposerAudit
{
    public function __construct(private Config $config, private AuditStore $store)
    {
    }

    public function run(): AuditResult
    {
        $binary = $this->config->get('aegis.audit.binary');
        $timeout = $this->config->get('aegis.audit.timeout');

        try {
            $process = Process::path(base_path())
                ->timeout(is_numeric($timeout) ? (int)$timeout : 120)
                ->run([is_string($binary) && $binary !== '' ? $binary : 'composer', 'audit', '--format=json', '--locked', '--no-interaction', '--abandoned=report']);

            $report = json_decode($process->output(), true);
            $result = is_array($report)
                ? AuditResult::fromReport($report)
                : AuditResult::failed(trim($process->errorOutput()) !== '' ? 'composer audit did not answer with JSON.' : 'composer audit answered nothing.');
        } catch (Throwable $e) {
            $result = AuditResult::failed(sprintf('composer audit could not run: %s', class_basename($e)));
        }

        $this->store->put($result);

        return $result;
    }
}
