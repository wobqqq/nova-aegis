<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Checks\Core;

use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;
use Wobqqq\Aegis\Hardening\HardeningModule;
use Wobqqq\Aegis\Hardening\HardeningSettings;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\Aegis\Support\Lang;

final readonly class PasswordPolicyCheck implements Check
{
    private const int RECOMMENDED_LENGTH = 12;

    public function __construct(private SettingsRepository $settings)
    {
    }

    #[Override]
    public function run(): CheckResult
    {
        $label = Lang::get('aegis::aegis.checks.password.label');
        $hardening = HardeningSettings::fromArray($this->settings->section(HardeningModule::KEY));

        if (!$hardening->enabled) {
            return CheckResult::warn('password', $label, Lang::get('aegis::aegis.checks.password.off'));
        }

        return $hardening->passwordMinLength >= self::RECOMMENDED_LENGTH && $hardening->passwordMixedCase && $hardening->passwordNumbers && $hardening->passwordSymbols
            ? CheckResult::pass('password', $label, Lang::get('aegis::aegis.checks.password.pass', ['length' => $hardening->passwordMinLength]))
            : CheckResult::warn('password', $label, Lang::get('aegis::aegis.checks.password.weak', ['length' => self::RECOMMENDED_LENGTH]));
    }
}
