<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace Laminas\Diactoros\Serializer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                Request\ArraySerializer::class   => Request\ArraySerializerFactory::class,
                Request\StringSerializer::class  => Request\StringSerializerFactory::class,
                Response\ArraySerializer::class  => Response\ArraySerializerFactory::class,
                Response\StringSerializer::class => Response\StringSerializerFactory::class,
            ],
        ];
    }
}
