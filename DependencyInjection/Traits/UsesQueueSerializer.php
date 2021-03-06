<?php

declare(strict_types=1);

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;

use Smartbox\Integration\FrameworkBundle\Core\Serializers\QueueSerializerInterface;

trait UsesQueueSerializer
{
    private $serializer;

    /**
     * @return QueueSerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    public function setSerializer(QueueSerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
