<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Wobqqq\Aegis\Audit\AuditStore;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class DependencyAdvisoriesCheck implements Check
{
    private const STALE_AFTER_DAYS = 7;

    public function __construct(private AuditStore $store)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.advisories.label');
        $result = $this->store->last();

        if (!$result instanceof \Wobqqq\Aegis\Audit\AuditResult) {
            return CheckResult::info('advisories', $label, (string)__('aegis::aegis.checks.advisories.never'));
        }

        if ($result->error !== null) {
            return CheckResult::warn('advisories', $label, (string)__('aegis::aegis.checks.advisories.error'));
        }

        if ($result->advisories !== []) {
            return CheckResult::fail('advisories', $label, (string)__('aegis::aegis.checks.advisories.fail', [
                'count' => count($result->advisories),
                'packages' => implode(', ', array_unique(array_column($result->advisories, 'package'))),
            ]));
        }

        if ($result->ranAt->lt(\Illuminate\Support\Facades\Date::now()->subDays(self::STALE_AFTER_DAYS))) {
            return CheckResult::warn('advisories', $label, (string)__('aegis::aegis.checks.advisories.stale', ['date' => $result->ranAt->toDateString()]));
        }

        return CheckResult::pass('advisories', $label, (string)__('aegis::aegis.checks.advisories.pass', ['date' => $result->ranAt->toDateString()]));
    }
}
