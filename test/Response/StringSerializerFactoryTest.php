<?php

declare(strict_types=1);

namespace LaminasTest\Psr7\Serializer\Response;

use Laminas\Psr7\Serializer\Response\StringSerializer;
use Laminas\Psr7\Serializer\Response\StringSerializerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StringSerializerFactoryTest extends TestCase
{
    public function testFactoryReturnsStringSerializerComposingResponseAndStreamFactories(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $container       = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $serviceName): object => match ($serviceName) {
                ResponseFactoryInterface::class => $responseFactory,
                StreamFactoryInterface::class => $streamFactory,
            });

        $factory  = new StringSerializerFactory();
        $instance = $factory($container);

        $this->assertInstanceOf(StringSerializer::class, $instance);
    }
}
