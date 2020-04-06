<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Serializers;

use Smartbox\Integration\FrameworkBundle\Core\Messages\MessageInterface;

interface QueueSerializerInterface
{
    public function decode(array $encodedMessage): MessageInterface;

    public function encode(MessageInterface $message): array;
}
