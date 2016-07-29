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
        return [
            ['1463132313.1234'],
            ['1463132313.0000'],
            [1463132313.1234],
            [1463132313.0000],
            [1463132313]
        ];
    }

    public function negativeTimestampCaseProvider()
    {
        return [
            ['-2177449200.00000', '1901-01-01'],
            ['-2177449200', '1901-01-01'],
            ['-1.30023', '1969-12-31'],
            ['-1', '1969-12-31'],
        ];
    }

    /**
     * @dataProvider timestampCaseProvider
     */
    public function testCreateDateTimeFromTimestampWithMilliseconds($millis)
    {
        $datetime = DateTimeHelper::createDateTimeFromTimestampWithMilliseconds($millis);
        $this->assertInstanceOf(\DateTime::class, $datetime);
    }

    public function testCreateDateTimeFromCurrentMicrotime(){
        $datetime = DateTimeHelper::createDateTimeFromCurrentMicrotime();
        $this->assertInstanceOf(\DateTime::class, $datetime);
    }

    /**
     * @dataProvider negativeTimestampCaseProvider
     */
    public function testCreateDateTimeFromTimestampWithMillisecondsWithNegativeTimestamps($millis, $expectedDate)
    {
        $datetime = DateTimeHelper::createDateTimeFromTimestampWithMilliseconds($millis);
        $this->assertEquals($expectedDate, $datetime->format('Y-m-d'));
    }
}
