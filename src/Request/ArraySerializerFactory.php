<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer\Request;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ArraySerializerFactory
{
    public function __invoke(ContainerInterface $container): ArraySerializer
    {
        return new ArraySerializer(
            $container->get(RequestFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    }
}
