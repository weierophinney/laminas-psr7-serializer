<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Serializer\Request;

use InvalidArgumentException;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Serializer\RelativeStream;
use Laminas\Diactoros\Serializer\Request\StringSerializer;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\Uri;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;

use function json_encode;
use function strlen;

class StringSerializerTest extends TestCase
{
    private StringSerializer $serializer;

    public function setUp(): void
    {
        $this->serializer = new StringSerializer(
            new RequestFactory(),
            new UriFactory(),
            new StreamFactory()
        );
    }

    public function testSerializesBasicRequest(): void
    {
        $request = (new Request())
            ->withMethod('GET')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'text/html');

        $message = $this->serializer->toString($request);
        $this->assertSame(
            "GET /foo/bar?baz=bat HTTP/1.1\r\nHost: example.com\r\nAccept: text/html",
            $message
        );
    }

    public function testSerializesRequestWithBody(): void
    {
        $body   = json_encode(['test' => 'value']);
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($body);

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $message = $this->serializer->toString($request);
        $this->assertStringContainsString("POST /foo/bar HTTP/1.1\r\n", $message);
        $this->assertStringContainsString("\r\n\r\n" . $body, $message);
    }

    public function testSerializesMultipleHeadersCorrectly(): void
    {
        $request = (new Request())
            ->withMethod('GET')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat');

        $message = $this->serializer->toString($request);
        $this->assertStringContainsString("X-Foo-Bar: Baz", $message);
        $this->assertStringContainsString("X-Foo-Bar: Bat", $message);
    }

    public function originForms(): iterable
    {
        return [
            'path-only'      => [
                'GET /foo HTTP/1.1',
                '/foo',
                ['getPath' => '/foo'],
            ],
            'path-and-query' => [
                'GET /foo?bar HTTP/1.1',
                '/foo?bar',
                ['getPath' => '/foo', 'getQuery' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider originForms
     * @psalm-param array<string, string> $expectations
     */
    public function testCanDeserializeRequestWithOriginForm(
        string $line,
        string $requestTarget,
        array $expectations
    ): void {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";
        $request = $this->serializer->fromString($message);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($requestTarget, $request->getRequestTarget());

        $uri = $request->getUri();
        foreach ($expectations as $method => $expect) {
            $this->assertSame($expect, $uri->{$method}());
        }
    }

    public function absoluteForms(): iterable
    {
        return [
            'path-only'      => [
                'GET http://example.com/foo HTTP/1.1',
                'http://example.com/foo',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPath'   => '/foo',
                ],
            ],
            'path-and-query' => [
                'GET http://example.com/foo?bar HTTP/1.1',
                'http://example.com/foo?bar',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPath'   => '/foo',
                    'getQuery'  => 'bar',
                ],
            ],
            'with-port'      => [
                'GET http://example.com:8080/foo?bar HTTP/1.1',
                'http://example.com:8080/foo?bar',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPort'   => 8080,
                    'getPath'   => '/foo',
                    'getQuery'  => 'bar',
                ],
            ],
            'with-authority' => [
                'GET https://me:too@example.com:8080/foo?bar HTTP/1.1',
                'https://me:too@example.com:8080/foo?bar',
                [
                    'getScheme'   => 'https',
                    'getUserInfo' => 'me:too',
                    'getHost'     => 'example.com',
                    'getPort'     => 8080,
                    'getPath'     => '/foo',
                    'getQuery'    => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider absoluteForms
     * @psalm-param array<string, string|int> $expectations
     */
    public function testCanDeserializeRequestWithAbsoluteForm(
        string $line,
        string $requestTarget,
        array $expectations
    ): void {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";
        $request = $this->serializer->fromString($message);

        $this->assertSame('GET', $request->getMethod());

        $this->assertSame($requestTarget, $request->getRequestTarget());

        $uri = $request->getUri();
        foreach ($expectations as $method => $expect) {
            $this->assertSame($expect, $uri->{$method}());
        }
    }

    public function testCanDeserializeRequestWithAuthorityForm(): void
    {
        $message = "CONNECT www.example.com:80 HTTP/1.1\r\nX-Foo-Bar: Baz";
        $request = $this->serializer->fromString($message);
        $this->assertSame('CONNECT', $request->getMethod());
        $this->assertSame('www.example.com:80', $request->getRequestTarget());

        $uri = $request->getUri();
        $this->assertNotSame('www.example.com', $uri->getHost());
        $this->assertNotSame(80, $uri->getPort());
    }

    public function testCanDeserializeRequestWithAsteriskForm(): void
    {
        $message = "OPTIONS * HTTP/1.1\r\nHost: www.example.com";
        $request = $this->serializer->fromString($message);
        $this->assertSame('OPTIONS', $request->getMethod());
        $this->assertSame('*', $request->getRequestTarget());

        $uri = $request->getUri();
        $this->assertNotSame('www.example.com', $uri->getHost());

        $this->assertTrue($request->hasHeader('Host'));
        $this->assertSame('www.example.com', $request->getHeaderLine('Host'));
    }

    public function invalidRequestLines(): iterable
    {
        return [
            'missing-method'   => ['/foo/bar HTTP/1.1'],
            'missing-target'   => ['GET HTTP/1.1'],
            'missing-protocol' => ['GET /foo/bar'],
            'simply-malformed' => ['What is this mess?'],
        ];
    }

    /**
     * @dataProvider invalidRequestLines
     */
    public function testRaisesExceptionDuringDeserializationForInvalidRequestLine(string $line): void
    {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";

        $this->expectException(UnexpectedValueException::class);

        $this->serializer->fromString($message);
    }

    public function testCanDeserializeResponseWithMultipleHeadersOfSameName(): void
    {
        $text    = "POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\nX-Foo-Bar: Bat\r\n\r\nContent!";
        $request = $this->serializer->fromString($text);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertTrue($request->hasHeader('X-Foo-Bar'));
        $values = $request->getHeader('X-Foo-Bar');
        $this->assertSame(['Baz', 'Bat'], $values);
    }

    public function headersWithContinuationLines(): iterable
    {
        return [
            'space' => ["POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\nContent!"],
            'tab'   => ["POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n\tBat\r\n\r\nContent!"],
        ];
    }

    /**
     * @dataProvider headersWithContinuationLines
     */
    public function testCanDeserializeResponseWithHeaderContinuations(string $text): void
    {
        $request = $this->serializer->fromString($text);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertTrue($request->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz;Bat', $request->getHeaderLine('X-Foo-Bar'));
    }

    public function messagesWithInvalidHeaders(): iterable
    {
        return [
            'invalid-name'         => [
                "GET /foo HTTP/1.1\r\nThi;-I()-Invalid: value",
                'Invalid header detected',
            ],
            'invalid-format'       => [
                "POST /foo HTTP/1.1\r\nThis is not a header\r\n\r\nContent",
                'Invalid header detected',
            ],
            'invalid-continuation' => [
                "POST /foo HTTP/1.1\r\nX-Foo-Bar: Baz\r\nInvalid continuation\r\nContent",
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

    public function testFromStreamStopsReadingAfterScanningHeader(): void
    {
        $headers = "POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\n";
        $payload = $headers . "Content!";

        /** @var StreamInterface&MockObject $stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $stream
            ->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));

        // assert that full request body is not read, and returned as RelativeStream instead
        $stream->expects($this->exactly(strlen($headers)))
            ->method('read')
            ->with(1)
            ->will($this->returnCallback(function () use ($payload) {
                static $i = 0;
                return $payload[$i++];
            }));

        $stream = $this->serializer->fromStream($stream);

        $this->assertInstanceOf(RelativeStream::class, $stream->getBody());
    }
}
