<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Serializers;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

interface QueueSerializerInterface
{
    /**
     * Decodes the message provided by the broker to what the consumer expects.
     *
     * The most common keys received in $encodedMessage are:
     * - `body` (string) - the message body
     * - `headers` (string<string>) - a key/value pair of headers
     */
    public function decode(array $encodedMessage): MessageInterface;

    /**
     * Encode the message provided by the producer to what the driver expects.
     *
     * The most common keys returned are:
     * - `body` (string) - the message body
     * - `headers` (string<string>) - a key/value pair of headers
     */
    public function encode(MessageInterface $message): array;
}
