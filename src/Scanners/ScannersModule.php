<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use Illuminate\Contracts\Config\Repository as Config;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Settings\Field;
use Wobqqq\Aegis\Support\Lang;

final readonly class ScannersModule implements Module
{
    public const string KEY = 'scanners';

    public const array SENSITIVE_PATHS = [
        '.env', '.env.example', '.env.local', '.env.production', '.env.backup',
        'composer.json', 'composer.lock', 'auth.json', 'package.json', 'package-lock.json', 'yarn.lock', '.npmrc',
        'phpunit.xml', 'phpunit.xml.dist', 'artisan',
        'config/app.php', 'config/database.php', 'config/mail.php', 'config/services.php',
        'storage/logs/laravel.log', 'storage/logs',
        '.git/config', '.git/HEAD', '.gitignore', '.htpasswd', 'docker-compose.yml', 'docker-compose.yaml', 'Dockerfile',
        'vendor', 'node_modules',
    ];

    public const array TCP_PORTS = [21, 22, 23, 25, 2375, 3306, 5432, 6379, 8080, 9200, 11211, 27017];

    public function __construct(private Config $config)
    {
    }

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function label(): string
    {
        return Lang::get('aegis::aegis.scanners.label');
    }

    #[Override]
    public function description(): string
    {
        return Lang::get('aegis::aegis.scanners.description');
    }

    #[Override]
    public function defaults(): array
    {
        $url = $this->config->get('app.url');
        $url = is_string($url) ? rtrim($url, '/') : '';

        $host = parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? (string)preg_replace('/^www\./i', '', $host) : '';

        return [
            'sensitive_file_urls' => $url === '' ? [] : [['url' => $url]],
            'sensitive_file_paths' => array_map(static fn (string $path): array => ['path' => $path], self::SENSITIVE_PATHS),
            'tcp_targets' => [['host' => '', 'ports' => implode(',', self::TCP_PORTS)]],
            'tls_targets' => $host === '' ? [] : [['host' => $host, 'ports' => '443']],
        ];
    }

    #[Override]
    public function rules(): array
    {
        $host = 'regex:/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/i';
        $ports = 'regex:/^\d{1,5}(?:,\d{1,5})*$/';

        return [
            'sensitive_file_urls' => ['present', 'array', 'max:20'],
            'sensitive_file_urls.*.url' => ['required', 'string', 'max:255', 'url:http,https'],
            'sensitive_file_paths' => ['present', 'array', 'max:200'],
            'sensitive_file_paths.*.path' => ['required', 'string', 'max:150', 'regex:/^(?!.*\.\.)[A-Za-z0-9._\/-]+$/'],
            'tcp_targets' => ['present', 'array', 'max:10'],
            'tcp_targets.*.host' => ['nullable', 'string', 'max:255', 'ip'],
            'tcp_targets.*.ports' => ['required', 'string', 'max:200', $ports],
            'tls_targets' => ['present', 'array', 'max:10'],
            'tls_targets.*.host' => ['required', 'string', 'max:255', $host],
            'tls_targets.*.ports' => ['required', 'string', 'max:200', $ports],
        ];
    }

    #[Override]
    public function fields(): array
    {
        $label = static fn (string $name): string => Lang::get('aegis::aegis.scanners.fields.' . $name);
        $help = static fn (string $name): string => Lang::get('aegis::aegis.scanners.help.' . $name);

        return [
            Field::table('sensitive_file_urls', $label('sensitive_file_urls'), [Field::text('url', $label('url'), placeholder: 'https://example.com')], $help('sensitive_file_urls')),
            Field::table('sensitive_file_paths', $label('sensitive_file_paths'), [Field::text('path', $label('path'), placeholder: '.env')], $help('sensitive_file_paths')),
            Field::table('tcp_targets', $label('tcp_targets'), [Field::text('host', $label('ip'), placeholder: '203.0.113.10'), Field::text('ports', $label('ports'), placeholder: '22,3306')], $help('tcp_targets')),
            Field::table('tls_targets', $label('tls_targets'), [Field::text('host', $label('host'), placeholder: 'example.com'), Field::text('ports', $label('ports'), placeholder: '443')], $help('tls_targets')),
        ];
    }

    #[Override]
    public function status(array $values): ?CheckResult
    {
        return null;
    }
}
