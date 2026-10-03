<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Exceptions;

use Wobqqq\Aegis\Support\Lang;

final class TargetNotListed extends AegisException
{
    public function __construct(public readonly string $target)
    {
        parent::__construct(Lang::get('aegis::aegis.scanners.unlisted'));
    }
}
