# Factories

This package provides a [ConfigProvider](https://docs.laminas.dev/laminas-config-aggregator/config-providers/), which provides mapping of each of the serializer implementations to a factory capable of producing an instance.

If you are installing the package in a Mezzio application, or any application using the [laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/) Composer plugin, you should be prompted during initial installation to inject the `ConfigProvider` class in your application configuration; if not, please follow the [installation instructions in the introduction](intro.md).

In order for these to work, you will need a [PSR-17 (HTTP Message Factories)](https://www.php-fig.org/psr/psr-17/) implementation installed, and have the following services registered in your application dependency configuration:

- `Psr\Http\Message\RequestFactoryInterface` (if de/serializing PSR-7 requests)
- `Psr\Http\Message\ResponseFactoryInterface` (if de/serializing PSR-7 responses)
- `Psr\Http\Message\StreamFactoryInterface` (for all message types)
- `Psr\Http\Message\UriFactoryInterface` (for de/serializing PSR-7 requests)

Some PSR-17 providers do this for you already; [Diactoros](https://docs.laminas.dev/laminas-diactoros) is one.

This package exposes the following services to containers:

- `Laminas\Psr7\Serializer\Request\ArraySerializer`
- `Laminas\Psr7\Serializer\Request\StringSerializer`
- `Laminas\Psr7\Serializer\Response\ArraySerializer`
- `Laminas\Psr7\Serializer\Response\StringSerializer`

When the services are registered, you can pull them from the container using their class names in order to inject them in objects that require them.
