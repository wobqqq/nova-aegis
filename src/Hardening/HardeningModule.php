<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Hardening;

use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Settings\Field;

final class HardeningModule implements Module
{
    public const KEY = 'hardening';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return (string)__('aegis::aegis.hardening.label');
    }

    public function description(): string
    {
        return (string)__('aegis::aegis.hardening.description');
    }

    public function defaults(): array
    {
        return [
            'enabled' => false,
            'session_secure' => true,
            'session_http_only' => true,
            'session_same_site' => 'lax',
            'session_lifetime' => 120,
            'session_encrypt' => false,
            'password_min_length' => 12,
            'password_mixed_case' => true,
            'password_letters' => true,
            'password_numbers' => true,
            'password_symbols' => true,
            'password_uncompromised' => false,
            'force_https' => false,
            'hsts' => false,
            'hsts_max_age' => 31_536_000,
            'hsts_include_subdomains' => false,
        ];
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'session_secure' => ['required', 'boolean'],
            'session_http_only' => ['required', 'boolean'],
            'session_same_site' => ['required', 'in:lax,strict'],
            'session_lifetime' => ['required', 'integer', 'min:1', 'max:1440'],
            'session_encrypt' => ['required', 'boolean'],
            'password_min_length' => ['required', 'integer', 'min:8', 'max:128'],
            'password_mixed_case' => ['required', 'boolean'],
            'password_letters' => ['required', 'boolean'],
            'password_numbers' => ['required', 'boolean'],
            'password_symbols' => ['required', 'boolean'],
            'password_uncompromised' => ['required', 'boolean'],
            'force_https' => ['required', 'boolean'],
            'hsts' => ['required', 'boolean'],
            'hsts_max_age' => ['required', 'integer', 'min:300', 'max:63072000'],
            'hsts_include_subdomains' => ['required', 'boolean'],
        ];
    }

    public function fields(): array
    {
        $field = static fn (string $name): string => (string)__('aegis::aegis.hardening.fields.' . $name);
        $help = static fn (string $name): string => (string)__('aegis::aegis.hardening.help.' . $name);

        return [
            Field::toggle('enabled', $field('enabled'), $help('enabled')),
            Field::toggle('session_secure', $field('session_secure'), $help('session_secure')),
            Field::toggle('session_http_only', $field('session_http_only'), $help('session_http_only')),
            Field::select('session_same_site', $field('session_same_site'), ['lax' => 'Lax', 'strict' => 'Strict'], $help('session_same_site')),
            Field::number('session_lifetime', $field('session_lifetime'), $help('session_lifetime')),
            Field::toggle('session_encrypt', $field('session_encrypt'), $help('session_encrypt')),
            Field::number('password_min_length', $field('password_min_length'), $help('password_min_length')),
            Field::toggle('password_mixed_case', $field('password_mixed_case')),
            Field::toggle('password_letters', $field('password_letters')),
            Field::toggle('password_numbers', $field('password_numbers')),
            Field::toggle('password_symbols', $field('password_symbols')),
            Field::toggle('password_uncompromised', $field('password_uncompromised'), $help('password_uncompromised')),
            Field::toggle('force_https', $field('force_https'), $help('force_https')),
            Field::toggle('hsts', $field('hsts'), $help('hsts')),
            Field::number('hsts_max_age', $field('hsts_max_age')),
            Field::toggle('hsts_include_subdomains', $field('hsts_include_subdomains'), $help('hsts_include_subdomains')),
        ];
    }

    public function status(array $values): CheckResult
    {
        $label = $this->label();

        return HardeningSettings::fromArray($values)->enabled
            ? CheckResult::pass(self::KEY, $label, (string)__('aegis::aegis.hardening.on'))
            : CheckResult::warn(self::KEY, $label, (string)__('aegis::aegis.hardening.off'));
    }
}
