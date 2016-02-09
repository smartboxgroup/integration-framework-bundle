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
                'method' => 'convertFromDateTimeToMongoFormat',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'mongo_array',
                'type' => 'DateTime',
                'method' => 'convertFromMongoFormatToDateTime',
            ),
        );
    }

    /**
     * @param VisitorInterface $visitor
     * @param \DateTime $date
     * @param array $type
     * @param Context $context
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function convertFromDateTimeToMongoFormat(VisitorInterface $visitor, \DateTime $date, array $type, Context $context)
    {
        return new \MongoDB\BSON\UTCDateTime($date->format('u'));
    }

    /**
     * @param VisitorInterface $visitor
     * @param \MongoDB\BSON\UTCDateTime $date
     * @param array $type
     * @param Context $context
     * @return \DateTime
     */
    public function convertFromMongoFormatToDateTime(VisitorInterface $visitor, \MongoDB\BSON\UTCDateTime $date, array $type, Context $context)
    {
        return $date->toDateTime();
    }
}