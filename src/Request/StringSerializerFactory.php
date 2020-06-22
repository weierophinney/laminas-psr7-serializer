<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-diactoros-serializer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-diactoros-serializer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer\Request;

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
