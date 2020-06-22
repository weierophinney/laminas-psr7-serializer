<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer;

use Psr\Http\Message\StreamInterface;

use const SEEK_SET;

/**
 * Wrapper for default Stream class, representing subpart (starting from given offset) of initial stream.
 * It can be used to avoid copying full stream, conserving memory.
 *
 * @example see Laminas\Diactoros\Serializer\AbstractStringSerializer::splitStream()
 */
final class RelativeStream implements StreamInterface
{
    /** @var StreamInterface */
    private $decoratedStream;

    /** @var int */
    private $offset;

    public function __construct(StreamInterface $decoratedStream, ?int $offset)
    {
        $this->decoratedStream = $decoratedStream;
        $this->offset          = (int) $offset;
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }
        return $this->getContents();
    }

    public function close(): void
    {
        $this->decoratedStream->close();
    }

    // Disabling rules to avoid issues when inheriting signatures.
    // phpcs:disable WebimpressCodingStandard.Functions.Param.MissingSpecification, WebimpressCodingStandard.Functions.ReturnType.ReturnValue

    public function detach()
    {
        return $this->decoratedStream->detach();
    }

    public function getSize(): int
    {
        return $this->decoratedStream->getSize() - $this->offset;
    }

    public function tell(): int
    {
        return $this->decoratedStream->tell() - $this->offset;
    }

    public function eof(): bool
    {
        return $this->decoratedStream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->decoratedStream->isSeekable();
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($whence === SEEK_SET) {
            $this->decoratedStream->seek($offset + $this->offset, $whence);
            return;
        }
        $this->decoratedStream->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->decoratedStream->isWritable();
    }

    public function write($string): int
    {
        if ($this->tell() < 0) {
            throw new Exception\InvalidStreamPointerPositionException();
        }
        return $this->decoratedStream->write($string);
    }

    public function isReadable(): bool
    {
        return $this->decoratedStream->isReadable();
    }

    public function read($length): string
    {
        if ($this->tell() < 0) {
            throw new Exception\InvalidStreamPointerPositionException();
        }
        return $this->decoratedStream->read($length);
    }

    public function getContents(): string
    {
        if ($this->tell() < 0) {
            throw new Exception\InvalidStreamPointerPositionException();
        }
        return $this->decoratedStream->getContents();
    }

    public function getMetadata($key = null)
    {
        return $this->decoratedStream->getMetadata($key);
    }

    // phpcs:enable
}
