<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer\Exception;

use Throwable;
use UnexpectedValueException;

class DeserializationException extends UnexpectedValueException implements ExceptionInterface
{
    public static function forInvalidHeader(): self
    {
        return new self('Invalid header detected');
    }

    public static function forInvalidHeaderContinuation(): self
    {
        return new self('Invalid header continuation');
    }

    public static function forRequestFromArray(Throwable $previous): self
    {
        return new self('Cannot deserialize request', $previous->getCode(), $previous);
    }

    public static function forResponseFromArray(Throwable $previous): self
    {
        return new self('Cannot deserialize response', $previous->getCode(), $previous);
    }

    public static function forUnexpectedCarriageReturn(): self
    {
        return new self('Unexpected carriage return detected');
    }

    public static function forUnexpectedEndOfHeaders(): self
    {
        return new self('Unexpected end of headers');
    }

    public static function forUnexpectedLineFeed(): self
    {
        return new self('Unexpected line feed detected');
    }
}
