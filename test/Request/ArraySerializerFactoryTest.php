<?php

declare(strict_types=1);

namespace LaminasTest\Psr7\Serializer\Request;

use Laminas\Psr7\Serializer\Request\ArraySerializer;
use Laminas\Psr7\Serializer\Request\ArraySerializerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ArraySerializerFactoryTest extends TestCase
{
    public function testFactoryReturnsArraySerializerInstanceComposingRequestAndStreamFactories(): void
    {
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $streamFactory  = $this->createMock(StreamFactoryInterface::class);
        $container      = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(RequestFactoryInterface::class)],
                [$this->equalTo(StreamFactoryInterface::class)]
            )
            ->will($this->returnValueMap([
                [RequestFactoryInterface::class, $requestFactory],
                [StreamFactoryInterface::class, $streamFactory],
            ]));

        $factory  = new ArraySerializerFactory();
        $instance = $factory($container);

        $this->assertInstanceOf(ArraySerializer::class, $instance);
    }
}
