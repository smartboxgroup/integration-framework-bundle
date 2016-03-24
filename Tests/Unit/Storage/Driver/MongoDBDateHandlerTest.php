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

    public function testItShouldCOnvertAMongoDateToADateTime()
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
}
