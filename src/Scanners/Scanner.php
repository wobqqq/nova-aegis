<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use Carbon\CarbonImmutable;
use Psr\Clock\ClockInterface;
use Wobqqq\Aegis\Exceptions\TargetNotListed;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\Aegis\Support\Values;

/**
 * Runs the three scanners, each only against the targets the settings list.
 */
final readonly class Scanner
{
    private const int EXPIRY_WARNING_DAYS = 14;

    public function __construct(
        private SettingsRepository $settings,
        private HttpProbe $http,
        private TcpProbe $tcp,
        private TlsProbe $tls,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TargetNotListed
     *
     * @return list<ScanResult>
     */
    public function sensitiveFiles(string $site): array
    {
        $values = $this->settings->section(ScannersModule::KEY);
        $sites = array_map(static fn (string $url): string => rtrim($url, '/'), Values::column($values, 'sensitive_file_urls', 'url'));
        $site = rtrim($site, '/');

        throw_unless(in_array($site, $sites, true), TargetNotListed::class, $site);

        $paths = array_map(static fn (string $path): string => trim($path, '/'), Values::column($values, 'sensitive_file_paths', 'path'));
        $urls = array_values(array_unique(array_map(static fn (string $path): string => $site . '/' . $path, $paths)));
        $results = [];

        foreach ($this->http->statuses($urls) as $url => $status) {
            $results[] = new ScanResult($url, (string)$status, $status === 200);
        }

        usort($results, static fn (ScanResult $a, ScanResult $b): int => $a->exposed === $b->exposed ? strcmp($a->target, $b->target) : ($a->exposed ? -1 : 1));

        return $results;
    }

    /**
     * @throws TargetNotListed
     *
     * @return list<ScanResult>
     */
    public function tcpPorts(string $ip): array
    {
        foreach (Values::targets($this->settings->section(ScannersModule::KEY), 'tcp_targets', 'host') as [$host, $ports]) {
            if ($host !== $ip) {
                continue;
            }

            $results = [];

            foreach ($this->tcp->states($host, $ports) as $port => $state) {
                $results[] = new ScanResult(sprintf('%s:%d', $host, $port), $state, $state === TcpProbe::OPEN);
            }

            return $results;
        }

        throw new TargetNotListed($ip);
    }

    /**
     * @throws TargetNotListed
     *
     * @return list<ScanResult>
     */
    public function tlsCertificates(string $host): array
    {
        foreach (Values::targets($this->settings->section(ScannersModule::KEY), 'tls_targets', 'host') as [$target, $ports]) {
            if (strcasecmp($target, $host) !== 0) {
                continue;
            }

            $results = [];

            foreach ($ports as $port) {
                $certificate = $this->tls->certificate($target, $port);
                $name = sprintf('%s:%d', $target, $port);

                if (!$certificate instanceof Certificate) {
                    $results[] = new ScanResult($name, 'invalid', true);

                    continue;
                }

                $expiresSoon = $certificate->expiresOn->lt(CarbonImmutable::instance($this->clock->now())->addDays(self::EXPIRY_WARNING_DAYS));
                $results[] = new ScanResult($name, $expiresSoon ? 'expires-soon' : 'valid', $expiresSoon, $certificate->expiresOn->toDateString());
            }

            return $results;
        }

        throw new TargetNotListed($host);
    }
}
