<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

class TcpProbe
{
    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public function __construct(private readonly int $timeout = 2)
    {
    }

    /**
     * Every port is dialled at once and the whole probe waits at most one timeout.
     *
     * @param list<int> $ports
     *
     * @return array<int, string>
     */
    public function states(string $ip, array $ports): array
    {
        $host = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? sprintf('[%s]', $ip) : $ip;
        $states = [];
        $pending = [];

        foreach ($ports as $port) {
            $states[$port] = self::CLOSED;

            $socket = @stream_socket_client(
                sprintf('tcp://%s:%d', $host, $port),
                $errorCode,
                $errorMessage,
                $this->timeout,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
            );

            if ($socket !== false) {
                $pending[$port] = $socket;
            }
        }

        $deadline = microtime(true) + $this->timeout;

        while ($pending !== [] && ($left = $deadline - microtime(true)) > 0) {
            $read = null;
            $write = array_values($pending);
            $except = null;
            $seconds = (int)$left;

            if (@stream_select($read, $write, $except, $seconds, (int)(($left - $seconds) * 1_000_000)) < 1) {
                break;
            }

            foreach ($write as $socket) {
                $port = array_search($socket, $pending, true);

                if ($port === false) {
                    continue;
                }

                if (stream_socket_get_name($socket, true) !== false) {
                    $states[$port] = self::OPEN;
                }

                fclose($socket);
                unset($pending[$port]);
            }
        }

        foreach ($pending as $socket) {
            fclose($socket);
        }

        return $states;
    }
}
