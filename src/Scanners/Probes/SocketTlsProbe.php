<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Scanners;

use Illuminate\Support\Carbon;
use OpenSSLCertificate;

class TlsProbe
{
    public function __construct(private readonly int $timeout = 10)
    {
    }

    /**
     * The certificate's validity dates, or null when no valid certificate is presented.
     *
     * @return array{issued_on: Carbon, expires_on: Carbon}|null
     */
    public function certificate(string $host, int $port): ?array
    {
        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ]]);

        $client = @stream_socket_client(sprintf('ssl://%s:%d', $host, $port), $errorCode, $errorMessage, $this->timeout, STREAM_CLIENT_CONNECT, $context);

        if ($client === false) {
            return null;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $ssl = is_array($params['options']['ssl'] ?? null) ? $params['options']['ssl'] : [];
        $certificate = $ssl['peer_certificate'] ?? null;
        $data = $certificate instanceof OpenSSLCertificate ? openssl_x509_parse($certificate) : false;

        if (!is_array($data) || !is_int($data['validFrom_time_t'] ?? null) || !is_int($data['validTo_time_t'] ?? null)) {
            return null;
        }

        return [
            'issued_on' => \Illuminate\Support\Facades\Date::createFromTimestamp($data['validFrom_time_t']),
            'expires_on' => \Illuminate\Support\Facades\Date::createFromTimestamp($data['validTo_time_t']),
        ];
    }
}
