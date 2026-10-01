<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect('Wobqqq\Aegis')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('value objects are immutable')
    ->expect([Wobqqq\Aegis\Checks\CheckResult::class, Wobqqq\Aegis\Scanners\ScanResult::class, Wobqqq\Aegis\Audit\AuditResult::class, Wobqqq\Aegis\Hardening\HardeningSettings::class, Wobqqq\Aegis\Settings\Field::class])
    ->toBeFinal()
    ->toBeReadonly();

arch('enums back every shared code')
    ->expect('Wobqqq\Aegis\Enums')
    ->toBeStringBackedEnums();

arch('only the scanners open network connections')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents'])
    ->toOnlyBeUsedIn('Wobqqq\Aegis\Scanners');
