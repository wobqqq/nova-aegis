<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Tests\Support;

use RuntimeException;

final class Sockets
{
    /**
     * @return array{0: resource, 1: int} the listening socket and its port
     */
    public static function listen(string $address = '127.0.0.1'): array
    {
        $server = @stream_socket_server(sprintf('tcp://%s:0', $address), $errorCode, $errorMessage);

        throw_if($server === false, RuntimeException::class, (string)$errorMessage);

        $name = (string)stream_socket_get_name($server, false);
        $separator = strrpos($name, ':');

        return [$server, (int)substr($name, $separator === false ? 0 : $separator + 1)];
    }

    public static function supportsIpv6(): bool
    {
        $server = @stream_socket_server('tcp://[::1]:0');

        if ($server === false) {
            return false;
        }

        fclose($server);

        return true;
    }

    public static function closedPort(): int
    {
        [$server, $port] = self::listen();
        fclose($server);

        return $port;
    }
}
