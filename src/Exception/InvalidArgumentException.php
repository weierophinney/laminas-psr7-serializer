<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
}
