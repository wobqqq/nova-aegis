<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Laravel\Nova\Nova;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Enums\InsecurePath;

final class NovaPathCheck implements Check
{
    public function run(): CheckResult
    {
        $label = (string)__('aegis::aegis.checks.nova_path.label');
        $path = trim(Nova::path(), '/');

        return InsecurePath::tryFrom(strtolower($path)) === null
            ? CheckResult::pass('nova_path', $label, (string)__('aegis::aegis.checks.nova_path.pass', ['path' => '/' . $path]))
            : CheckResult::warn('nova_path', $label, (string)__('aegis::aegis.checks.nova_path.warn', ['path' => '/' . $path]));
    }
}
