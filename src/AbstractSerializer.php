<?php

declare(strict_types=1);

namespace Laminas\Psr7\Serializer;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractSerializer
{
    /**
     * @param array $headers $headers Array of key value pairs, where each are
     *     string values, the key represents the header name, and the value
     *     represents its value (either a string or array of strings).
     * @psalm-param MessageInterface $message
     * @return (
     *     $message is RequestInterface
     *     ? RequestInterface
     *     : (
     *         $message is ResponseInterface
     *         ? ResponseInterface
     *         : MessageInterface
     *     )
     * )
     */
    protected function injectHeaders(array $headers, MessageInterface $message): MessageInterface
    {
        foreach ($headers as $header => $value) {
            $message = $message->withHeader($header, $value);
        }
        return $message;
    }
}
