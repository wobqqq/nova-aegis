<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Hardening\HardeningService;
use Wobqqq\Aegis\Hardening\HardeningSettings;
use Wobqqq\Aegis\Http\Middleware\TransportSecurity;
use Wobqqq\Aegis\Settings\SettingsRepository;

/**
 * @param array<string, mixed> $values
 */
function harden(array $values): HardeningService
{
    resolve(SettingsRepository::class)->save(HardeningModule::KEY, $values + (new HardeningModule())->defaults());

    $service = resolve(HardeningService::class);
    $service->apply();

    return $service;
}

function passes(string $password): bool
{
    return Validator::make(['password' => $password], ['password' => Password::defaults()])->passes();
}

it('changes nothing while it is off', function (): void {
    config(['session.secure' => false]);

    harden(['enabled' => false, 'session_secure' => true]);

    expect(config('session.secure'))->toBeFalse();
});

it('hardens the session cookie and the password policy', function (): void {
    harden([
        'enabled' => true,
        'session_secure' => true,
        'session_same_site' => 'strict',
        'session_lifetime' => 30,
        'session_encrypt' => true,
        'password_min_length' => 14,
    ]);

    expect(config('session.secure'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('strict')
        ->and(config('session.lifetime'))->toBe(30)
        ->and(config('session.encrypt'))->toBeTrue()
        ->and(passes('Short1!'))->toBeFalse()
        ->and(passes('long but weak password'))->toBeFalse()
        ->and(passes('Correct-Horse-9-Battery'))->toBeTrue();
});

it('generates HTTPS URLs when HTTPS is forced', function (): void {
    harden(['enabled' => true, 'force_https' => true]);

    expect(URL::to('/dashboard'))->toStartWith('https://');
});

it('redirects plain HTTP and sends HSTS over HTTPS', function (): void {
    harden(['enabled' => true, 'force_https' => true, 'hsts' => true, 'hsts_max_age' => 600, 'hsts_include_subdomains' => true]);
    $middleware = resolve(TransportSecurity::class);
    $next = static fn (): Response => new Response('ok');

    $redirect = $middleware->handle(Request::create('http://aegis.test/page?x=1'), $next);
    $secure = $middleware->handle(Request::create('https://aegis.test/page'), $next);

    expect($redirect->getStatusCode())->toBe(301)
        ->and($redirect->headers->get('Location'))->toBe('https://aegis.test/page?x=1')
        ->and($secure->headers->get('Strict-Transport-Security'))->toBe('max-age=600; includeSubDomains');
});

it('leaves requests alone while the hardening is off', function (): void {
    $response = resolve(TransportSecurity::class)->handle(Request::create('http://aegis.test/'), static fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200)->and($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('reads broken stored values as their safe defaults', function (): void {
    $settings = HardeningSettings::fromArray([
        'enabled' => 'yes',
        'session_same_site' => 'none',
        'session_lifetime' => 'forever',
        'password_min_length' => 3,
        'hsts_max_age' => 999_999_999,
    ]);

    expect($settings->enabled)->toBeTrue()
        ->and($settings->sessionSameSite)->toBe('lax')
        ->and($settings->sessionLifetime)->toBe(120)
        ->and($settings->passwordMinLength)->toBe(8)
        ->and($settings->hstsMaxAge)->toBe(63_072_000);
});
