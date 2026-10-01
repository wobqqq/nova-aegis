<?php

declare(strict_types=1);

return [
    'menu' => 'Aegis',

    'checks' => [
        'failed' => 'The check could not run; see the application log.',
        'debug' => [
            'label' => 'Debug mode',
            'pass' => 'Debug mode is off.',
            'fail' => 'Debug mode is on: error pages show the code, the configuration and the request.',
        ],
        'environment' => [
            'label' => 'Environment',
            'pass' => 'The application runs in production.',
            'warn' => 'The application runs in ":env", not "production".',
        ],
        'app_key' => [
            'label' => 'Application key',
            'pass' => 'The application key is set.',
            'fail' => 'APP_KEY is empty: sessions and encrypted values are not protected.',
        ],
        'https' => [
            'label' => 'HTTPS',
            'pass' => 'APP_URL uses HTTPS.',
            'warn' => 'APP_URL does not use HTTPS.',
        ],
        'nova_path' => [
            'label' => 'Nova path',
            'pass' => 'Nova is served from :path, not a path scanners try first.',
            'warn' => 'Nova is served from :path, the first path bots and scanners try. Change nova.path.',
        ],
        'session' => [
            'label' => 'Session cookie',
            'pass' => 'The session cookie is HTTPS-only, hidden from JavaScript and SameSite.',
            'warn' => 'The session cookie is weak: :flags.',
        ],
        'password' => [
            'label' => 'Password policy',
            'off' => 'Aegis does not set the password policy: turn the hardening on.',
            'pass' => 'Passwords need at least :length characters, mixed case, a number and a symbol.',
            'weak' => 'The password policy is weaker than :length characters with mixed case, a number and a symbol.',
        ],
        'stale_admins' => [
            'label' => 'Unused accounts',
            'unconfigured' => 'Set aegis.users.last_login_column to find the accounts nobody signs in with.',
            'pass' => 'Every account signed in during the last :days days.',
            'warn' => ':count accounts have not signed in for :days days.',
        ],
        'advisories' => [
            'label' => 'Dependency advisories',
            'never' => 'Run php artisan aegis:audit (or schedule it) to check the installed packages.',
            'error' => 'The last composer audit could not run.',
            'fail' => ':count security advisories in the installed packages: :packages.',
            'stale' => 'No advisories on :date, but the audit is older than a week.',
            'pass' => 'No security advisories in the installed packages (checked :date).',
        ],
    ],

    'hardening' => [
        'label' => 'Hardening',
        'description' => 'Session cookies, the password policy and HTTPS, applied to the whole application.',
        'on' => 'The hardening is on.',
        'off' => 'The hardening is off.',
        'fields' => [
            'enabled' => 'Apply the hardening',
            'session_secure' => 'HTTPS-only session cookie',
            'session_http_only' => 'Hide the session cookie from JavaScript',
            'session_same_site' => 'SameSite',
            'session_lifetime' => 'Session lifetime (minutes)',
            'session_encrypt' => 'Encrypt the session data',
            'password_min_length' => 'Minimum password length',
            'password_mixed_case' => 'Require upper- and lowercase letters',
            'password_letters' => 'Require a letter',
            'password_numbers' => 'Require a number',
            'password_symbols' => 'Require a symbol',
            'password_uncompromised' => 'Refuse passwords found in data breaches',
            'force_https' => 'Force HTTPS',
            'hsts' => 'Send Strict-Transport-Security',
            'hsts_max_age' => 'HSTS max-age (seconds)',
            'hsts_include_subdomains' => 'HSTS for the subdomains too',
        ],
        'help' => [
            'enabled' => 'Nothing below changes the application until this is on.',
            'session_secure' => 'Signing in over plain HTTP stops working.',
            'session_http_only' => 'A script injected in a page cannot read the session.',
            'session_same_site' => 'Strict also drops the cookie on links from other sites.',
            'session_lifetime' => 'Idle minutes before a session expires.',
            'session_encrypt' => 'Encrypts what the session stores.',
            'password_min_length' => 'Applies to every Password::defaults() rule, Nova\'s user forms included.',
            'password_uncompromised' => 'Asks the Have I Been Pwned range API (k-anonymity).',
            'force_https' => 'Redirects every HTTP request to HTTPS and generates HTTPS URLs.',
            'hsts' => 'Browsers then refuse plain HTTP for this site: turn it on once HTTPS works everywhere.',
            'hsts_include_subdomains' => 'Only when every subdomain is served over HTTPS.',
        ],
    ],

    'scanners' => [
        'label' => 'Scanners',
        'description' => 'The targets the scanners may reach. A scan only runs against what is listed here.',
        'unlisted' => 'This target is not listed in the scanner settings.',
        'fields' => [
            'sensitive_file_urls' => 'Sites to check for sensitive files',
            'sensitive_file_paths' => 'Sensitive paths',
            'tcp_targets' => 'Servers to check for open ports',
            'tls_targets' => 'Hosts to check the TLS certificate of',
            'url' => 'URL',
            'path' => 'Path',
            'ip' => 'IP address',
            'host' => 'Host',
            'ports' => 'Ports',
        ],
        'help' => [
            'sensitive_file_urls' => 'A path answering 200 counts as exposed; redirects are not followed.',
            'sensitive_file_paths' => 'Relative to each site, without "..".',
            'tcp_targets' => 'Every port is tried at once; comma-separated ports.',
            'tls_targets' => 'A certificate that is invalid or expires within 14 days is flagged.',
        ],
    ],
];
