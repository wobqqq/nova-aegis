<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class AppKeyCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.app_key.label');
        $key = $this->config->get('app.key');

        return is_string($key) && $key !== ''
            ? CheckResult::pass('app_key', $label, (string)__('aegis::aegis.checks.app_key.pass'))
            : CheckResult::fail('app_key', $label, (string)__('aegis::aegis.checks.app_key.fail'));
    }
}
