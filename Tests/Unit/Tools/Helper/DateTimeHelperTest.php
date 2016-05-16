<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Messages;

use Smartbox\Integration\FrameworkBundle\Tools\Helper\DateTimeHelper;

/**
 * Class ContextTest.
 */
class DateTimeHelperTest extends \PHPUnit_Framework_TestCase
{
    public function timestampCaseProvider(){
        return [
            ['1463132313.1234'],
            ['1463132313.0000'],
            [1463132313.1234],
            [1463132313.0000],
            [1463132313]
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
}
