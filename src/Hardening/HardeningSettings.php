<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Hardening;

use Wobqqq\Aegis\Support\Values;

final readonly class HardeningSettings
{
    public function __construct(
        public bool $enabled,
        public bool $sessionSecure,
        public bool $sessionHttpOnly,
        public string $sessionSameSite,
        public int $sessionLifetime,
        public bool $sessionEncrypt,
        public int $passwordMinLength,
        public bool $passwordMixedCase,
        public bool $passwordLetters,
        public bool $passwordNumbers,
        public bool $passwordSymbols,
        public bool $passwordUncompromised,
        public bool $forceHttps,
        public bool $hsts,
        public int $hstsMaxAge,
        public bool $hstsIncludeSubdomains,
    ) {
    }

    /**
     * Reads the stored values again, whatever they are: a value the rules never saw falls back to its default.
     *
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        $sameSite = Values::string($values, 'session_same_site', 'lax');

        return new self(
            Values::bool($values, 'enabled'),
            Values::bool($values, 'session_secure', true),
            Values::bool($values, 'session_http_only', true),
            in_array($sameSite, ['lax', 'strict'], true) ? $sameSite : 'lax',
            Values::int($values, 'session_lifetime', 120, 1, 1440),
            Values::bool($values, 'session_encrypt'),
            Values::int($values, 'password_min_length', 12, 8, 128),
            Values::bool($values, 'password_mixed_case', true),
            Values::bool($values, 'password_letters', true),
            Values::bool($values, 'password_numbers', true),
            Values::bool($values, 'password_symbols', true),
            Values::bool($values, 'password_uncompromised'),
            Values::bool($values, 'force_https'),
            Values::bool($values, 'hsts'),
            Values::int($values, 'hsts_max_age', 31_536_000, 300, 63_072_000),
            Values::bool($values, 'hsts_include_subdomains'),
        );
    }
}
