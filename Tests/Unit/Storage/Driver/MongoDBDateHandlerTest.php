<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Storage\Driver;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\VisitorInterface;
use Smartbox\Integration\FrameworkBundle\Components\DB\NoSQL\Drivers\MongoDB\MongoDBDateHandler;
use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;

/**
 * Class MongoDBDateHandlerTest.
 */
class MongoDBDateHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MongoDBDateHandler */
    private $handler;

    public function setUp(): void
    {
        if (!\extension_loaded('mongodb')) {
            $this->markTestSkipped(
                'The MongoDB extension is not available.'
            );

            return;
        }
        $this->handler = new MongoDBDateHandler();
    }

    public function testItShouldConvertADateTimeToAMongoDate()
    {
        $dateTime = new \DateTime('2015-12-25 22:17:05');

        /** @var VisitorInterface $visitor */
        $visitor = $this->createMock(VisitorInterface::class);
        /** @var SerializationContext $context */
        $context = $this->createMock(SerializationContext::class);

        $mongoDate = $this->handler->convertFromDateTimeToMongoFormat($visitor, $dateTime, [], $context);

        $this->assertEquals($dateTime, $mongoDate->toDateTime());
    }

    public function testItShouldConvertAMongoDateToADateTime()
    {
        $expectedDateTime = new \DateTime('2015-12-25 22:17:05');

        $mongoDate = MongoDBDateHandler::convertDateTimeToMongoFormat($expectedDateTime);

        /** @var VisitorInterface $visitor */
        $visitor = $this->createMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->createMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertFromMongoFormatToDateTime($visitor, $mongoDate, [], $context);

        $this->assertEquals($expectedDateTime, $convertedDateTime);
    }

    public function testOfBugWithLosingPrecisionDuringConversionFromDateTimeToMongoFormat()
    {
        $expectedDateTime = DateTimeHelper::createDateTimeFromCurrentMicrotime();

        $mongoDate = MongoDBDateHandler::convertDateTimeToMongoFormat($expectedDateTime);

        /** @var VisitorInterface $visitor */
        $visitor = $this->createMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->createMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertFromMongoFormatToDateTime($visitor, $mongoDate, [], $context);

        // after conversion of \DateTime with microseconds we lost some microseconds
        // so we have to replace 'xxx' with '000' in 2016-04-13T15:25:39.565xxx+00:00
        // bug is explained in MongoDBDateHandler::convertDateTimeToMongoFormat method
        $expectedDateTimeFormatted = \substr_replace($expectedDateTime->format('Y-m-d\TH:i:s.uP'), '000', 23, 3);
        $this->assertEquals($expectedDateTimeFormatted, $convertedDateTime->format('Y-m-d\TH:i:s.uP'));
    }
}
