<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Request;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class StringSerializerFactory
{
    public function __invoke(ContainerInterface $container): StringSerializer
    {
        return new StringSerializer(
            $container->get(RequestFactoryInterface::class),
            $container->get(UriFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    }
}
