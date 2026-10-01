<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Enums;

enum Status: string
{
    case PASS = 'pass';
    case WARN = 'warn';
    case FAIL = 'fail';
    case INFO = 'info';
}
