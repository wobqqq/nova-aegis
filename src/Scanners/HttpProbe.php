<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class HttpProbe
{
    public function __construct(
        private readonly ?Client $client = null,
        private readonly int $timeout = 10,
        private readonly int $concurrency = 7,
    ) {
    }

    /**
     * The status code of each URL, or "error". Redirects are not followed: a sensitive path
     * redirected to the home page would answer 200 there and read as exposed.
     *
     * @param list<string> $urls
     *
     * @return array<string, int|string>
     */
    public function statuses(array $urls): array
    {
        $client = $this->client ?? new Client();
        $requests = static function (string ...$urls) {
            foreach ($urls as $url) {
                yield new Request('GET', $url);
            }
        };

        $statuses = [];

        $pool = new Pool($client, $requests(...$urls), [
            'concurrency' => $this->concurrency,
            'options' => [
                'timeout' => $this->timeout,
                'connect_timeout' => min(5, $this->timeout),
                'http_errors' => false,
                'allow_redirects' => false,
                'headers' => ['User-Agent' => 'Aegis sensitive files scanner'],
            ],
            'fulfilled' => static function (ResponseInterface $response, int $index) use (&$statuses, $urls): void {
                $statuses[$urls[$index]] = $response->getStatusCode();
            },
            'rejected' => static function (mixed $reason, int $index) use (&$statuses, $urls): void {
                $statuses[$urls[$index]] = 'error';
            },
        ]);

        $pool->promise()->wait();

        return $statuses;
    }
}
