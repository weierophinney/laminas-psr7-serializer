<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer\Response;

use Laminas\Diactoros\Serializer\AbstractStringSerializer;
use Laminas\Diactoros\Serializer\Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

use function fopen;
use function preg_match;
use function sprintf;

final class StringSerializer extends AbstractStringSerializer
{
    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
    }

    /**
     * Deserialize a response string to a response instance.
     *
     * @throws Exception\SerializationException When errors occur parsing the message.
     */
    public function fromString(string $message): ResponseInterface
    {
        $resource = fopen('php://temp', 'wb+');
        $stream   = $this->streamFactory->createStreamFromResource($resource);
        $stream->write($message);
        return $this->fromStream($stream);
    }

    /**
     * Parse a response from a stream.
     *
     * @throws Exception\InvalidArgumentException When the stream is not readable.
     * @throws Exception\SerializationException When errors occur parsing the message.
     */
    public function fromStream(StreamInterface $stream): ResponseInterface
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new Exception\InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        ['version' => $version, 'status' => $status, 'reason' => $reasonPhrase] = $this->getStatusLine($stream);

        ['headers' => $headers, 'body' => $body] = $this->splitStream($stream);

        $response = $this->responseFactory->createResponse((int) $status, $reasonPhrase)
            ->withProtocolVersion($version)
            ->withBody($body);

        return $this->injectHeaders($headers, $response);
    }

    /**
     * Create a string representation of a response.
     */
    public function toString(ResponseInterface $response): string
    {
        $reasonPhrase = $response->getReasonPhrase();
        $headers      = $this->serializeHeaders($response->getHeaders());
        $body         = (string) $response->getBody();
        $format       = 'HTTP/%s %d%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }

        $headers .= "\r\n\r\n";

        return sprintf(
            $format,
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $reasonPhrase ? ' ' . $reasonPhrase : '',
            $headers,
            $body
        );
    }

    /**
     * Retrieve the status line for the message.
     *
     * @return array Array with three elements (by key): version, status, reason
     * @throws Exception\SerializationException If line is malformed.
     */
    private function getStatusLine(StreamInterface $stream): array
    {
        $line = $this->getLine($stream);

        if (
            ! preg_match(
                '#^HTTP/(?P<version>[1-9]\d*\.\d) (?P<status>[1-5]\d{2})(\s+(?P<reason>.+))?$#',
                $line,
                $matches
            )
        ) {
            throw Exception\SerializationException::forInvalidStatusLine();
        }

        return [
            'version' => $matches['version'],
            'status'  => (int) $matches['status'],
            'reason'  => $matches['reason'] ?? '',
        ];
    }
}
