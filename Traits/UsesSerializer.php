<?php

namespace Smartbox\Integration\FrameworkBundle\Traits;


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
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
    }
}