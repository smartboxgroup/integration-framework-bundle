<?php

namespace Smartbox\Integration\FrameworkBundle\Tests\Unit\Exceptions\Handler;

use PHPUnit\Framework\TestCase;
use Smartbox\Integration\FrameworkBundle\Components\WebService\HasShowExternalSystemErrorMessage;

class HasShowExternalSystemErrorMessageTraitTest extends TestCase
{
    /**
     * @param $message
     * @param $code
     *
     * @group externalSystemException
     * @dataProvider provideExceptionData
     */
    public function testOriginalCode($code)
    {
        /** @var HasShowExternalSystemErrorMessage $mock */
        $mock = $this->getMockForTrait(HasShowExternalSystemErrorMessage::class);

        $mock->setOriginalCode($code);

        $this->assertTrue(is_int($mock->getOriginalCode()), 'The code should be an integer');
    }

    public function provideExceptionData()
    {
        return [
            "Code as string" => [
                "ANY_CODE"
            ],
            "Integer code as string" => [
                "404"
            ],
            "Code as Integer" => [
                400
            ]
        ];
    }
}