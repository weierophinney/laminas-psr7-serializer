<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Serializer\Request;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Serializer\Request\ArraySerializer;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class ArraySerializerTest extends TestCase
{
    private ArraySerializer $serializer;

    public function setUp(): void
    {
        $this->serializer = new ArraySerializer(
            new RequestFactory(),
            new StreamFactory()
        );
    }

    public function testSerializeToArray(): void
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $message = $this->serializer->toArray($request);

        $this->assertSame([
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
            'body'             => '{"test":"value"}',
        ], $message);
    }

    public function testDeserializeFromArray(): void
    {
        $serializedRequest = [
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
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

        $message = $this->serializer->fromArray($serializedRequest);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $this->assertSame(Request\Serializer::toString($request), Request\Serializer::toString($message));
    }

    public function testMissingBodyParamInSerializedRequestThrowsException(): void
    {
        $serializedRequest = [
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
        ];

        $this->expectException(UnexpectedValueException::class);

        $this->serializer->fromArray($serializedRequest);
    }
}
