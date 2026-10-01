<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Process;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Scanners\HttpProbe;
use Wobqqq\Aegis\Scanners\ScannersModule;
use Wobqqq\Aegis\Scanners\TlsProbe;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\Aegis\Tests\Support\Sockets;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\json;

it('keeps every endpoint to the administrators the gate lets in', function (string $method, string $uri): void {
    json($method, $uri)->assertUnauthorized();
    actingAs(editor())->json($method, $uri)->assertForbidden();
})->with([
    ['GET', '/nova-vendor/aegis/overview'],
    ['POST', '/nova-vendor/aegis/audit'],
    ['GET', '/nova-vendor/aegis/settings'],
    ['PUT', '/nova-vendor/aegis/settings/hardening'],
    ['POST', '/nova-vendor/aegis/scans/sensitive-files'],
    ['POST', '/nova-vendor/aegis/scans/tcp-ports'],
    ['POST', '/nova-vendor/aegis/scans/tls-certificates'],
]);

it('refuses everyone when the application defines no gate', function (): void {
    Gate::swap(new Illuminate\Auth\Access\Gate(app(), static fn () => auth()->user()));

    actingAs(admin())->getJson('/nova-vendor/aegis/overview')->assertForbidden();
});

it('answers the checks, the modules and the last audit', function (): void {
    actingAs(admin())->getJson('/nova-vendor/aegis/overview')
        ->assertOk()
        ->assertJsonPath('modules.0.key', HardeningModule::KEY)
        ->assertJsonStructure(['checks' => [['key', 'label', 'status', 'message']], 'audit']);
});

it('runs the dependency audit on request', function (): void {
    Process::fake(['*' => Process::result('{"advisories": []}')]);

    actingAs(admin())->postJson('/nova-vendor/aegis/audit')->assertOk()->assertJsonPath('audit.advisories', []);
});

it('describes every section with its fields and values', function (): void {
    actingAs(admin())->getJson('/nova-vendor/aegis/settings')
        ->assertOk()
        ->assertJsonPath('sections.0.key', HardeningModule::KEY)
        ->assertJsonPath('sections.0.values.enabled', false)
        ->assertJsonPath('sections.0.fields.0.type', 'toggle')
        ->assertJsonPath('sections.1.fields.0.columns.0.name', 'url');
});

it('saves a section and answers the errors of an invalid one', function (): void {
    $values = ['enabled' => true] + (new HardeningModule())->defaults();

    actingAs($admin = admin())->putJson('/nova-vendor/aegis/settings/hardening', ['values' => $values])
        ->assertOk()->assertJsonPath('values.enabled', true);

    actingAs($admin)->putJson('/nova-vendor/aegis/settings/hardening', ['values' => ['password_min_length' => 2] + $values])
        ->assertUnprocessable()->assertJsonValidationErrors('password_min_length');

    actingAs($admin)->putJson('/nova-vendor/aegis/settings/unknown', ['values' => []])->assertNotFound();
});

it('runs a scan against a listed target and refuses the others', function (): void {
    app()->instance(HttpProbe::class, new HttpProbe(new Client(['handler' => HandlerStack::create(
        static fn (): GuzzleHttp\Promise\PromiseInterface => Create::promiseFor(new Response(200)),
    )])));
    app()->instance(TlsProbe::class, new class () extends TlsProbe {
        public function certificate(string $host, int $port): ?array
        {
            return null;
        }
    });
    resolve(SettingsRepository::class)->save(ScannersModule::KEY, [
        'sensitive_file_paths' => [['path' => '.env']],
        'tcp_targets' => [['host' => '127.0.0.1', 'ports' => (string)Sockets::closedPort()]],
    ] + resolve(ScannersModule::class)->defaults());

    actingAs($admin = admin())->postJson('/nova-vendor/aegis/scans/sensitive-files', ['url' => 'https://aegis.test'])
        ->assertOk()->assertJsonPath('exposed', 1)->assertJsonPath('results.0.target', 'https://aegis.test/.env');

    actingAs($admin)->postJson('/nova-vendor/aegis/scans/sensitive-files', ['url' => 'https://other.test'])->assertUnprocessable();
    actingAs($admin)->postJson('/nova-vendor/aegis/scans/sensitive-files', ['url' => 'file:///etc/passwd'])->assertJsonValidationErrors('url');
    actingAs($admin)->postJson('/nova-vendor/aegis/scans/tcp-ports', ['host' => 'not-an-ip'])->assertJsonValidationErrors('host');
    actingAs($admin)->postJson('/nova-vendor/aegis/scans/tcp-ports', ['host' => '127.0.0.1'])->assertOk()->assertJsonPath('exposed', 0);
    actingAs($admin)->postJson('/nova-vendor/aegis/scans/tls-certificates', ['host' => 'aegis.test'])->assertOk()->assertJsonPath('results.0.status', 'invalid');
});
