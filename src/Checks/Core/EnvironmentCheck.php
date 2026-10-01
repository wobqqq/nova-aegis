<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class EnvironmentCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.environment.label');
        $environment = $this->config->get('app.env');
        $environment = is_string($environment) ? $environment : '';

        return $environment === 'production'
            ? CheckResult::pass('environment', $label, (string)__('aegis::aegis.checks.environment.pass'))
            : CheckResult::warn('environment', $label, (string)__('aegis::aegis.checks.environment.warn', ['env' => $environment]));
    }
}
