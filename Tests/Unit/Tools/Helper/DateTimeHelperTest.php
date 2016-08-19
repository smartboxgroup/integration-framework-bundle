<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages;

use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;

/**
 * Class DateTimeHelperTest.
 */
class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function timestampCaseProvider()
    {
        // milliseconds, expected date
        return [
            'positive' => [1463132313.1234, '2016-05-13'],
            'positiveZeroMillis' => [1463132313.0000, '2016-05-13'],
            'positiveNoMillis' => [1463132313, '2016-05-13'],
            'positive1Second' => [1.1234, '1970-01-01'],
            'positive1SecondZeroMillis' => [1.0000, '1970-01-01'],
            'positive1SecondNoMillis' => [1, '1970-01-01'],
            'negative' => [-1463132313.1234, '1923-08-21'],
            'negativeZeroMillis' => [-1463132313.00000, '1923-08-21'],
            'negativeNoMillis' => [-1463132313, '1923-08-21'],
            'negative1Second' => [-1.1234, '1969-12-31'],
            'negative1SecondZeroMillis' => [-1.0000, '1969-12-31'],
            'negative1SecondNoMillis' => [-1, '1969-12-31'],
            'stringPositive' => ['1463132313.1234', '2016-05-13'],
            'stringPositiveZeroMillis' => ['1463132313.0000', '2016-05-13'],
            'stringPositiveNoMillis' => ['1463132313', '2016-05-13'],
            'stringPositive1Second' => ['1.1234', '1970-01-01'],
            'stringPositive1SecondZeroMillis' => ['1.0000', '1970-01-01'],
            'stringPositive1SecondNoMillis' => ['1', '1970-01-01'],
            'stringNegative' => ['-1463132313.1234', '1923-08-21'],
            'stringNegativeZeroMillis' => ['-1463132313.00000', '1923-08-21'],
            'stringNegativeNoMillis' => ['-1463132313', '1923-08-21'],
            'stringNegative1Second' => ['-1.1234', '1969-12-31'],
            'stringNegative1SecondZeroMillis' => ['-1.0000', '1969-12-31'],
            'stringNegative1SecondNoMillis' => ['-1', '1969-12-31'],
        ];
    }

    /**
     * @dataProvider timestampCaseProvider
     */
    public function testCreateDateTimeFromTimestampWithMilliseconds($millis, $expectedDate)
    {
        $datetime = DateTimeHelper::createDateTimeFromTimestampWithMilliseconds($millis);
        $this->assertEquals($expectedDate, $datetime->format('Y-m-d'));
    }

    public function testCreateDateTimeFromCurrentMicrotime()
    {
        $datetime = DateTimeHelper::createDateTimeFromCurrentMicrotime();
        $this->assertInstanceOf(\DateTime::class, $datetime);
    }
}
