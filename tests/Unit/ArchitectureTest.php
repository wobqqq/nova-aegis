<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Wobqqq\Aegis\Audit\Advisory;
use Wobqqq\Aegis\Audit\AuditResult;
use Wobqqq\Aegis\Audit\ComposerAudit;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Exceptions\AegisException;
use Wobqqq\Aegis\Hardening\HardeningSettings;
use Wobqqq\Aegis\Scanners\Certificate;
use Wobqqq\Aegis\Scanners\ScanResult;
use Wobqqq\Aegis\Settings\Field;

arch('every file declares strict types')
    ->expect('Wobqqq\Aegis')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('value objects are immutable')
    ->expect([CheckResult::class, ScanResult::class, AuditResult::class, Advisory::class, Certificate::class, HardeningSettings::class, Field::class])
    ->toBeFinal()
    ->toBeReadonly();

arch('every class is final')
    ->expect('Wobqqq\Aegis')
    ->classes()
    ->toBeFinal()
    ->ignoring(AegisException::class);

arch('business exceptions extend the Aegis exception')
    ->expect('Wobqqq\Aegis\Exceptions')
    ->classes()
    ->toExtend(AegisException::class)
    ->ignoring(AegisException::class);

arch('the scanners and checks read time from the clock')
    ->expect(['Wobqqq\Aegis\Scanners', 'Wobqqq\Aegis\Checks', ComposerAudit::class])
    ->not->toUse([Date::class, 'now', 'today']);

arch('enums back every shared code')
    ->expect('Wobqqq\Aegis\Enums')
    ->toBeStringBackedEnums();

arch('only the scanners open network connections')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents'])
    ->toOnlyBeUsedIn('Wobqqq\Aegis\Scanners');
