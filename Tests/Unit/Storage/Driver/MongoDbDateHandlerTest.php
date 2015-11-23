<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Storage\Driver;


use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\VisitorInterface;
use Smartbox\Integration\FrameworkBundle\Storage\Driver\MongoDBDateHandler;

/**
 * Class MongoDBDateHandlerTest
 * @package Smartbox\Integration\FrameworkBundle\Tests\Unit\Storage\Driver
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

        $mongoDate = $this->handler->convertDateTimeToMongoDate($visitor, $dateTime, [], $context);

        $this->assertEquals($dateTime, $mongoDate->toDateTime());
    }

    public function testItShouldCOnvertAMongoDateToADateTime()
    {
        $expectedDateTime = new \DateTime('2015-12-25 22:17:05');

        $sec = $expectedDateTime->getTimestamp();
        $mongoDate = new \MongoDate($sec);

        /** @var VisitorInterface $visitor */
        $visitor = $this->getMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->getMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertMongoDateToDateTime($visitor, $mongoDate, [], $context);

        $this->assertEquals($expectedDateTime, $convertedDateTime);
    }
}
