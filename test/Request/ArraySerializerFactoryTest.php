<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Serializer\Request;

use Laminas\Diactoros\Serializer\Request\ArraySerializer;
use Laminas\Diactoros\Serializer\Request\ArraySerializerFactory;
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
