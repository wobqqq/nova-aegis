<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Contracts;

use Wobqqq\Aegis\Checks\CheckResult;

interface Check
{
    public function run(): CheckResult;
}
