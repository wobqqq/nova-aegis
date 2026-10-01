<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Enums;

enum FieldType: string
{
    case TOGGLE = 'toggle';
    case NUMBER = 'number';
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case TABLE = 'table';
}
