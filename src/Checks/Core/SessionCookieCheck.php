<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Illuminate\Contracts\Config\Repository as Config;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Support\Lang;

final readonly class SessionCookieCheck implements Check
{
    public function __construct(private Config $config)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.session.label');
        $sameSite = $this->config->get('session.same_site');

        $weaknesses = array_keys(array_filter([
            'secure' => $this->config->get('session.secure') !== true,
            'http_only' => $this->config->get('session.http_only') === false,
            'same_site' => !in_array(is_string($sameSite) ? strtolower($sameSite) : null, ['lax', 'strict'], true),
        ]));

        return $weaknesses === []
            ? CheckResult::pass('session', $label, Lang::get('aegis::aegis.checks.session.pass'))
            : CheckResult::warn('session', $label, Lang::get('aegis::aegis.checks.session.warn', ['flags' => implode(', ', $weaknesses)]));
    }
}
