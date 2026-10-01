<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Console;

use Illuminate\Console\Command;
use Wobqqq\Aegis\Audit\Advisory;
use Wobqqq\Aegis\Audit\ComposerAudit;

final class AuditCommand extends Command
{
    protected $signature = 'aegis:audit';

    protected $description = 'Check the installed packages for security advisories and keep the result for the Aegis dashboard.';

    public function handle(ComposerAudit $audit): int
    {
        $result = $audit->run();

        if ($result->error !== null) {
            $this->components->error($result->error);

            return self::FAILURE;
        }

        if ($result->advisories === []) {
            $this->components->info('No security advisories.');

            return self::SUCCESS;
        }

        $this->table(['Package', 'Advisory', 'CVE'], array_map(
            static fn (Advisory $advisory): array => [$advisory->package, $advisory->title, $advisory->cve ?? '-'],
            $result->advisories,
        ));

        return self::FAILURE;
    }
}
