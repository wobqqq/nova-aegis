<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Date;
use Psr\Http\Message\RequestInterface;
use Wobqqq\Aegis\Exceptions\TargetNotListed;
use Wobqqq\Aegis\Scanners\Certificate;
use Wobqqq\Aegis\Scanners\HttpProbe;
use Wobqqq\Aegis\Scanners\Probes\GuzzleHttpProbe;
use Wobqqq\Aegis\Scanners\Probes\SocketTcpProbe;
use Wobqqq\Aegis\Scanners\Probes\SocketTlsProbe;
use Wobqqq\Aegis\Scanners\Scanner;
use Wobqqq\Aegis\Scanners\ScannersModule;
use Wobqqq\Aegis\Scanners\ScanResult;
use Wobqqq\Aegis\Scanners\TcpProbe;
use Wobqqq\Aegis\Scanners\TlsProbe;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\Aegis\Tests\Support\Sockets;

/**
 * @param array<string, mixed> $values
 */
function scanTargets(array $values): void
{
    resolve(SettingsRepository::class)->save(ScannersModule::KEY, $values + resolve(ScannersModule::class)->defaults());
}

/**
 * @param array<string, int> $statuses
 *
 * @return ArrayObject<int, string>
 */
function fakeHttp(array $statuses): ArrayObject
{
    /** @var ArrayObject<int, string> $requested */
    $requested = new ArrayObject();
    $handler = static function (RequestInterface $request) use ($statuses, $requested): PromiseInterface {
        $requested[] = (string)$request->getUri();

        return Create::promiseFor(new Response($statuses[(string)$request->getUri()] ?? 404));
    };

    app()->instance(HttpProbe::class, new GuzzleHttpProbe(new Client(['handler' => HandlerStack::create($handler)])));

    return $requested;
}

it('suggests the application URL, its host and the usual sensitive paths', function (): void {
    $defaults = resolve(ScannersModule::class)->defaults();

    expect($defaults)->toHaveKey('sensitive_file_urls', [['url' => 'https://aegis.test']])
        ->toHaveKey('tls_targets', [['host' => 'aegis.test', 'ports' => '443']])
        ->and($defaults['sensitive_file_paths'] ?? [])->toContain(['path' => '.env']);
});

it('reports the sensitive paths a listed site serves', function (): void {
    scanTargets(['sensitive_file_paths' => [['path' => '.env'], ['path' => '/composer.json/']]]);
    $requested = fakeHttp(['https://aegis.test/.env' => 200, 'https://aegis.test/composer.json' => 301]);

    $results = resolve(Scanner::class)->sensitiveFiles('https://aegis.test/');

    expect($results)->toEqual([
        new ScanResult('https://aegis.test/.env', '200', true),
        new ScanResult('https://aegis.test/composer.json', '301', false),
    ])
        ->and($requested->getArrayCopy())->toEqualCanonicalizing(['https://aegis.test/.env', 'https://aegis.test/composer.json']);
});

it('never reaches a site that is not listed', function (): void {
    $requested = fakeHttp([]);

    expect(static fn (): array => resolve(Scanner::class)->sensitiveFiles('https://internal.example'))->toThrow(TargetNotListed::class)
        ->and($requested->getArrayCopy())->toBe([]);
});

it('does not follow a redirect to call a path exposed', function (): void {
    $probe = new GuzzleHttpProbe(new Client(['handler' => HandlerStack::create(static fn (RequestInterface $request, array $options): PromiseInterface => Create::promiseFor(
        ($options['allow_redirects'] ?? null) === false ? new Response(302, ['Location' => '/']) : new Response(200),
    ))]));

    expect($probe->statuses(['https://aegis.test/.env']))->toBe(['https://aegis.test/.env' => 302]);
});

it('reports an unreachable site as an error', function (): void {
    $probe = new GuzzleHttpProbe(new Client(['handler' => HandlerStack::create(static fn (): PromiseInterface => Create::rejectionFor(new RuntimeException('down')))]));

    expect($probe->statuses(['https://aegis.test/.env']))->toBe(['https://aegis.test/.env' => 'error']);
});

it('tells open ports from closed ones on a listed server', function (): void {
    [$server, $open] = Sockets::listen();
    $closed = Sockets::closedPort();
    scanTargets(['tcp_targets' => [['host' => '127.0.0.1', 'ports' => sprintf('%d,%d,0,99999', $open, $closed)]]]);

    $results = resolve(Scanner::class)->tcpPorts('127.0.0.1');
    fclose($server);

    expect(collect($results)->mapWithKeys(static fn (ScanResult $result): array => [$result->target => $result->status])->all())->toBe([
        '127.0.0.1:' . $open => TcpProbe::OPEN,
        '127.0.0.1:' . $closed => TcpProbe::CLOSED,
    ])->and(static fn (): array => resolve(Scanner::class)->tcpPorts('192.0.2.1'))->toThrow(TargetNotListed::class);
});

it('dials every port within one timeout', function (): void {
    $start = microtime(true);

    new SocketTcpProbe(1)->states('10.255.255.1', [21, 22, 23, 25, 3306, 5432]);

    expect(microtime(true) - $start)->toBeLessThan(2.5);
});

it('connects to an IPv6 address', function (): void {
    [$server, $port] = Sockets::listen('[::1]');

    $states = new SocketTcpProbe(1)->states('::1', [$port]);
    fclose($server);

    expect($states)->toBe([$port => TcpProbe::OPEN]);
})->skip(!Sockets::supportsIpv6(), 'IPv6 loopback is not available.');

it('flags an invalid certificate and one that expires soon', function (): void {
    scanTargets(['tls_targets' => [['host' => 'aegis.test', 'ports' => '443,8443,993']]]);
    app()->instance(TlsProbe::class, new class () implements TlsProbe {
        #[Override]
        public function certificate(string $host, int $port): ?Certificate
        {
            return match ($port) {
                443 => new Certificate(Date::now()->subYear(), Date::now()->addMonths(6)),
                8443 => new Certificate(Date::now()->subYear(), Date::now()->addDays(3)),
                default => null,
            };
        }
    });

    $results = collect(resolve(Scanner::class)->tlsCertificates('AEGIS.test'))->mapWithKeys(
        static fn (ScanResult $result): array => [$result->target => $result->status],
    )->all();

    expect($results)->toBe(['aegis.test:443' => 'valid', 'aegis.test:8443' => 'expires-soon', 'aegis.test:993' => 'invalid'])
        ->and(static fn (): array => resolve(Scanner::class)->tlsCertificates('other.test'))->toThrow(TargetNotListed::class);
});

it('answers no certificate for a host that does not listen', function (): void {
    expect(new SocketTlsProbe(1)->certificate('127.0.0.1', Sockets::closedPort()))->toBeNull();
});
