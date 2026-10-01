<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class DebugModeCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.debug.label');

        return $this->config->get('app.debug') === false
            ? CheckResult::pass('debug', $label, (string)__('aegis::aegis.checks.debug.pass'))
            : CheckResult::fail('debug', $label, (string)__('aegis::aegis.checks.debug.fail'));
    }
}
