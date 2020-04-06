<?php

namespace Smartbox\Integration\FrameworkBundle\Core\Serializers;

use JMS\Serializer\SerializerInterface;

trait UsesQueueSerializer
{
    private $serializer;

    /**
     * @return SerializerInterface
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