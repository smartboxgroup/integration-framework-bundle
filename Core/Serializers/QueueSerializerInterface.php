<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Serializers;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

interface QueueSerializerInterface
{
    /**
     * Decodes the message provided by the broker to what the consumer expects.
     *
     * @param array $encodedMessage
     *
     * @return MessageInterface
     */
    public function decode(array $encodedMessage): MessageInterface;

    /**
     * Encode the message provided by the producer to what the driver expects.
     *
     * @param MessageInterface $message
     *
     * @return array
     */
    public function encode(MessageInterface $message): array;
}
