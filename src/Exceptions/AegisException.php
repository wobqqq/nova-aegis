<?php

declare(strict_types=1);

namespace Wobqqq\Aegis\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * A request Aegis refuses for a reason the administrator can act on. The message is translated and shown as it is;
 * the error is not logged.
 */
abstract class AegisException extends RuntimeException implements ShouldntReport
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return 422;
    }

    public function render(): JsonResponse
    {
        return new JsonResponse(['message' => $this->getMessage()], $this->status());
    }
}
