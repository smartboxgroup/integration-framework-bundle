<?php

namespace Smartbox\Integration\FrameworkBundle\Util;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Monolog\Formatter\JsonFormatter;

/**
 * Class JMSSerializerFormatter
 * @package Smartbox\Integration\FrameworkBundle\Util
 */
class JMSSerializerFormatter extends JsonFormatter
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $context = new SerializationContext();
        $context->setGroups(['logs']);
        $context->setSerializeNull(true);

        return $this->serializer->serialize($record, 'json', $context);
    }
}