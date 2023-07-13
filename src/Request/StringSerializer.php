<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Request;

use Laminas\Psr7\Serializer\AbstractStringSerializer;
use Laminas\Psr7\Serializer\Exception;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function fopen;
use function preg_match;
use function sprintf;

/**
 * Serialize (cast to string) or deserialize (cast string to Request) messages.
 *
 * This class provides functionality for serializing a RequestInterface instance
 * to a string, as well as the reverse operation of creating a Request instance
 * from a string or stream representing a message.
 */
final class StringSerializer extends AbstractStringSerializer
{
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private UriFactoryInterface $uriFactory;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->uriFactory     = $uriFactory;
        $this->streamFactory  = $streamFactory;
    }

    /**
     * Deserialize a request string to a request instance.
     *
     * Internally, casts the message to a stream and invokes fromStream().
     *
     * @throws Exception\SerializationException When errors occur parsing the message.
     */
    public function fromString(string $message): RequestInterface
    {
        $fh     = fopen('php://temp', 'wb+');
        $stream = $this->streamFactory->createStreamFromResource($fh);
        $stream->write($message);
        return $this->fromStream($stream);
    }

    /**
     * Deserialize a request stream to a request instance.
     *
     * @throws Exception\InvalidArgumentException If the message stream is not
     *     readable or seekable.
     * @throws Exception\SerializationException If an invalid request line is detected.
     */
    public function fromStream(StreamInterface $stream): RequestInterface
    {
        if (! $stream->isReadable() || ! $stream->isSeekable()) {
            throw new Exception\InvalidArgumentException('Message stream must be both readable and seekable');
        }

        $stream->rewind();

        ['method' => $method, 'target' => $requestTarget, 'version' => $version] = $this->getRequestLine($stream);
        $uri = $this->createUriFromRequestTarget($requestTarget);

        ['headers' => $headers, 'body' => $body] = $this->splitStream($stream);

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withProtocolVersion($version)
            ->withRequestTarget($requestTarget)
            ->withBody($body);

        return $this->injectHeaders($headers, $request);
    }

    /**
     * Serialize a request message to a string.
     */
    public function toString(RequestInterface $request): string
    {
        $httpMethod = $request->getMethod();
        $headers    = $this->serializeHeaders($request->getHeaders());
        $body       = (string) $request->getBody();
        $format     = '%s %s HTTP/%s%s%s';

        if (! empty($headers)) {
            $headers = "\r\n" . $headers;
        }
        if (! empty($body)) {
            $headers .= "\r\n\r\n";
        }

        return sprintf(
            $format,
            $httpMethod,
            $request->getRequestTarget(),
            $request->getProtocolVersion(),
            $headers,
            $body
        );
    }

    /**
     * Retrieve the components of the request line.
     *
     * Retrieves the first line of the stream and parses it, raising an
     * exception if it does not follow specifications; if valid, returns a list
     * with the method, target, and version, in that order.
     *
     * @throws Exception\SerializationException
     */
    private function getRequestLine(StreamInterface $stream): array
    {
        $requestLine = $this->getLine($stream);

        if (
            ! preg_match(
                '#^(?P<method>[!\#$%&\'*+.^_`|~a-zA-Z0-9-]+) (?P<target>[^\s]+) HTTP/(?P<version>[1-9]\d*\.\d+)$#',
                $requestLine,
                $matches
            )
        ) {
            throw Exception\SerializationException::forInvalidRequestLine();
        }

        return [
            'method'  => $matches['method'],
            'target'  => $matches['target'],
            'version' => $matches['version'],
        ];
    }

    /**
     * Create and return a Uri instance based on the provided request target.
     *
     * If the request target is of authority or asterisk form, an empty Uri
     * instance is returned; otherwise, the value is used to create and return
     * a new Uri instance.
     */
    private function createUriFromRequestTarget(string $requestTarget): UriInterface
    {
        if (preg_match('#^https?://#', $requestTarget)) {
            return $this->uriFactory->createUri($requestTarget);
        }

        if (preg_match('#^(\*|[^/])#', $requestTarget)) {
            return $this->uriFactory->createUri();
        }

        return $this->uriFactory->createUri($requestTarget);
    }
}
