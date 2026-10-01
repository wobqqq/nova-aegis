<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners\Probes;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Override;
use Psr\Http\Message\ResponseInterface;
use Wobqqq\Aegis\Scanners\HttpProbe;

final readonly class GuzzleHttpProbe implements HttpProbe
{
    public function __construct(
        private Client $client = new Client(),
        private int $timeout = 10,
        private int $concurrency = 7,
    ) {
    }

    #[Override]
    public function statuses(array $urls): array
    {
        $statuses = [];

        $pool = new Pool($this->client, $this->requests($urls), [
            'concurrency' => $this->concurrency,
            'options' => [
                'timeout' => $this->timeout,
                'connect_timeout' => min(5, $this->timeout),
                'http_errors' => false,
                'allow_redirects' => false,
                'headers' => ['User-Agent' => 'Aegis sensitive files scanner'],
            ],
            'fulfilled' => static function (ResponseInterface $response, int|string $url) use (&$statuses): void {
                $statuses[(string)$url] = $response->getStatusCode();
            },
            'rejected' => static function (mixed $reason, int|string $url) use (&$statuses): void {
                $statuses[(string)$url] = 'error';
            },
        ]);

        $pool->promise()->wait();

        return $statuses;
    }

    /**
     * @param list<string> $urls
     *
     * @return Generator<string, Request>
     */
    private function requests(array $urls): Generator
    {
        foreach ($urls as $url) {
            yield $url => new Request('GET', $url);
        }
    }
}
