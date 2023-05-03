# Usage

This package provides serialization classes to convert
[PSR-7 (HTTP Message)](https://www.php-fig.org/psr/psr-7/) request and response instances to
and from arrays and strings, exposing the following classes:

- `Laminas\Diactoros\Serializer\Request\ArraySerializer`
- `Laminas\Diactoros\Serializer\Request\StringSerializer`
- `Laminas\Diactoros\Serializer\Response\ArraySerializer`
- `Laminas\Diactoros\Serializer\Response\StringSerializer`

Each uses relevant factories from [PSR-17 (HTTP Message Factories)](https://www.php-fig.org/psr/psr-17/)
to deserialize to the relevant PSR-7 instances, based on the PSR-7
implementation you have installed.

## Serialize PSR-7 Requests to Arrays

Sometimes you may want to keep the structure of an HTTP request when serializing
it, to allow analyzing it more easily. For this purpose, we provide
`Laminas\Diactoros\Serializer\Request\ArraySerializer`, which can both serialize
to an array, as well as deserialize those arrays back to a PSR-7 request.

The array format it uses is as follows:

```php
[
    'method'           => string,
    'request_target'   => string,
    'uri'              => string,
    'protocol_version' => string|int|float,
    'headers'          => array<string, string|string[]>,
    'body'             => string,
]
```

The constructor for `Laminas\Diactoros\Serializer\Request\ArraySerializer`
reads:

```php
public function __construct(
    Psr\Http\Message\RequestFactoryInterface $requestFactory,
    Psr\Http\Message\StreamFactoryInterface $streamFactory
)
```

and the class exposes the following methods:

```php
public function fromArray(array $serializedRequest): Psr\Http\Message\RequestInterface
public function toArray(Psr\Http\Message\RequestInterface $request): array
```

As examples:

```php
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Serializer\Request\ArraySerializer;
use Laminas\Diactoros\StreamFactory;

$serializer = new ArraySerializer(
    new RequestFactory(),
    new StreamFactory()
);

$requestData = [
    'method'           => 'GET',
    'request_target'   => '/api/example',
    'uri'              => 'https://example.com/api/example',
    'protocol_version' => 1.1,
    'headers'          => [
        'Host'   => 'example.com',
        'Accept' => 'application/json',
    ],
    'body'             => '',
];

$request = $serializer->fromArray($requestData);
assert($requestData == $serializer->toArray($request));
```

## Serialize PSR-7 Requests to Strings

If you want to serialize your request to an actual string HTTP message, or
parse an HTTP request into a PSR-7 instance, you can use
`Laminas\Diactoros\Serializer\Request\StringSerializer`.

It's constructor reads:

```php
public function __construct(
    Psr\Http\Message\RequestFactoryInterface $requestFactory,
    Psr\Http\Message\UriFactoryInterface $uriFactory,
    Psr\Http\Message\StreamFactoryInterface $streamFactory
)
```

and it exposes the following methods:

```php
public function fromString(string $message): Psr\Http\Message\RequestInterface
public function fromStream(Psr\Http\Message\StreamInterface $stream): Psr\Http\Message\RequestInterface
public function toString(Psr\Http\Message\RequestInterface $request): string
```

Let's consider the following HTTP request:

```http
POST /api/example HTTP/1.1
Host: www.example.com
Accept: application/json
Content-Type: application/json

{"request": "ping"}
```

Now, we will assign it to the value `$message` in the following example:

```php
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\Serializer\Request\StringSerializer;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;

$serializer = new StringSerializer(
    new RequestFactory(),
    new UriFactory(),
    new StreamFactory()
);

$request = $serializer->fromString($message);
assert($message === $serializer->toString($request));
```

## Serialize PSR-7 Responses to Arrays

Mirroring the functionality for [serializing requests to
arrays](#serialize-psr-7-requests-to-arrays), we provide similar functionality
for responses via the `Laminas\Diactoros\Serializer\Response\ArraySerializer`
class.

The array format it uses is as follows:

```php
[
    'status_code'      => int|string,
    'reason_phrase'    => string,
    'protocol_version' => int|float|string,
    'headers'          => array<string, string|string[]>,
    'body'             => string,
]
```

The constructor for `Laminas\Diactoros\Serializer\Response\ArraySerializer`
reads:

```php
public function __construct(
    Psr\Http\Message\ResponseFactoryInterface $responseFactory,
    Psr\Http\Message\StreamFactoryInterface $streamFactory
)
```

and the class exposes the following methods:

```php
public function fromArray(array $serializedResponse): Psr\Http\Message\ResponseInterface
public function toArray(Psr\Http\Message\ResponseInterface $request): array
```

As examples:

```php
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\Serializer\Response\ArraySerializer;
use Laminas\Diactoros\StreamFactory;

$serializer = new ArraySerializer(
    new ResponseFactory(),
    new StreamFactory()
);

$responseData = [
    'status_code'      => 422,
    'reason_phrase'    => 'Invalid entity',
    'protocol_version' => 1.1,
    'headers'          => [
        'Content-Type' => 'application/json',
    ],
    'body'             => '{"status":422,"detail":"The data provided was invalid"}',
];

$response = $serializer->fromArray($responseData);
assert($responseData == $serializer->toArray($response));
```

## Serialize PSR-7 Responses to Strings

If you want to serialize your response to an actual string HTTP message, or
parse an HTTP response into a PSR-7 instance, you can use
`Laminas\Diactoros\Serializer\Response\StringSerializer`.

It's constructor reads:

```php
public function __construct(
    Psr\Http\Message\ResponseFactoryInterface $responseFactory,
    Psr\Http\Message\StreamFactoryInterface $streamFactory
)
```

and it exposes the following methods:

```php
public function fromString(string $message): Psr\Http\Message\ResponseInterface
public function fromStream(Psr\Http\Message\StreamInterface $stream): Psr\Http\Message\ResponseInterface
public function toString(Psr\Http\Message\ResponseInterface $response): string
```

Let's consider the following HTTP response:

```http
HTTP/1.1 422 Invalid entity
Content-Type: application/json

{"status":422,"detail":"The data provided was invalid"}
```

Now, we will assign it to the value `$message` in the following example:

```php
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\Serializer\Response\StringSerializer;
use Laminas\Diactoros\StreamFactory;

$serializer = new StringSerializer(
    new ResponseFactory(),
    new StreamFactory()
);

$response = $serializer->fromString($message);
assert($message === $serializer->toString($response));
```
