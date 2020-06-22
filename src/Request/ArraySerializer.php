<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer\Request;

use Laminas\Diactoros\Serializer\AbstractSerializer;
use Laminas\Diactoros\Serializer\Exception;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function fopen;
use function sprintf;

/**
 * Serialize or deserialize request messages to/from arrays.
 *
 * This class provides functionality for serializing a RequestInterface instance
 * to an array, as well as the reverse operation of creating a Request instance
 * from an array representing a message.
 */
final class ArraySerializer extends AbstractSerializer
{
    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory  = $streamFactory;
    }

    /**
     * Deserialize a request array to a request instance.
     *
     * Expected keys:
     *
     * - string|UriInterface uri
     * - string method
     * - string body
     * - array<string, string|string[]> headers
     * - string request_target
     * - string protocol_version
     *
     * @throws Exception\DeserializationException When unable to deserialize response.
     */
    public function fromArray(array $serializedRequest): RequestInterface
    {
        try {
            $stream          = fopen('php://temp', 'wb+');
            $uri             = $this->getValueFromKey($serializedRequest, 'uri');
            $method          = $this->getValueFromKey($serializedRequest, 'method');
            $body            = $this->streamFactory->createStreamFromResource($stream);
            $headers         = $this->getValueFromKey($serializedRequest, 'headers');
            $requestTarget   = $this->getValueFromKey($serializedRequest, 'request_target');
            $protocolVersion = $this->getValueFromKey($serializedRequest, 'protocol_version');

            $body->write($this->getValueFromKey($serializedRequest, 'body'));

            $request = $this->requestFactory->createRequest($method, $uri)
                ->withProtocolVersion($protocolVersion)
                ->withRequestTarget($requestTarget)
                ->withBody($body);

            return $this->injectHeaders($headers, $request);
        } catch (Throwable $exception) {
            throw Exception\DeserializationException::forRequestFromArray($exception);
        }
    }

    /**
     * Serialize a request message to an array.
     *
     * Returns an associative array with the following keys:
     *
     * - string|UriInterface uri
     * - string method
     * - string body
     * - array<string, string|string[]> headers
     * - string request_target
     * - string protocol_version
     */
    public function toArray(RequestInterface $request): array
    {
        return [
            'method'           => $request->getMethod(),
            'request_target'   => $request->getRequestTarget(),
            'uri'              => (string) $request->getUri(),
            'protocol_version' => $request->getProtocolVersion(),
            'headers'          => $request->getHeaders(),
            'body'             => (string) $request->getBody(),
        ];
    }

    /**
     * @return mixed
     * @throws Exception\DeserializationException
     */
    private function getValueFromKey(array $data, string $key, ?string $message = null)
    {
        if (! isset($data[$key])) {
            $message = $message ?: sprintf('Missing "%s" key in serialized request', $key);
            throw new Exception\DeserializationException($message);
        }

        return $data[$key];
    }
}
