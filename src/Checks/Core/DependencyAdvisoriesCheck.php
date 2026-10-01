<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Support\Facades\Date;
use Override;
use Wobqqq\Aegis\Audit\AuditResult;
use Wobqqq\Aegis\Audit\AuditStore;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Support\Lang;

final readonly class DependencyAdvisoriesCheck implements Check
{
    private const int STALE_AFTER_DAYS = 7;

    public function __construct(private AuditStore $store)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.advisories.label');
        $result = $this->store->last();

        if (!$result instanceof AuditResult) {
            return CheckResult::info('advisories', $label, Lang::get('aegis::aegis.checks.advisories.never'));
        }

        if ($result->error !== null) {
            return CheckResult::warn('advisories', $label, Lang::get('aegis::aegis.checks.advisories.error'));
        }

        if ($result->advisories !== []) {
            return CheckResult::fail('advisories', $label, Lang::get('aegis::aegis.checks.advisories.fail', [
                'count' => count($result->advisories),
                'packages' => implode(', ', $result->packages()),
            ]));
        }

        if ($result->ranAt->lt(Date::now()->subDays(self::STALE_AFTER_DAYS))) {
            return CheckResult::warn('advisories', $label, Lang::get('aegis::aegis.checks.advisories.stale', ['date' => $result->ranAt->toDateString()]));
        }

        return CheckResult::pass('advisories', $label, Lang::get('aegis::aegis.checks.advisories.pass', ['date' => $result->ranAt->toDateString()]));
    }
}
