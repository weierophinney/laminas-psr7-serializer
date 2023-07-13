<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Exception;

use RuntimeException;
use Throwable;

class InvalidStreamPointerPositionException extends RuntimeException implements ExceptionInterface
{
    public function __construct(
        string $message = 'Invalid pointer position',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
