<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Serializer\Response;

use InvalidArgumentException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\Serializer\Response\StringSerializer;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;

class StringSerializerTest extends TestCase
{
    private StringSerializer $serializer;

    public function setUp(): void
    {
        $this->serializer = new StringSerializer(
            new ResponseFactory(),
            new StreamFactory()
        );
    }

    public function testSerializesBasicResponse(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain')
            ->withAddedHeader('X-Foo-Bar', 'Baz');
        $response->getBody()->write('Content!');

        $message = $this->serializer->toString($response);
        $this->assertSame(
            "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\n\r\nContent!",
            $message
        );
    }

    public function testSerializesResponseWithoutBodyCorrectly(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');

        $message = $this->serializer->toString($response);
        $this->assertSame(
            "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\n",
            $message
        );
    }

    public function testSerializesMultipleHeadersCorrectly(): void
    {
        $response = (new Response())
            ->withStatus(204)
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat');

        $message = $this->serializer->toString($response);
        $this->assertStringContainsString("X-Foo-Bar: Baz", $message);
        $this->assertStringContainsString("X-Foo-Bar: Bat", $message);
    }

    public function testOmitsReasonPhraseFromStatusLineIfEmpty(): void
    {
        $response = (new Response())
            ->withStatus(299)
            ->withAddedHeader('X-Foo-Bar', 'Baz');
        $response->getBody()->write('Content!');

        $message = $this->serializer->toString($response);
        $this->assertStringContainsString("HTTP/1.1 299\r\n", $message);
    }

    public function testCanDeserializeBasicResponse(): void
    {
        $text     = "HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\n\r\nContent!";
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('A-OK', $response->getReasonPhrase());

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $this->assertSame('Content!', (string) $response->getBody());
    }

    public function testCanDeserializeResponseWithMultipleHeadersOfSameName(): void
    {
        $text     = "HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\nX-Foo-Bar: Bat\r\n\r\nContent!";
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $values = $response->getHeader('X-Foo-Bar');
        $this->assertSame(['Baz', 'Bat'], $values);
    }

    public function headersWithContinuationLines(): iterable
    {
        return [
            'space' => ["HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\nContent!"],
            'tab'   => ["HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n\tBat\r\n\r\nContent!"],
        ];
    }

    /**
     * @dataProvider headersWithContinuationLines
     */
    public function testCanDeserializeResponseWithHeaderContinuations(string $text): void
    {
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz;Bat', $response->getHeaderLine('X-Foo-Bar'));
    }

    public function testCanDeserializeResponseWithoutBody(): void
    {
        $text     = "HTTP/1.0 204\r\nX-Foo-Bar: Baz";
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersOrBody(): void
    {
        $text     = "HTTP/1.0 204";
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersButContainingBody(): void
    {
        $text     = "HTTP/1.0 204\r\n\r\nContent!";
        $response = $this->serializer->fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertSame('Content!', $body);
    }

    public function testDeserializationRaisesExceptionForInvalidStatusLine(): void
    {
        $text = "This is an invalid status line\r\nX-Foo-Bar: Baz\r\n\r\nContent!";

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('status line');

        $this->serializer->fromString($text);
    }

    public function messagesWithInvalidHeaders(): iterable
    {
        return [
            'invalid-name'         => [
                "HTTP/1.1 204\r\nThi;-I()-Invalid: value",
                'Invalid header detected',
            ],
            'invalid-format'       => [
                "HTTP/1.1 204\r\nThis is not a header\r\n\r\nContent",
                'Invalid header detected',
            ],
            'invalid-continuation' => [
                "HTTP/1.1 204\r\nX-Foo-Bar: Baz\r\nInvalid continuation\r\nContent",
                'Invalid header continuation',
            ],
        ];
    }

    /**
     * @dataProvider messagesWithInvalidHeaders
     */
    public function testDeserializationRaisesExceptionForMalformedHeaders(
        string $message,
        string $exceptionMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->serializer->fromString($message);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotReadable(): void
    {
        /** @var StreamInterface&MockObject $stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));

        $this->expectException(InvalidArgumentException::class);

        $this->serializer->fromStream($stream);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotSeekable(): void
    {
        /** @var StreamInterface&MockObject $stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $stream
            ->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(false));

        $this->expectException(InvalidArgumentException::class);

        $this->serializer->fromStream($stream);
    }

    /**
     * @see https://github.com/zendframework/zend-diactoros/issues/113
     */
    public function testDeserializeCorrectlyCastsStatusCodeToInteger(): void
    {
        $response = $this->serializer->fromString('HTTP/1.0 204');
        // according to interface the int is expected
        $this->assertSame(204, $response->getStatusCode());
    }
}
