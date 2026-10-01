<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Support\Lang;

final readonly class DebugModeCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.debug.label');

        return $this->config->get('app.debug') === false
            ? CheckResult::pass('debug', $label, Lang::get('aegis::aegis.checks.debug.pass'))
            : CheckResult::fail('debug', $label, Lang::get('aegis::aegis.checks.debug.fail'));
    }
}
