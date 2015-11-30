<?php

namespace Smartbox\Integration\FrameworkBundle\Storage\Driver;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Context;
use JMS\Serializer\VisitorInterface;

/**
 * Class MongoDBDateHandler
 * @package Smartbox\Integration\FrameworkBundle\Storage\Driver
 */
class MongoDBDateHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'mongo_array',
                'type' => 'DateTime',
                'method' => 'convertDateTimeToMongoDate',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'mongo_array',
                'type' => 'DateTime',
                'method' => 'convertMongoDateToDateTime',
            ),
        );
    }

    /**
     * @param VisitorInterface $visitor
     * @param \DateTime $date
     * @param array $type
     * @param Context $context
     * @return \MongoDate
     */
    public function convertDateTimeToMongoDate(VisitorInterface $visitor, \DateTime $date, array $type, Context $context)
    {
        return new \MongoDate($date->getTimestamp(), $date->format('u'));
    }

    /**
     * @param VisitorInterface $visitor
     * @param \MongoDate $date
     * @param array $type
     * @param Context $context
     * @return \DateTime
     */
    public function convertMongoDateToDateTime(VisitorInterface $visitor, \MongoDate $date, array $type, Context $context)
    {
        return $date->toDateTime();
    }
}