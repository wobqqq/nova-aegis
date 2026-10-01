<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Console;

use Illuminate\Console\Command;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Checks\CheckRunner;
use Wobqqq\Aegis\Enums\Status;

final class CheckCommand extends Command
{
    protected $signature = 'aegis:check {--strict : Fail on warnings too}';

    protected $description = 'Run the Aegis security checks; the exit code is non-zero when one fails.';

    public function handle(CheckRunner $runner): int
    {
        $results = [...$runner->checks(), ...$runner->modules()];

        $this->table(['Status', 'Check', 'Result'], array_map(
            static fn (CheckResult $result): array => [strtoupper($result->status->value), $result->label, $result->message],
            $results,
        ));

        $failing = array_filter($results, fn (CheckResult $result): bool => $result->status === Status::FAIL
            || ($this->option('strict') === true && $result->status === Status::WARN));

        return $failing === [] ? self::SUCCESS : self::FAILURE;
    }
}
