<?php

namespace Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;
use MongoDB\BSON\UTCDatetime;

/**
 * Class MongoDBDateHandler.
 */
class MongoDBDateHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => 'mongo_array',
                'type' => 'DateTime',
                'method' => 'convertFromDateTimeToMongoFormat',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format' => 'mongo_array',
                'type' => 'DateTime',
                'method' => 'convertFromMongoFormatToDateTime',
            ],
        ];
    }

    /**
     * @param VisitorInterface $visitor
     * @param \DateTime        $date
     * @param array            $type
     * @param Context          $context
     *
     * @return \MongoDB\BSON\UTCDateTime
     */
    public function convertFromDateTimeToMongoFormat(VisitorInterface $visitor, \DateTime $date, array $type, Context $context)
    {
        return self::convertDateTimeToMongoFormat($date);
    }

    /**
     * Method converts \MongoDB\BSON\UTCDateTime to \DateTime.
     *
     * @param VisitorInterface $visitor
     * @param UTCDatetime      $date
     * @param array            $type
     * @param Context          $context
     *
     * @return \DateTime
     */
    public function convertFromMongoFormatToDateTime(VisitorInterface $visitor, $date, array $type, Context $context)
    {
        /**
         * this $dateTime object is incorrect in case of using microseconds
         * because after conversion of \MongoDB\BSON\UTCDateTime to \DateTime
         * method $dateTime->format('U.u') returns invalid string xxxxxxxxx.zzzzzzzzz
         * part after "." contains 9 digits but it should contain up to 6 digits
         * so we have to reduce this part to 6 digits.
         *
         * @var \DateTime
         */
        if (!is_string($date) && !$date instanceof UTCDatetime) {
            throw new \InvalidArgumentException('The provided date must be a valid string or an instance of UTCDateTime');
        }

        if (is_string($date)) {
            $dateTime = date_create($date);
            if (false === $dateTime) {
                throw new \InvalidArgumentException(sprintf('The provided date "%s" is not a valid date/time string', $date));
            }

            return $dateTime;
        }

        return self::convertMongoFormatToDateTime($date);
    }

    /**
     * Method converts \MongoDB\BSON\UTCDateTime to \DateTime preserving milliseconds.
     *
     * @param UTCDatetime $date
     *
     * @return \DateTime
     */
    public static function convertMongoFormatToDateTime(UTCDatetime $date)
    {
        /**
         * this $dateTime object is incorrect in case of using microseconds
         * because after conversion of \MongoDB\BSON\UTCDateTime to \DateTime
         * method $dateTime->format('U.u') returns invalid string xxxxxxxxx.zzzzzzzzz
         * part after "." contains 9 digits but it should contain up to 6 digits
         * so we have to reduce this part to 6 digits.
         *
         * @var \DateTime
         */
        $dateTime = $date->toDateTime();

        $seconds = $dateTime->format('U');
        $microseconds = substr($dateTime->format('u'), 0, 5); // we allow max 6 digits
        $fixedDateTime = DateTimeHelper::createDateTimeFromTimestampWithMilliseconds(sprintf('%s.%s', $seconds, $microseconds));

        return $fixedDateTime;
    }

    /**
     * Method converts \DateTime to \MongoDB\BSON\UTCDateTime.
     *
     * With this conversion we may loose some precision because \MongoDB\BSON\UTCDateTime accepts milliseconds as integer
     * so everything under 1000 microseconds will be lost
     *
     * @param \DateTime $date
     *
     * @return \MongoDB\BSON\UTCDateTime
     */
    public static function convertDateTimeToMongoFormat(\DateTime $date)
    {
        return new UTCDateTime(self::getUnixTimestampWithMilliseconds($date));
    }

    /**
     * This method returns milliseconds as integer value (we need milliseconds to create \MongoDB\BSON\UTCDateTime).
     *
     * In case of having microseconds in DateTime we will loose some precision
     * because to represent f.e.: 1800 microseconds as milliseconds we should use float value 1.8
     * but this method should return integer so we will round to 1 (0.8 will be lost)
     *
     * @param \DateTime $dateTime
     *
     * @return int
     */
    protected static function getUnixTimestampWithMilliseconds(\DateTime $dateTime)
    {
        $millisecondsFromSeconds = intval($dateTime->format('U')) * 1000;
        $millisecondsFromMicroseconds = intval($dateTime->format('u') / 1000);
        $timestampWithMillis = $millisecondsFromSeconds + $millisecondsFromMicroseconds;

        return $timestampWithMillis;
    }
}
