<?php

declare(strict_types=1);

namespace LaminasTest\Psr7\Serializer;

use Laminas\Psr7\Serializer\ConfigProvider;
use Laminas\Psr7\Serializer\Request;
use Laminas\Psr7\Serializer\Response;
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
