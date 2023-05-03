<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Response;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\Serializer\Response\ArraySerializer;
use Laminas\Diactoros\Serializer\Response\StringSerializer;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class ArraySerializerTest extends TestCase
{
    private ArraySerializer $serializer;

    public function setUp(): void
    {
        $this->serializer = new ArraySerializer(
            new ResponseFactory(),
            new StreamFactory()
        );
    }

    public function testSerializeToArray(): void
    {
        $response = $this->createResponse();

        $message = $this->serializer->toArray($response);

        $this->assertSame($this->createSerializedResponse(), $message);
    }

    public function testDeserializeFromArray(): void
    {
        $serializedResponse = $this->createSerializedResponse();

        $message = $this->serializer->fromArray($serializedResponse);

        $response = $this->createResponse();

        $stringSerializer = new StringSerializer(new ResponseFactory(), new StreamFactory());

        $this->assertSame($stringSerializer->toString($response), $stringSerializer->toString($message));
    }

    public function testMissingBodyParamInSerializedRequestThrowsException(): void
    {
        $serializedRequest = $this->createSerializedResponse();
        unset($serializedRequest['body']);

        $this->expectException(UnexpectedValueException::class);

        $this->serializer->fromArray($serializedRequest);
    }

    private function createResponse(): ResponseInterface
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        return (new Response())
            ->withStatus(201, 'Custom')
            ->withProtocolVersion('1.1')
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);
    }

    private function createSerializedResponse(): array
    {
        return [
            'status_code'      => 201,
            'reason_phrase'    => 'Custom',
            'protocol_version' => '1.1',
            'headers'          => [
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
            'body'             => '{"test":"value"}',
        ];
    }
}
