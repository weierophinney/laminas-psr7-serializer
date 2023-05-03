<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer\Response;

use Laminas\Diactoros\Exception;
use Laminas\Diactoros\Serializer\AbstractSerializer;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function fopen;
use function sprintf;

/**
 * Serialize or deserialize response messages to/from arrays.
 *
 * This class provides functionality for serializing a ResponseInterface instance
 * to an array, as well as the reverse operation of creating a Response instance
 * from an array representing a message.
 */
final class ArraySerializer extends AbstractSerializer
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
     * Deserialize a response array to a response instance.
     *
     * Creates a response instance from an array that contains the following
     * keys:
     *
     * - int|string status_code
     * - string reason_phrase
     * - int|float|string protocol_version
     * - array<string, string|string[]> headers
     * - string body
     *
     * @throws Exception\DeserializationException When cannot deserialize response.
     */
    public function fromArray(array $serializedResponse): ResponseInterface
    {
        try {
            $stream = fopen('php://temp', 'wb+');
            $body   = $this->streamFactory->createStreamFromResource($stream);
            $body->write($this->getValueFromKey($serializedResponse, 'body'));

            $statusCode      = $this->getValueFromKey($serializedResponse, 'status_code');
            $headers         = $this->getValueFromKey($serializedResponse, 'headers');
            $protocolVersion = $this->getValueFromKey($serializedResponse, 'protocol_version');
            $reasonPhrase    = $this->getValueFromKey($serializedResponse, 'reason_phrase');

            $response = $this->responseFactory->createResponse((int) $statusCode, $reasonPhrase)
                ->withProtocolVersion($protocolVersion)
                ->withBody($body);

            return $this->injectHeaders($headers, $response);
        } catch (Throwable $exception) {
            throw Exception\DeserializationException::forResponseFromArray($exception);
        }
    }

    /**
     * Serialize a response message to an array.
     *
     * Returns an array that contains the following keys:
     *
     * - int|string status_code
     * - string reason_phrase
     * - int|float|string protocol_version
     * - array<string, string|string[]> headers
     * - string body
     */
    public function toArray(ResponseInterface $response): array
    {
        return [
            'status_code'      => $response->getStatusCode(),
            'reason_phrase'    => $response->getReasonPhrase(),
            'protocol_version' => $response->getProtocolVersion(),
            'headers'          => $response->getHeaders(),
            'body'             => (string) $response->getBody(),
        ];
    }

    /**
     * @return mixed
     * @throws UnexpectedValueException
     */
    private function getValueFromKey(array $data, string $key, ?string $message = null)
    {
        if (! isset($data[$key])) {
            $message = $message ?: sprintf('Missing "%s" key in serialized response', $key);
            throw new Exception\DeserializationException($message);
        }

        return $data[$key];
    }
}
