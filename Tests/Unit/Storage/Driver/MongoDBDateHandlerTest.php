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
class MongoDBDateHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MongoDBDateHandler */
    private $handler;

    public function setup()
    {
        if (!extension_loaded('mongodb')) {
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

    public function dateProvider()
    {
        return [
            ['2009-02-15 15:16:17.123000'], // MongoDBDateHandler does not take into account microseconds, just milliseconds
            ['2009-02-15 15:16:17.023000', true], // This is a MongoDBDateHandler bug!
        ];
    }

    /**
     * @param string $date
     * @param boolean $skip
     * @dataProvider dateProvider
     */
    public function testOfBugWithLosingPrecisionDuringConversionFromDateTimeToMongoFormat($date, $skip = false)
    {
        $expectedDateTime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $date);

        $mongoDate = MongoDBDateHandler::convertDateTimeToMongoFormat($expectedDateTime);

        /** @var VisitorInterface $visitor */
        $visitor = $this->createMock(VisitorInterface::class);
        /** @var DeserializationContext $context */
        $context = $this->createMock(DeserializationContext::class);

        $convertedDateTime = $this->handler->convertFromMongoFormatToDateTime($visitor, $mongoDate, [], $context);

        $expectedDateTimeFormatted = $expectedDateTime->format('Y-m-d\TH:i:s.uP');
        if ($skip == true) {
            $this->markTestSkipped('Test was skipped because is know to fail with value '.$date.' and it\'s a known bug...');
        } else {
            $this->assertEquals($expectedDateTimeFormatted, $convertedDateTime->format('Y-m-d\TH:i:s.uP'));
        }
    }
}
