<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Wobqqq\Aegis\Audit\AuditResult;
use Wobqqq\Aegis\Audit\AuditStore;

use Wobqqq\Aegis\Audit\ComposerAudit;

it('runs composer audit and keeps the advisories it reports', function (): void {
    Process::fake(['*' => Process::result((string)json_encode([
        'advisories' => ['acme/lib' => [['packageName' => 'acme/lib', 'title' => 'SQL injection', 'cve' => 'CVE-2026-1', 'link' => 'https://example.com']]],
        'abandoned' => ['old/pkg' => null],
    ]), exitCode: 1)]);

    $result = resolve(ComposerAudit::class)->run();

    expect($result->advisories)->toBe([['package' => 'acme/lib', 'title' => 'SQL injection', 'cve' => 'CVE-2026-1', 'link' => 'https://example.com']])
        ->and($result->abandoned)->toBe(['old/pkg'])
        ->and(resolve(AuditStore::class)->last()?->advisories)->toHaveCount(1);

    Process::assertRan(static fn (Illuminate\Process\PendingProcess $process): bool => $process->command === ['composer', 'audit', '--format=json', '--locked', '--no-interaction', '--abandoned=report']);
});

it('records an audit that did not answer JSON as an error', function (): void {
    Process::fake(['*' => Process::result('', 'composer: not found', 127)]);

    expect(resolve(ComposerAudit::class)->run()->error)->toBe('composer audit did not answer with JSON.');
});

it('survives a stored audit in a shape it does not know', function (): void {
    cache()->forever('aegis.audit.v1', ['unexpected' => true]);

    expect(resolve(AuditStore::class)->last())->toBeNull()
        ->and(AuditResult::fromArray(['ran_at' => '2026-01-01T00:00:00+00:00', 'advisories' => [['package' => 'a/b'], 'junk'], 'abandoned' => ['x', 3]]))
        ->toBeInstanceOf(AuditResult::class);
});

it('prints the advisories and fails from the console', function (): void {
    Process::fake(['*' => Process::result((string)json_encode(['advisories' => ['acme/lib' => [['title' => 'XSS']]]]))]);

    expect(Artisan::call('aegis:audit'))->toBe(1)->and(Artisan::output())->toContain('acme/lib')->toContain('XSS');
});

it('succeeds from the console when nothing is reported', function (): void {
    Process::fake(['*' => Process::result('{"advisories": []}')]);

    expect(Artisan::call('aegis:audit'))->toBe(0)->and(Artisan::output())->toContain('No security advisories.');
});

it('fails from the console when the audit cannot run', function (): void {
    Process::fake(['*' => Process::result('not json')]);

    expect(Artisan::call('aegis:audit'))->toBe(1);
});

it('records an audit that could not start', function (): void {
    Process::fake(static fn () => throw new RuntimeException('no shell'));

    expect(resolve(ComposerAudit::class)->run()->error)->toBe('composer audit could not run: RuntimeException');
});

it('answers no audit when the cache cannot be read', function (): void {
    /** @var Illuminate\Contracts\Cache\Repository&Mockery\MockInterface $cache */
    $cache = Mockery::mock(Illuminate\Contracts\Cache\Repository::class);
    $cache->allows('get')->andThrow(new RuntimeException('down'));

    expect((new AuditStore($cache))->last())->toBeNull();
});
