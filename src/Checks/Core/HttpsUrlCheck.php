<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class HttpsUrlCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.https.label');
        $url = $this->config->get('app.url');

        return is_string($url) && str_starts_with(strtolower($url), 'https://')
            ? CheckResult::pass('https', $label, (string)__('aegis::aegis.checks.https.pass'))
            : CheckResult::warn('https', $label, (string)__('aegis::aegis.checks.https.warn'));
    }
}
