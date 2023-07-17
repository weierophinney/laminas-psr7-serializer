<?php

declare(strict_types=1);

namespace LaminasTest\Psr7\Serializer\Request;

use Laminas\Psr7\Serializer\Request\StringSerializer;
use Laminas\Psr7\Serializer\Request\StringSerializerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class StringSerializerFactoryTest extends TestCase
{
    public function testFactoryReturnsStringSerializerComposingRequestUriAndStreamFactories(): void
    {
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $uriFactory     = $this->createMock(UriFactoryInterface::class);
        $streamFactory  = $this->createMock(StreamFactoryInterface::class);
        $container      = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(fn (string $serviceName) => match ($serviceName) {
                RequestFactoryInterface::class  => $requestFactory,
                StreamFactoryInterface::class   => $streamFactory,
                UriFactoryInterface::class      => $uriFactory,
            });

        $factory  = new StringSerializerFactory();
        $instance = $factory($container);

        $this->assertInstanceOf(StringSerializer::class, $instance);
    }
}
