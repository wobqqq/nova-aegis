<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

final readonly class SessionCookieCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.session.label');
        $sameSite = $this->config->get('session.same_site');

        $weaknesses = array_keys(array_filter([
            'secure' => $this->config->get('session.secure') !== true,
            'http_only' => $this->config->get('session.http_only') === false,
            'same_site' => !in_array(is_string($sameSite) ? strtolower($sameSite) : null, ['lax', 'strict'], true),
        ]));

        return $weaknesses === []
            ? CheckResult::pass('session', $label, (string)__('aegis::aegis.checks.session.pass'))
            : CheckResult::warn('session', $label, (string)__('aegis::aegis.checks.session.warn', ['flags' => implode(', ', $weaknesses)]));
    }
}
