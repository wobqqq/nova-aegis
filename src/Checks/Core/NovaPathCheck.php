<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Laravel\Nova\Nova;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Enums\InsecurePath;
use Wobqqq\Aegis\Support\Lang;

final class NovaPathCheck implements Check
{
    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.nova_path.label');
        $path = trim(Nova::path(), '/');

        return InsecurePath::tryFrom(strtolower($path)) === null
            ? CheckResult::pass('nova_path', $label, Lang::get('aegis::aegis.checks.nova_path.pass', ['path' => '/' . $path]))
            : CheckResult::warn('nova_path', $label, Lang::get('aegis::aegis.checks.nova_path.warn', ['path' => '/' . $path]));
    }
}
