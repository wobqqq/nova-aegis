<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Audit\AuditResult;
use Wobqqq\Aegis\Audit\AuditStore;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Checks\CheckRunner;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Enums\Status;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Settings\SettingsRepository;

function check(string $key): CheckResult
{
    $results = collect(resolve(CheckRunner::class)->checks())->keyBy('key');

    return $results[$key] ?? throw new UnexpectedValueException(sprintf('No "%s" check ran.', $key));
}

it('flags debug mode, a non-production environment, a missing key and plain HTTP', function (): void {
    config(['app.debug' => true, 'app.env' => 'local', 'app.key' => '', 'app.url' => 'http://aegis.test']);

    expect(check('debug')->status)->toBe(Status::FAIL)
        ->and(check('environment')->status)->toBe(Status::WARN)
        ->and(check('app_key')->status)->toBe(Status::FAIL)
        ->and(check('https')->status)->toBe(Status::WARN);

    config(['app.debug' => false, 'app.env' => 'production', 'app.key' => 'base64:key', 'app.url' => 'https://aegis.test']);

    expect(check('debug')->status)->toBe(Status::PASS)
        ->and(check('environment')->status)->toBe(Status::PASS)
        ->and(check('app_key')->status)->toBe(Status::PASS)
        ->and(check('https')->status)->toBe(Status::PASS);
});

it('flags a Nova path every scanner tries', function (string $path, Status $status): void {
    config(['nova.path' => $path]);

    expect(check('nova_path')->status)->toBe($status);
})->with([
    ['/nova', Status::WARN],
    ['/Admin', Status::WARN],
    ['/control-room-7f3a', Status::PASS],
]);

it('flags a weak session cookie', function (): void {
    config(['session.secure' => false, 'session.http_only' => true, 'session.same_site' => null]);

    expect(check('session')->status)->toBe(Status::WARN)
        ->and(check('session')->message)->toContain('secure')->toContain('same_site');

    config(['session.secure' => true, 'session.same_site' => 'strict']);

    expect(check('session')->status)->toBe(Status::PASS);
});

it('reads the password policy from the hardening settings', function (): void {
    expect(check('password')->status)->toBe(Status::WARN);

    resolve(SettingsRepository::class)->save(HardeningModule::KEY, ['enabled' => true, 'password_min_length' => 8] + new HardeningModule()->defaults());
    expect(check('password')->status)->toBe(Status::WARN);

    resolve(SettingsRepository::class)->save(HardeningModule::KEY, ['enabled' => true, 'password_min_length' => 14] + new HardeningModule()->defaults());
    expect(check('password')->status)->toBe(Status::PASS);
});

it('finds the accounts nobody signed in with', function (): void {
    expect(check('stale_admins')->status)->toBe(Status::INFO);

    config(['aegis.users.last_login_column' => 'last_login_at', 'aegis.users.stale_after_days' => 30]);
    admin();

    expect(check('stale_admins')->status)->toBe(Status::PASS);

    editor()->forceFill(['last_login_at' => Date::now()->subDays(45)])->save();

    expect(check('stale_admins')->status)->toBe(Status::WARN)->and(check('stale_admins')->message)->toContain('1 accounts');
});

it('refuses a last login column that is not a plain column name', function (): void {
    config(['aegis.users.last_login_column' => 'last_login_at; drop table users']);

    expect(check('stale_admins')->status)->toBe(Status::INFO);
});

it('reports the last dependency audit', function (): void {
    $store = resolve(AuditStore::class);

    expect(check('advisories')->status)->toBe(Status::INFO);

    $store->put(AuditResult::fromReport(['advisories' => ['acme/lib' => [['packageName' => 'acme/lib', 'title' => 'RCE', 'cve' => 'CVE-1']]]]));
    expect(check('advisories')->status)->toBe(Status::FAIL)->and(check('advisories')->message)->toContain('acme/lib');

    $store->put(AuditResult::failed('broken'));
    expect(check('advisories')->status)->toBe(Status::WARN);

    $store->put(new AuditResult(Date::now()->subDays(10), [], []));
    expect(check('advisories')->status)->toBe(Status::WARN);

    $store->put(AuditResult::fromReport(['advisories' => []]));
    expect(check('advisories')->status)->toBe(Status::PASS);
});

it('keeps going when a check throws, without leaking its error', function (): void {
    Aegis::check(new class () implements Check {
        #[Override]
        public function run(): CheckResult
        {
            throw new RuntimeException('secret detail');
        }
    });

    $failed = collect(resolve(CheckRunner::class)->checks())->last();

    expect($failed?->status)->toBe(Status::FAIL)->and($failed?->message)->not->toContain('secret detail');
});

it('lists the status of every module that reports one', function (): void {
    $modules = collect(resolve(CheckRunner::class)->modules());

    expect($modules->pluck('key')->all())->toBe([HardeningModule::KEY]);
});
