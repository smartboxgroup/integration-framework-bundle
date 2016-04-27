<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Storage\Driver;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\VisitorInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDBDateHandler;

/**
 * Class MongoDBDateHandlerTest.
 */
class MongoDBDateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MongoDBDateHandler */
    private $handler;

    public function setup()
    {
        $this->handler = new MongoDBDateHandler();
    }

    public function testItShouldConvertADateTimeToAMongoDate()
    {
        $dateTime = new \DateTime('2015-12-25 22:17:05');

        /** @var VisitorInterface $visitor */
        $visitor = $this->getMock(VisitorInterface::class);
        /** @var SerializationContext $context */
        $context = $this->getMock(SerializationContext::class);

        $mongoDate = $this->handler->convertFromDateTimeToMongoFormat($visitor, $dateTime, [], $context);

        $this->assertEquals($dateTime, $mongoDate->toDateTime());
    }

    public function testItShouldConvertAMongoDateToADateTime()
    {
        $expectedDateTime = new \DateTime('2015-12-25 22:17:05');

        $mongoDate = MongoDBDateHandler::convertDateTimeToMongoFormat($expectedDateTime);

        /** @var VisitorInterface $visitor */
        $visitor = $this->getMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->getMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertFromMongoFormatToDateTime($visitor, $mongoDate, [], $context);

        $this->assertEquals($expectedDateTime, $convertedDateTime);
    }

    public function testOfBugWithLosingPrecisionDuringConversionFromDateTimeToMongoFormat()
    {
        $expectedDateTime = \DateTime::createFromFormat('U.u', microtime(true), new \DateTimeZone('UTC'));

        $mongoDate = MongoDBDateHandler::convertDateTimeToMongoFormat($expectedDateTime);

        /** @var VisitorInterface $visitor */
        $visitor = $this->getMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->getMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertFromMongoFormatToDateTime($visitor, $mongoDate, [], $context);

        // after conversion of \DateTime with microseconds we lost some microseconds
        // so we have to replace 'xxx' with '000' in 2016-04-13T15:25:39.565xxx+00:00
        // bug is explained in MongoDBDateHandler::convertDateTimeToMongoFormat method
        $expectedDateTimeFormatted = substr_replace($expectedDateTime->format('Y-m-d\TH:i:s.uP'), '000', 23, 3);
        $this->assertEquals($expectedDateTimeFormatted, $convertedDateTime->format('Y-m-d\TH:i:s.uP'));
    }
}