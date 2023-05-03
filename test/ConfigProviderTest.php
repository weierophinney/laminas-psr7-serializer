<?php

/**
 * @see       https://github.com/laminas/laminas-diactoros-serializer for the canonical source repository
 */

declare(strict_types=1);

namespace LaminasTest\Diactoros\Serializer;

use Laminas\Diactoros\Serializer\ConfigProvider;
use Laminas\Diactoros\Serializer\Request;
use Laminas\Diactoros\Serializer\Response;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testProvidesFactoriesForEachSerializer(): void
    {
        $provider = new ConfigProvider();
        $config   = $provider();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);

        $dependencies = $config['dependencies'];
        $this->assertIsArray($dependencies);
        $this->assertArrayHasKey('factories', $dependencies);

        $factories = $dependencies['factories'];

        $this->assertArrayHasKey(Request\ArraySerializer::class, $factories);
        $this->assertArrayHasKey(Request\StringSerializer::class, $factories);
        $this->assertArrayHasKey(Response\ArraySerializer::class, $factories);
        $this->assertArrayHasKey(Response\StringSerializer::class, $factories);
    }
}
