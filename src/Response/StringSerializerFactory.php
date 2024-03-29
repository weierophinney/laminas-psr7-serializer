<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Response;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StringSerializerFactory
{
    public function __invoke(ContainerInterface $container): StringSerializer
    {
        return new StringSerializer(
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    }
}
