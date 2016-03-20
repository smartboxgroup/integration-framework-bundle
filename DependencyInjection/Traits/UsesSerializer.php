<?php

namespace Smartbox\Integration\FrameworkBundle\DependencyInjection\Traits;


use JMS\Serializer\SerializerInterface;

trait UsesSerializer
{

    /** @var  SerializerInterface */
    protected $serializer;

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}