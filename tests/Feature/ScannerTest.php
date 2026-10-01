<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Wobqqq\Aegis\Scanners\HttpProbe;
use Wobqqq\Aegis\Scanners\Scanner;
use Wobqqq\Aegis\Scanners\ScannersModule;
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
    $handler = static function (RequestInterface $request, array $options) use ($statuses, $requested): GuzzleHttp\Promise\PromiseInterface {
        $requested[] = (string)$request->getUri();

        return Create::promiseFor(new Response($statuses[(string)$request->getUri()] ?? 404));
    };

    app()->instance(HttpProbe::class, new HttpProbe(new Client(['handler' => HandlerStack::create($handler)])));

    return $requested;
}

it('suggests the application URL, its host and the usual sensitive paths', function (): void {
    $defaults = resolve(ScannersModule::class)->defaults();

    expect($defaults['sensitive_file_urls'])->toBe([['url' => 'https://aegis.test']])
        ->and($defaults['tls_targets'])->toBe([['host' => 'aegis.test', 'ports' => '443']])
        ->and($defaults['sensitive_file_paths'])->toContain(['path' => '.env']);
});

it('reports the sensitive paths a listed site serves', function (): void {
    scanTargets(['sensitive_file_paths' => [['path' => '.env'], ['path' => '/composer.json/']]]);
    $requested = fakeHttp(['https://aegis.test/.env' => 200, 'https://aegis.test/composer.json' => 301]);

    $results = resolve(Scanner::class)->sensitiveFiles('https://aegis.test/') ?? [];

    expect($results)->toHaveCount(2)
        ->and($results[0]->target)->toBe('https://aegis.test/.env')
        ->and($results[0]->exposed)->toBeTrue()
        ->and($results[1]->exposed)->toBeFalse()
        ->and($requested->getArrayCopy())->toEqualCanonicalizing(['https://aegis.test/.env', 'https://aegis.test/composer.json']);
});

it('never reaches a site that is not listed', function (): void {
    $requested = fakeHttp([]);

    expect(resolve(Scanner::class)->sensitiveFiles('https://internal.example'))->toBeNull()
        ->and($requested->getArrayCopy())->toBe([]);
});

it('does not follow a redirect to call a path exposed', function (): void {
    $probe = new HttpProbe(new Client(['handler' => HandlerStack::create(static fn (RequestInterface $request, array $options): GuzzleHttp\Promise\PromiseInterface => Create::promiseFor(
        $options['allow_redirects'] === false ? new Response(302, ['Location' => '/']) : new Response(200),
    ))]));

    expect($probe->statuses(['https://aegis.test/.env']))->toBe(['https://aegis.test/.env' => 302]);
});

it('reports an unreachable site as an error', function (): void {
    $probe = new HttpProbe(new Client(['handler' => HandlerStack::create(static fn (): GuzzleHttp\Promise\PromiseInterface => Create::rejectionFor(new RuntimeException('down')))]));

    expect($probe->statuses(['https://aegis.test/.env']))->toBe(['https://aegis.test/.env' => 'error']);
});

it('tells open ports from closed ones on a listed server', function (): void {
    [$server, $open] = Sockets::listen();
    $closed = Sockets::closedPort();
    scanTargets(['tcp_targets' => [['host' => '127.0.0.1', 'ports' => sprintf('%d,%d,0,99999', $open, $closed)]]]);

    $results = resolve(Scanner::class)->tcpPorts('127.0.0.1');
    fclose($server);

    expect(collect($results)->mapWithKeys(fn ($result): array => [$result->target => $result->status])->all())->toBe([
        '127.0.0.1:' . $open => TcpProbe::OPEN,
        '127.0.0.1:' . $closed => TcpProbe::CLOSED,
    ])->and(resolve(Scanner::class)->tcpPorts('192.0.2.1'))->toBeNull();
});

it('dials every port within one timeout', function (): void {
    $start = microtime(true);

    (new TcpProbe(1))->states('10.255.255.1', [21, 22, 23, 25, 3306, 5432]);

    expect(microtime(true) - $start)->toBeLessThan(2.5);
});

it('connects to an IPv6 address', function (): void {
    [$server, $port] = Sockets::listen('[::1]');

    $states = (new TcpProbe(1))->states('::1', [$port]);
    fclose($server);

    expect($states)->toBe([$port => TcpProbe::OPEN]);
})->skip(!Sockets::supportsIpv6(), 'IPv6 loopback is not available.');

it('flags an invalid certificate and one that expires soon', function (): void {
    scanTargets(['tls_targets' => [['host' => 'aegis.test', 'ports' => '443,8443,993']]]);
    app()->instance(TlsProbe::class, new class () extends TlsProbe {
        public function certificate(string $host, int $port): ?array
        {
            return match ($port) {
                443 => ['issued_on' => Illuminate\Support\Facades\Date::now()->subYear(), 'expires_on' => Illuminate\Support\Facades\Date::now()->addMonths(6)],
                8443 => ['issued_on' => Illuminate\Support\Facades\Date::now()->subYear(), 'expires_on' => Illuminate\Support\Facades\Date::now()->addDays(3)],
                default => null,
            };
        }
    });

    $results = collect(resolve(Scanner::class)->tlsCertificates('AEGIS.test'))->mapWithKeys(
        static fn ($result): array => [$result->target => $result->status],
    )->all();

    expect($results)->toBe(['aegis.test:443' => 'valid', 'aegis.test:8443' => 'expires-soon', 'aegis.test:993' => 'invalid'])
        ->and(resolve(Scanner::class)->tlsCertificates('other.test'))->toBeNull();
});

it('answers no certificate for a host that does not listen', function (): void {
    expect((new TlsProbe(1))->certificate('127.0.0.1', Sockets::closedPort()))->toBeNull();
});
