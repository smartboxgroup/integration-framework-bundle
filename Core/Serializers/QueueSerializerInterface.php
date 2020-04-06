<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\Core\Serializers;

use Smartbox\Integration\FrameworkBundle\Components\Queues\QueueMessageInterface;

interface QueueSerializerInterface
{
    public function decode(array $encodedMessage): QueueMessageInterface;

    public function encode(QueueMessageInterface $message): array;
}
