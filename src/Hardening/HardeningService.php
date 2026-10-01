<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Hardening;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Wobqqq\Aegis\Settings\SettingsRepository;

final class HardeningService
{
    private ?HardeningSettings $settings = null;

    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly Config $config,
    ) {
    }

    public function settings(): HardeningSettings
    {
        return $this->settings ??= HardeningSettings::fromArray($this->repository->section(HardeningModule::KEY));
    }

    public function forget(): void
    {
        $this->settings = null;
    }

    public function apply(): void
    {
        $settings = $this->settings();

        if (!$settings->enabled) {
            return;
        }

        $this->config->set([
            'session.secure' => $settings->sessionSecure,
            'session.http_only' => $settings->sessionHttpOnly,
            'session.same_site' => $settings->sessionSameSite,
            'session.lifetime' => $settings->sessionLifetime,
            'session.encrypt' => $settings->sessionEncrypt,
        ]);

        Password::defaults(static function () use ($settings): Password {
            $rule = Password::min($settings->passwordMinLength);
            $rule = $settings->passwordMixedCase ? $rule->mixedCase() : $rule;
            $rule = $settings->passwordLetters ? $rule->letters() : $rule;
            $rule = $settings->passwordNumbers ? $rule->numbers() : $rule;
            $rule = $settings->passwordSymbols ? $rule->symbols() : $rule;

            return $settings->passwordUncompromised ? $rule->uncompromised() : $rule;
        });

        if ($settings->forceHttps) {
            URL::forceScheme('https');
        }
    }
}
